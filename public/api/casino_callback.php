<?php
/**
 * Casino Callback Handler — Unified Wallet API
 *
 * Bet4Wins sends POST requests here for:
 *   action=balance  → Return player balance
 *   action=debit    → Deduct bet amount
 *   action=credit   → Add win amount
 *   action=rollback → Reverse a failed transaction
 *
 * Every request is validated via SHA256 hash before processing.
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Casino\CasinoManager;

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error_code' => 'METHOD_NOT_ALLOWED']);
    exit;
}

$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody, true);

if (!$input || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error_code' => 'INVALID_REQUEST', 'message' => 'Invalid JSON body']);
    exit;
}

$action       = $input['action'] ?? '';
$playerId     = isset($input['player_id']) ? (int) $input['player_id'] : 0;
$receivedHash = $input['hash'] ?? '';

if (!$action || !$playerId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error_code' => 'MISSING_PARAMS', 'message' => 'action and player_id are required']);
    exit;
}

try {
    $casino = new CasinoManager($config);

    // ── Validate hash for all actions ──
    if (!$casino->validateHash($input, $receivedHash)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'error_code' => 'INVALID_HASH', 'message' => 'Hash validation failed']);
        exit;
    }

    switch ($action) {

        case 'balance':
            $result = $casino->getBalance($playerId);
            break;

        case 'debit':
            $transactionId = $input['transaction_id'] ?? '';
            $amount        = isset($input['amount']) ? (float) $input['amount'] : 0;
            $roundId       = $input['round_id'] ?? null;
            $gameId        = $input['game_id'] ?? null;

            if (!$transactionId || $amount <= 0) {
                echo json_encode(['status' => 'error', 'error_code' => 'INVALID_PARAMS', 'message' => 'transaction_id and positive amount required']);
                exit;
            }

            $result = $casino->debit($playerId, $transactionId, $amount, $roundId, $gameId, $input);
            break;

        case 'credit':
            $transactionId = $input['transaction_id'] ?? '';
            $amount        = isset($input['amount']) ? (float) $input['amount'] : 0;
            $roundId       = $input['round_id'] ?? null;
            $gameId        = $input['game_id'] ?? null;

            if (!$transactionId || $amount < 0) {
                echo json_encode(['status' => 'error', 'error_code' => 'INVALID_PARAMS', 'message' => 'transaction_id and non-negative amount required']);
                exit;
            }

            $result = $casino->credit($playerId, $transactionId, $amount, $roundId, $gameId, $input);
            break;

        case 'rollback':
            $transactionId         = $input['transaction_id'] ?? '';
            $originalTransactionId = $input['original_transaction_id'] ?? '';
            $roundId               = $input['round_id'] ?? null;
            $gameId                = $input['game_id'] ?? null;

            if (!$transactionId || !$originalTransactionId) {
                echo json_encode(['status' => 'error', 'error_code' => 'INVALID_PARAMS', 'message' => 'transaction_id and original_transaction_id required']);
                exit;
            }

            $result = $casino->rollback($playerId, $transactionId, $originalTransactionId, $roundId, $gameId, $input);
            break;

        default:
            http_response_code(400);
            $result = ['status' => 'error', 'error_code' => 'UNKNOWN_ACTION', 'message' => "Unknown action: {$action}"];
            break;
    }

    echo json_encode($result);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error_code' => 'INTERNAL_ERROR', 'message' => 'Internal server error']);
}
