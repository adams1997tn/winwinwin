<?php
/**
 * POST /api/place_bet.php
 *
 * Receives: { selections: [...], stake: float }
 * Each selection: { match_id, market_id, outcome_key, odds }
 *
 * Validates balance, verifies odds haven't drifted >10%, inserts bet, deducts balance.
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Must be logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to place a bet']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new \InvalidArgumentException('Invalid JSON body');
    }

    $selections = $input['selections'] ?? [];
    $stake      = isset($input['stake']) ? (float)$input['stake'] : 0;

    // ── Validation ──
    if (empty($selections)) {
        throw new \InvalidArgumentException('No selections provided');
    }
    if ($stake < 0.50) {
        throw new \InvalidArgumentException('Minimum stake is 0.50');
    }
    if ($stake > 10000) {
        throw new \InvalidArgumentException('Maximum stake is 10,000');
    }
    if (count($selections) > 20) {
        throw new \InvalidArgumentException('Maximum 20 selections per bet');
    }

    // Prevent duplicate matches
    $matchIds = array_column($selections, 'match_id');
    if (count($matchIds) !== count(array_unique($matchIds))) {
        throw new \InvalidArgumentException('Cannot add the same match twice');
    }

    $db     = Database::getInstance($config['db'])->getPdo();
    $userId = (int)$_SESSION['user_id'];

    $db->beginTransaction();

    // ── Lock user row and check balance ──
    $userStmt = $db->prepare('SELECT id, balance, banned FROM users WHERE id = ? FOR UPDATE');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        throw new \RuntimeException('User not found');
    }
    if ($user['banned']) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => 'Your account has been suspended']);
        exit;
    }
    if ((float)$user['balance'] < $stake) {
        $db->rollBack();
        echo json_encode([
            'success' => false,
            'error'   => 'Insufficient balance. You have ' . number_format($user['balance'], 2) . '€',
        ]);
        exit;
    }

    // ── Verify each selection's odds against database ──
    $totalOdds    = 1.0;
    $verifiedSels = [];
    $maxDrift     = 0.10; // 10% max drift allowed

    foreach ($selections as $sel) {
        $matchId    = (int)($sel['match_id'] ?? 0);
        $marketId   = (int)($sel['market_id'] ?? 0);
        $outcomeKey = $sel['outcome_key'] ?? '';
        $clientOdds = (float)($sel['odds'] ?? 0);

        if (!$matchId || !$marketId || !$outcomeKey || $clientOdds <= 1) {
            throw new \InvalidArgumentException('Invalid selection data');
        }

        // Verify match is still active and upcoming
        $matchCheck = $db->prepare('SELECT id, home_team, away_team FROM `matches` WHERE id = ? AND active = 1 AND start_time > NOW()');
        $matchCheck->execute([$matchId]);
        $match = $matchCheck->fetch();

        if (!$match) {
            throw new \RuntimeException("Match #{$matchId} is no longer available");
        }

        // Verify odds exist and haven't drifted too much
        $oddsCheck = $db->prepare(
            'SELECT value FROM odds WHERE match_id = ? AND market_id = ? AND outcome_key = ? AND value IS NOT NULL'
        );
        $oddsCheck->execute([$matchId, $marketId, $outcomeKey]);
        $dbOdds = $oddsCheck->fetchColumn();

        if ($dbOdds === false) {
            throw new \RuntimeException("Odds no longer available for {$match['home_team']} vs {$match['away_team']}");
        }

        $dbOdds = (float)$dbOdds;
        $drift  = abs($dbOdds - $clientOdds) / $dbOdds;

        if ($drift > $maxDrift) {
            $db->rollBack();
            echo json_encode([
                'success'    => false,
                'error'      => "Odds changed for {$match['home_team']} vs {$match['away_team']}. Was {$clientOdds}, now {$dbOdds}. Please refresh.",
                'odds_changed' => true,
            ]);
            exit;
        }

        // Use current DB odds (most accurate)
        $totalOdds *= $dbOdds;

        $verifiedSels[] = [
            'match_id'    => $matchId,
            'market_id'   => $marketId,
            'outcome_key' => $outcomeKey,
            'odds'        => $dbOdds,
            'home_team'   => $match['home_team'],
            'away_team'   => $match['away_team'],
        ];
    }

    $totalOdds      = round($totalOdds, 4);
    $potentialPayout = round($stake * $totalOdds, 2);
    $transactionId   = strtoupper('BET-' . date('Ymd') . '-' . bin2hex(random_bytes(4)));

    // ── Insert bet ──
    $betStmt = $db->prepare(
        'INSERT INTO bets (user_id, selections_json, total_odds, stake, potential_payout, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $betStmt->execute([
        $userId,
        json_encode($verifiedSels),
        $totalOdds,
        $stake,
        $potentialPayout,
        'pending',
    ]);
    $betId = (int)$db->lastInsertId();

    // ── Deduct balance ──
    $deductStmt = $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ?');
    $deductStmt->execute([$stake, $userId]);

    // Fetch new balance
    $balStmt = $db->prepare('SELECT balance FROM users WHERE id = ?');
    $balStmt->execute([$userId]);
    $newBalance = (float)$balStmt->fetchColumn();

    // ── Log transaction ──
    $txStmt = $db->prepare(
        'INSERT INTO transactions (user_id, type, amount, balance_after, reference_id, description) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $txStmt->execute([
        $userId,
        'bet_placement',
        -$stake,
        $newBalance,
        $betId,
        "Bet #{$betId} placed — {$transactionId}",
    ]);

    $db->commit();

    // Update session balance
    $_SESSION['balance'] = $newBalance;

    echo json_encode([
        'success'         => true,
        'transaction_id'  => $transactionId,
        'bet_id'          => $betId,
        'total_odds'      => $totalOdds,
        'stake'           => $stake,
        'potential_payout' => $potentialPayout,
        'new_balance'     => $newBalance,
        'message'         => "Bet placed successfully! Transaction: {$transactionId}",
    ]);

} catch (\InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred while placing your bet']);
}
