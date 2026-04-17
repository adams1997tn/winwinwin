<?php

declare(strict_types=1);

namespace Sport2\Betting;

use PDO;
use App\Admin\WalletManager;

class BettingManager
{
    private PDO $sportDb;   // sport2 database
    private PDO $mainDb;    // sportsbook database
    private WalletManager $wallet;

    public function __construct(PDO $sportDb, PDO $mainDb)
    {
        $this->sportDb = $sportDb;
        $this->mainDb  = $mainDb;
        $this->wallet  = new WalletManager($mainDb);
    }

    // ═══════════════════════════════════════════
    //  SETTINGS
    // ═══════════════════════════════════════════

    public function getSettings(): array
    {
        $stmt = $this->mainDb->query("SELECT setting_key, setting_value FROM bet_settings");
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        return $settings;
    }

    public function updateSetting(string $key, string $value): void
    {
        $stmt = $this->mainDb->prepare("UPDATE bet_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }

    // ═══════════════════════════════════════════
    //  PLACE BET
    // ═══════════════════════════════════════════

    /**
     * Place a bet.
     *
     * @param int    $userId     Authenticated user ID
     * @param string $betType    single|combo|system
     * @param float  $stake      Bet amount
     * @param array  $selections Array of ['selection_id' => int, 'odds' => float]
     * @return array The created bet record
     */
    public function placeBet(int $userId, string $betType, float $stake, array $selections): array
    {
        $settings = $this->getSettings();

        // Validate bet type
        if (!in_array($betType, ['single', 'combo', 'system'], true)) {
            throw new \InvalidArgumentException('Invalid bet type');
        }

        // Validate stake
        $minStake = (float)($settings['min_stake'] ?? 100);
        $maxStake = (float)($settings['max_stake'] ?? 500000);
        if ($stake < $minStake) {
            throw new \InvalidArgumentException("Minimum stake is {$minStake}");
        }
        if ($stake > $maxStake) {
            throw new \InvalidArgumentException("Maximum stake is {$maxStake}");
        }

        // Validate selections count
        if (empty($selections)) {
            throw new \InvalidArgumentException('No selections provided');
        }
        if ($betType === 'single' && count($selections) !== 1) {
            throw new \InvalidArgumentException('Single bet must have exactly 1 selection');
        }
        if ($betType === 'combo' && count($selections) < 2) {
            throw new \InvalidArgumentException('Combo bet must have at least 2 selections');
        }
        $maxSelections = (int)($settings['max_selections'] ?? 20);
        if (count($selections) > $maxSelections) {
            throw new \InvalidArgumentException("Maximum {$maxSelections} selections allowed");
        }

        // Verify all selections are valid and active in sport2
        $selectionIds = array_column($selections, 'selection_id');
        $placeholders = implode(',', array_fill(0, count($selectionIds), '?'));

        $stmt = $this->sportDb->prepare("
            SELECT s.id, s.name AS sel_name, s.odds, s.is_active,
                   m.id AS market_id, m.name AS market_name, m.status AS market_status,
                   e.id AS event_id, e.name AS event_name, e.status AS event_status, e.is_live
            FROM selections s
            JOIN markets m ON s.market_id = m.id
            JOIN events e ON m.event_id = e.id
            WHERE s.id IN ({$placeholders})
        ");
        $stmt->execute($selectionIds);
        $dbSelections = $stmt->fetchAll();

        if (count($dbSelections) !== count($selectionIds)) {
            throw new \InvalidArgumentException('One or more selections not found');
        }

        // Check live betting enabled
        $liveBetting = (bool)($settings['allow_live_betting'] ?? true);

        // Build selection data & verify
        $totalOdds = 1.0;
        $selData = [];
        $oddsMap = [];
        foreach ($selections as $sel) {
            $oddsMap[$sel['selection_id']] = (float)$sel['odds'];
        }

        $eventIds = [];
        foreach ($dbSelections as $db) {
            if (!$db['is_active']) {
                throw new \InvalidArgumentException("Selection '{$db['sel_name']}' is suspended");
            }
            if ($db['event_status'] != 1) {
                throw new \InvalidArgumentException("Event '{$db['event_name']}' is not active");
            }
            if ($db['market_status'] != 1) {
                throw new \InvalidArgumentException("Market '{$db['market_name']}' is suspended");
            }
            if ($db['is_live'] && !$liveBetting) {
                throw new \InvalidArgumentException('Live betting is currently disabled');
            }

            // Check for duplicate events in combo/system
            if ($betType !== 'single') {
                if (in_array($db['event_id'], $eventIds, true)) {
                    throw new \InvalidArgumentException('Cannot select multiple outcomes from the same event');
                }
                $eventIds[] = $db['event_id'];
            }

            $currentOdds = (float)$db['odds'];
            $submittedOdds = $oddsMap[$db['id']];

            // Check odds change threshold
            $threshold = (float)($settings['odds_change_threshold'] ?? 0.15);
            if ($submittedOdds > 0 && abs($currentOdds - $submittedOdds) / $submittedOdds > $threshold) {
                throw new \InvalidArgumentException(
                    "Odds changed significantly for '{$db['sel_name']}': was {$submittedOdds}, now {$currentOdds}"
                );
            }

            $totalOdds *= $currentOdds;

            $selData[] = [
                'event_id'       => $db['event_id'],
                'market_id'      => $db['market_id'],
                'selection_id'   => $db['id'],
                'event_name'     => $db['event_name'],
                'market_name'    => $db['market_name'],
                'selection_name' => $db['sel_name'],
                'odds'           => $currentOdds,
            ];
        }

        $totalOdds = round($totalOdds, 4);
        $potentialPayout = round($stake * $totalOdds, 3);

        // Check max payout
        $maxPayout = (float)($settings['max_payout'] ?? 5000000);
        if ($potentialPayout > $maxPayout) {
            throw new \InvalidArgumentException(
                "Potential payout ({$potentialPayout}) exceeds maximum ({$maxPayout})"
            );
        }

        // Check min odds for single
        if ($betType === 'single') {
            $minOdds = (float)($settings['min_odds_single'] ?? 1.10);
            if ($totalOdds < $minOdds) {
                throw new \InvalidArgumentException("Minimum odds for single bet is {$minOdds}");
            }
        }

        // Build selections_json for legacy field
        $selectionsJson = json_encode(array_map(fn($s) => [
            'event_id'     => $s['event_id'],
            'market_id'    => $s['market_id'],
            'selection_id' => $s['selection_id'],
            'event_name'   => $s['event_name'],
            'market_name'  => $s['market_name'],
            'selection_name' => $s['selection_name'],
            'odds'         => $s['odds'],
        ], $selData));

        // ── ATOMIC: debit wallet + insert bet ──
        $this->mainDb->beginTransaction();
        try {
            // Debit user's balance
            $this->wallet->debit(
                $userId,
                $stake,
                'bet_place',
                null, // source_id will be updated after bet creation
                "Bet placement: {$betType} - {$totalOdds} odds",
                $userId,
                'bet_placement',
                "Placed {$betType} bet @ {$totalOdds} odds"
            );

            // Insert bet
            $stmt = $this->mainDb->prepare("
                INSERT INTO bets (user_id, bet_type, selections_json, total_odds, stake, potential_payout, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $userId,
                $betType,
                $selectionsJson,
                $totalOdds,
                $stake,
                $potentialPayout,
            ]);
            $betId = (int)$this->mainDb->lastInsertId();

            // Insert normalized selections
            $insStmt = $this->mainDb->prepare("
                INSERT INTO bet_selections
                    (bet_id, event_id, market_id, selection_id, event_name, market_name, selection_name, odds_at_placement)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($selData as $s) {
                $insStmt->execute([
                    $betId,
                    $s['event_id'],
                    $s['market_id'],
                    $s['selection_id'],
                    $s['event_name'],
                    $s['market_name'],
                    $s['selection_name'],
                    $s['odds'],
                ]);
            }

            // Update wallet_ledger source_id with bet ID
            $this->mainDb->prepare("
                UPDATE wallet_ledger SET source_id = ?
                WHERE user_id = ? AND source_type = 'bet_place' AND source_id IS NULL
                ORDER BY id DESC LIMIT 1
            ")->execute([$betId, $userId]);

            $this->mainDb->commit();

            return $this->getBet($betId);

        } catch (\Throwable $e) {
            $this->mainDb->rollBack();
            throw $e;
        }
    }

    // ═══════════════════════════════════════════
    //  QUERIES
    // ═══════════════════════════════════════════

    public function getBet(int $betId): ?array
    {
        $stmt = $this->mainDb->prepare("
            SELECT b.*, u.username
            FROM bets b
            JOIN users u ON u.id = b.user_id
            WHERE b.id = ?
        ");
        $stmt->execute([$betId]);
        $bet = $stmt->fetch();
        if (!$bet) return null;

        $bet['selections'] = $this->getBetSelections($betId);
        return $bet;
    }

    public function getBetSelections(int $betId): array
    {
        $stmt = $this->mainDb->prepare("
            SELECT * FROM bet_selections WHERE bet_id = ? ORDER BY id
        ");
        $stmt->execute([$betId]);
        return $stmt->fetchAll();
    }

    /**
     * Get user bets with filtering.
     */
    public function getUserBets(int $userId, string $filter = 'all', int $limit = 50, int $offset = 0): array
    {
        $where = ['b.user_id = ?'];
        $params = [$userId];

        switch ($filter) {
            case 'active':
                $where[] = "b.status = 'pending'";
                break;
            case 'settled':
                $where[] = "b.status IN ('won','lost')";
                break;
            case 'cancelled':
                $where[] = "b.status IN ('cancelled','voided')";
                break;
            case 'cashout':
                $where[] = "b.cashout_amount IS NOT NULL";
                break;
        }

        $whereSQL = implode(' AND ', $where);

        // Count
        $countStmt = $this->mainDb->prepare("SELECT COUNT(*) FROM bets b WHERE {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->mainDb->prepare("
            SELECT b.* FROM bets b
            WHERE {$whereSQL}
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        $bets = $stmt->fetchAll();

        // Attach selections
        foreach ($bets as &$bet) {
            $bet['selections'] = $this->getBetSelections((int)$bet['id']);
        }

        return ['bets' => $bets, 'total' => $total];
    }

    // ═══════════════════════════════════════════
    //  CANCEL BET (user-initiated, only if pending)
    // ═══════════════════════════════════════════

    public function cancelBet(int $betId, int $userId): array
    {
        $bet = $this->getBet($betId);
        if (!$bet) {
            throw new \RuntimeException('Bet not found');
        }
        if ((int)$bet['user_id'] !== $userId) {
            throw new \RuntimeException('Unauthorized');
        }
        if ($bet['status'] !== 'pending') {
            throw new \RuntimeException('Only pending bets can be cancelled');
        }
        if ($bet['locked']) {
            throw new \RuntimeException('This bet is locked and cannot be cancelled');
        }

        // Check if any event has gone live — block cancel
        foreach ($bet['selections'] as $sel) {
            $evtStmt = $this->sportDb->prepare("SELECT is_live FROM events WHERE id = ?");
            $evtStmt->execute([$sel['event_id']]);
            $evt = $evtStmt->fetch();
            if ($evt && $evt['is_live']) {
                throw new \RuntimeException('Cannot cancel: one or more events are already live');
            }
        }

        $this->mainDb->beginTransaction();
        try {
            // Refund stake
            $this->wallet->credit(
                $userId,
                (float)$bet['stake'],
                'bet_refund',
                $betId,
                "Bet #{$betId} cancelled by user — refund",
                $userId,
                'refund',
                "Bet #{$betId} cancellation refund"
            );

            // Update bet status
            $this->mainDb->prepare("UPDATE bets SET status = 'cancelled' WHERE id = ?")->execute([$betId]);

            // Update selections
            $this->mainDb->prepare("UPDATE bet_selections SET status = 'cancelled' WHERE bet_id = ?")->execute([$betId]);

            $this->mainDb->commit();
            return $this->getBet($betId);
        } catch (\Throwable $e) {
            $this->mainDb->rollBack();
            throw $e;
        }
    }

    // ═══════════════════════════════════════════
    //  GGR / ADMIN STATS
    // ═══════════════════════════════════════════

    public function getGGR(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = '1=1';
        $params = [];

        if ($dateFrom) {
            $where .= ' AND b.created_at >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $where .= ' AND b.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        $stmt = $this->mainDb->prepare("
            SELECT
                COUNT(*) AS total_bets,
                COALESCE(SUM(b.stake), 0) AS total_stake,
                COALESCE(SUM(CASE WHEN b.status = 'won' THEN b.potential_payout ELSE 0 END), 0) AS total_payout,
                COALESCE(SUM(CASE WHEN b.status = 'pending' THEN b.stake ELSE 0 END), 0) AS pending_stake,
                COALESCE(SUM(CASE WHEN b.cashout_amount IS NOT NULL THEN b.cashout_amount ELSE 0 END), 0) AS total_cashout,
                COUNT(CASE WHEN b.status = 'won' THEN 1 END) AS won_count,
                COUNT(CASE WHEN b.status = 'lost' THEN 1 END) AS lost_count,
                COUNT(CASE WHEN b.status = 'pending' THEN 1 END) AS pending_count,
                COUNT(CASE WHEN b.status = 'cancelled' OR b.status = 'voided' THEN 1 END) AS cancelled_count,
                COUNT(CASE WHEN b.bet_type = 'single' THEN 1 END) AS single_count,
                COUNT(CASE WHEN b.bet_type = 'combo' THEN 1 END) AS combo_count,
                COUNT(CASE WHEN b.bet_type = 'system' THEN 1 END) AS system_count
            FROM bets b
            WHERE {$where}
        ");
        $stmt->execute($params);
        $stats = $stmt->fetch();

        $stats['ggr'] = round((float)$stats['total_stake'] - (float)$stats['total_payout'] - (float)$stats['total_cashout'], 3);
        $stats['margin'] = $stats['total_stake'] > 0
            ? round($stats['ggr'] / (float)$stats['total_stake'] * 100, 2)
            : 0;

        return $stats;
    }

    public function getRecentBets(int $limit = 50): array
    {
        $stmt = $this->mainDb->prepare("
            SELECT b.*, u.username
            FROM bets b
            JOIN users u ON u.id = b.user_id
            ORDER BY b.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $bets = $stmt->fetchAll();

        foreach ($bets as &$bet) {
            $bet['selections'] = $this->getBetSelections((int)$bet['id']);
        }

        return $bets;
    }

    /**
     * Verify current live odds for betslip (frontend calls this to sync)
     */
    public function verifySelections(array $selectionIds): array
    {
        if (empty($selectionIds)) return [];

        $placeholders = implode(',', array_fill(0, count($selectionIds), '?'));
        $stmt = $this->sportDb->prepare("
            SELECT s.id, s.name AS sel_name, s.odds, s.is_active, s.odds_direction,
                   m.name AS market_name, m.status AS market_status,
                   e.id AS event_id, e.name AS event_name, e.home_team, e.away_team,
                   e.status AS event_status, e.is_live
            FROM selections s
            JOIN markets m ON s.market_id = m.id
            JOIN events e ON m.event_id = e.id
            WHERE s.id IN ({$placeholders})
        ");
        $stmt->execute($selectionIds);
        return $stmt->fetchAll();
    }
}
