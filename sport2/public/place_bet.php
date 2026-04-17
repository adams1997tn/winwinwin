<?php
/**
 * place_bet.php — Server-side Bet Placement
 *
 * Receives POST from the BetSlip.
 * 1. Validates session (user must be logged in)
 * 2. Re-fetches CURRENT odds from sport2.selections (prevents stale-odds exploits)
 * 3. Verifies user balance from sportsbook.users (prevents double-spend)
 * 4. Delegates to BettingManager::placeBet() for atomic wallet debit + bet insert
 * 5. Returns JSON response
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Boot both frameworks
require_once dirname(__DIR__) . '/bootstrap.php';
$parentBoot = dirname(__DIR__, 2) . '/bootstrap.php';
if (!defined('BASE_PATH')) {
    require_once $parentBoot;
} else {
    if (!isset($config)) {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
    }
}

use Sport2\Core\Database as Sport2Db;
use App\Core\Database as MainDb;
use Sport2\Betting\BettingManager;

try {
    // ─── 1. AUTH CHECK ──────────────────────────────────
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Please log in to place a bet']);
        exit;
    }
    $userId = (int)$_SESSION['user_id'];

    // ─── 2. PARSE INPUT ─────────────────────────────────
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !is_array($input)) {
        throw new \InvalidArgumentException('Invalid request body');
    }

    $betType    = $input['bet_type'] ?? 'single';
    $stake      = (float)($input['stake'] ?? 0);
    $selections = $input['selections'] ?? [];

    if (!in_array($betType, ['single', 'combo', 'system'], true)) {
        throw new \InvalidArgumentException('Invalid bet type');
    }
    if ($stake <= 0) {
        throw new \InvalidArgumentException('Stake must be positive');
    }
    if (empty($selections) || !is_array($selections)) {
        throw new \InvalidArgumentException('No selections provided');
    }

    // Sanitize selections (keep as string — sport2 uses VARCHAR IDs)
    foreach ($selections as &$sel) {
        if (empty($sel['selection_id']) || !isset($sel['odds'])) {
            throw new \InvalidArgumentException('Each selection must have selection_id and odds');
        }
        $sel['selection_id'] = trim((string)$sel['selection_id']);
        $sel['odds'] = (float)$sel['odds'];
    }
    unset($sel);

    // ─── 3. CONNECT TO BOTH DATABASES ───────────────────
    $sportDb = Sport2Db::get();
    $mainDb  = MainDb::getInstance($config['db'])->getPdo();

    // ─── 4. VERIFY USER BALANCE (fresh from DB) ─────────
    $balStmt = $mainDb->prepare("SELECT balance FROM users WHERE id = ? AND banned = 0");
    $balStmt->execute([$userId]);
    $currentBalance = $balStmt->fetchColumn();

    if ($currentBalance === false) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Account not found or suspended']);
        exit;
    }

    $currentBalance = (float)$currentBalance;
    if ($currentBalance < $stake) {
        echo json_encode([
            'ok'      => false,
            'error'   => 'Insufficient balance',
            'balance' => $currentBalance,
        ]);
        exit;
    }

    // ─── 5. VERIFY CURRENT ODDS FROM DB ─────────────────
    // Re-fetch live odds to prevent stale-odds exploits
    $selIds = array_column($selections, 'selection_id');
    $ph = implode(',', array_fill(0, count($selIds), '?'));

    $oddsStmt = $sportDb->prepare("
        SELECT s.id, s.odds, s.is_active,
               m.status AS market_status,
               e.status AS event_status, e.is_live
        FROM selections s
        JOIN markets m ON s.market_id = m.id
        JOIN events e ON m.event_id = e.id
        WHERE s.id IN ({$ph})
    ");
    $oddsStmt->execute($selIds);
    $liveOdds = $oddsStmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    // Check each selection is still valid
    $updatedSelections = [];
    $oddsChanged = false;

    foreach ($selections as $sel) {
        $selId = $sel['selection_id'];
        $current = $liveOdds[$selId] ?? null;

        if (!$current) {
            throw new \InvalidArgumentException("Selection '{$selId}' no longer exists");
        }
        if (!$current['is_active']) {
            throw new \InvalidArgumentException("Selection '{$selId}' is suspended");
        }
        if ($current['market_status'] != 1) {
            throw new \InvalidArgumentException("Market for selection '{$selId}' is suspended");
        }
        if ($current['event_status'] != 1) {
            throw new \InvalidArgumentException("Event for selection '{$selId}' is no longer active");
        }

        $currentOdds = (float)$current['odds'];
        $submittedOdds = $sel['odds'];

        // Detect odds change (use current DB odds, not submitted)
        if (abs($currentOdds - $submittedOdds) > 0.01) {
            $oddsChanged = true;
        }

        $updatedSelections[] = [
            'selection_id' => $selId,
            'odds'         => $currentOdds, // Always use CURRENT DB odds
        ];
    }

    // ─── 6. PLACE BET (atomic via BettingManager) ───────
    $betting = new BettingManager($sportDb, $mainDb);
    $bet = $betting->placeBet($userId, $betType, $stake, $updatedSelections);

    // Refresh balance
    $balStmt->execute([$userId]);
    $newBalance = (float)$balStmt->fetchColumn();
    $_SESSION['balance'] = $newBalance;

    // ─── 7. RESPONSE ────────────────────────────────────
    echo json_encode([
        'ok'           => true,
        'bet'          => $bet,
        'balance'      => $newBalance,
        'odds_changed' => $oddsChanged,
        'message'      => $oddsChanged
            ? 'Bet placed with updated odds (odds changed since selection)'
            : 'Bet placed successfully',
    ]);

} catch (\InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (\RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
