<?php
/**
 * settle_bets.php — Auto-settlement cron script
 *
 * Checks pending bets against sport2 event results.
 * Events with status != 1 (finished/cancelled) are candidates for settlement.
 *
 * Usage:
 *   php settle_bets.php              # Normal run
 *   php settle_bets.php --dry-run    # Preview without settling
 *   php settle_bets.php --auto-lock  # Also run auto-lock check
 *
 * Cron: every 5 min — 0/5 * * * * php /path/to/sport2/settle_bets.php >> /path/to/logs/settle.log 2>&1
 */

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/bootstrap.php'; // Parent app

use Sport2\Core\Database as Sport2Db;
use App\Core\Database as MainDb;
use App\Admin\WalletManager;
use Sport2\Betting\CashoutManager;

$dryRun  = in_array('--dry-run', $argv ?? []);
$autoLock = in_array('--auto-lock', $argv ?? []);

$startTime = microtime(true);
$log = fn(string $msg) => printf("[%s] %s\n", date('Y-m-d H:i:s'), $msg);

$log("=== Settlement run started" . ($dryRun ? ' (DRY RUN)' : '') . " ===");

try {
    $sportDb = Sport2Db::get();
    $mainDb  = MainDb::getInstance($config['db'])->getPdo();
    $wallet  = new WalletManager($mainDb);

    // Auto-lock check
    if ($autoLock) {
        $cashoutMgr = new CashoutManager($sportDb, $mainDb);
        $locked = $cashoutMgr->autoLockCheck();
        $log("Auto-lock: {$locked} bets locked due to odds movement");
    }

    // 1. Get all pending bets with their selections
    $stmt = $mainDb->query("
        SELECT b.id AS bet_id, b.user_id, b.bet_type, b.stake, b.total_odds, b.potential_payout, b.locked,
               bs.id AS sel_id, bs.event_id, bs.selection_id, bs.odds_at_placement, bs.status AS sel_status
        FROM bets b
        JOIN bet_selections bs ON bs.bet_id = b.id
        WHERE b.status = 'pending'
        ORDER BY b.id
    ");
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        $log("No pending bets to settle.");
        exit(0);
    }

    // Group by bet
    $bets = [];
    foreach ($rows as $r) {
        $bid = $r['bet_id'];
        if (!isset($bets[$bid])) {
            $bets[$bid] = [
                'bet_id'          => (int)$bid,
                'user_id'         => (int)$r['user_id'],
                'bet_type'        => $r['bet_type'],
                'stake'           => (float)$r['stake'],
                'total_odds'      => (float)$r['total_odds'],
                'potential_payout' => (float)$r['potential_payout'],
                'locked'          => (bool)$r['locked'],
                'selections'      => [],
            ];
        }
        $bets[$bid]['selections'][] = [
            'sel_id'       => (int)$r['sel_id'],
            'event_id'     => (int)$r['event_id'],
            'selection_id' => (int)$r['selection_id'],
            'odds'         => (float)$r['odds_at_placement'],
            'status'       => $r['sel_status'],
        ];
    }

    $log("Found " . count($bets) . " pending bets with " . count($rows) . " total selections");

    // 2. Get unique event IDs and their status from sport2
    $allEventIds = [];
    foreach ($bets as $bet) {
        foreach ($bet['selections'] as $sel) {
            $allEventIds[$sel['event_id']] = true;
        }
    }

    $eventIds = array_keys($allEventIds);
    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));

    $evtStmt = $sportDb->prepare("
        SELECT e.id, e.name, e.status, e.is_live, e.live_score
        FROM events e
        WHERE e.id IN ({$placeholders})
    ");
    $evtStmt->execute($eventIds);
    $events = $evtStmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    // 3. Get selection outcomes from sport2 (the API syncs result data)
    // A selection is "won" if the event is finished AND the selection's outcome matches
    // For Altenar: we check if the selection is marked as winning by the API
    // since the sync updates selection status
    $allSelIds = [];
    foreach ($bets as $bet) {
        foreach ($bet['selections'] as $sel) {
            $allSelIds[$sel['selection_id']] = true;
        }
    }

    $selIds = array_keys($allSelIds);
    $selPlaceholders = implode(',', array_fill(0, count($selIds), '?'));

    $selStmt = $sportDb->prepare("
        SELECT s.id, s.is_active, s.odds, s.result,
               m.status AS market_status,
               e.id AS event_id, e.status AS event_status
        FROM selections s
        JOIN markets m ON s.market_id = m.id
        JOIN events e ON m.event_id = e.id
        WHERE s.id IN ({$selPlaceholders})
    ");
    $selStmt->execute($selIds);
    $selectionData = $selStmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    // 4. Settle bets
    $settled = 0;
    $won = 0;
    $lost = 0;
    $partial = 0;

    foreach ($bets as $bet) {
        $betId = $bet['bet_id'];
        $allResolved = true;
        $allWon = true;
        $anyLost = false;

        foreach ($bet['selections'] as $sel) {
            $selData = $selectionData[$sel['selection_id']] ?? null;
            $evtData = $events[$sel['event_id']] ?? null;

            // Event still active/live — skip this bet
            if (!$evtData || $evtData['status'] == 1) {
                $allResolved = false;
                break;
            }

            // Event is finalized (status != 1)
            // Determine if selection won or lost
            if ($selData) {
                // result: 1=won, 2=lost, 3=void (Altenar convention), 0=pending
                $resultStatus = (int)($selData['result'] ?? 0);

                if ($resultStatus === 0) {
                    // Result not yet posted
                    $allResolved = false;
                    break;
                } elseif ($resultStatus === 1) {
                    // Won
                } elseif ($resultStatus === 3) {
                    // Void — treat as won with odds 1.0 (refund this leg)
                } else {
                    // Lost (resultStatus === 2 or anything else)
                    $anyLost = true;
                    $allWon = false;
                }
            } else {
                // Selection not found in sport2 — can't settle
                $allResolved = false;
                break;
            }
        }

        if (!$allResolved) {
            continue;
        }

        // ── SETTLE THIS BET ──
        $settled++;

        if ($dryRun) {
            $outcome = $anyLost ? 'LOST' : 'WON';
            $log("[DRY] Bet #{$betId} → {$outcome} (stake: {$bet['stake']}, payout: {$bet['potential_payout']})");
            if (!$anyLost) $won++;
            else $lost++;
            continue;
        }

        $mainDb->beginTransaction();
        try {
            if ($anyLost) {
                // BET LOST
                $mainDb->prepare("UPDATE bets SET status = 'lost', settled_at = NOW() WHERE id = ?")->execute([$betId]);

                // Update individual selections
                foreach ($bet['selections'] as $sel) {
                    $selData = $selectionData[$sel['selection_id']] ?? null;
                    $resultStatus = (int)($selData['result'] ?? 2);
                    $selOutcome = $resultStatus === 1 ? 'won' : ($resultStatus === 3 ? 'void' : 'lost');

                    $mainDb->prepare("
                        UPDATE bet_selections SET status = ?, settled_at = NOW() WHERE id = ?
                    ")->execute([$selOutcome, $sel['sel_id']]);
                }

                $lost++;
                $log("Bet #{$betId} LOST (user #{$bet['user_id']}, stake: {$bet['stake']})");

            } else {
                // BET WON — credit payout
                $payout = $bet['potential_payout'];

                $wallet->credit(
                    $bet['user_id'],
                    $payout,
                    'bet_win',
                    $betId,
                    "Bet #{$betId} won — payout {$payout}",
                    $bet['user_id'],
                    'payout',
                    "Won bet #{$betId} @ {$bet['total_odds']} odds"
                );

                $mainDb->prepare("UPDATE bets SET status = 'won', settled_at = NOW() WHERE id = ?")->execute([$betId]);

                // Update individual selections
                foreach ($bet['selections'] as $sel) {
                    $selData = $selectionData[$sel['selection_id']] ?? null;
                    $resultStatus = (int)($selData['result'] ?? 1);
                    $selOutcome = $resultStatus === 3 ? 'void' : 'won';

                    $mainDb->prepare("
                        UPDATE bet_selections SET status = ?, settled_at = NOW() WHERE id = ?
                    ")->execute([$selOutcome, $sel['sel_id']]);
                }

                $won++;
                $log("Bet #{$betId} WON (user #{$bet['user_id']}, payout: {$payout})");
            }

            $mainDb->commit();
        } catch (\Throwable $e) {
            $mainDb->rollBack();
            $log("ERROR settling bet #{$betId}: " . $e->getMessage());
        }
    }

    $duration = round((microtime(true) - $startTime) * 1000);
    $log("=== Settlement complete: {$settled} settled ({$won} won, {$lost} lost) in {$duration}ms ===");

} catch (\Throwable $e) {
    $log("FATAL: " . $e->getMessage());
    exit(1);
}
