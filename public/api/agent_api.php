<?php
/**
 * Agent API — Scoped management: payment methods, deposits, users, bets, withdrawals.
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/agent/guard.php';

use App\Admin\PaymentManager;
use App\Admin\AgentManager;
use App\Admin\WalletManager;
use App\Core\CsrfProtection;

$walletMgr  = new WalletManager($db);
$paymentMgr = new PaymentManager($db, $walletMgr);
$agentMgr   = new AgentManager($db);
$csrf       = new CsrfProtection();
$agentId    = $agentUser['id'];

// ── GET requests ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    try {
        switch ($action) {
            case 'csrf_token':
                echo json_encode(['success' => true, 'token' => $csrf->getToken()]);
                break;

            case 'stats':
                $stats = $paymentMgr->getAgentDepositStats($agentId);
                $scopedStats = $agentMgr->getAgentScopedStats($agentId);
                echo json_encode(['success' => true, 'stats' => $stats, 'scoped_stats' => $scopedStats]);
                break;

            case 'payment_methods':
                echo json_encode(['success' => true, 'methods' => $paymentMgr->getAgentPaymentMethods($agentId)]);
                break;

            case 'deposits':
                $status = $_GET['status'] ?? null;
                if ($status && !in_array($status, ['pending', 'approved', 'rejected'])) $status = null;
                $limit  = min((int)($_GET['limit'] ?? 50), 200);
                $offset = max((int)($_GET['offset'] ?? 0), 0);
                echo json_encode([
                    'success'  => true,
                    'deposits' => $paymentMgr->getAgentDepositRequests($agentId, $status, $limit, $offset)
                ]);
                break;

            case 'deposit_chart':
                $days = min((int)($_GET['days'] ?? 7), 30);
                echo json_encode(['success' => true, 'chart' => $paymentMgr->getDepositChartData($days, $agentId)]);
                break;

            // ═══ SCOPED USERS ═══
            case 'my_users':
                $limit  = min((int)($_GET['limit'] ?? 100), 500);
                $offset = max((int)($_GET['offset'] ?? 0), 0);
                echo json_encode(['success' => true, 'users' => $agentMgr->getAgentUsers($agentId, $limit, $offset)]);
                break;

            // ═══ SCOPED BETS ═══
            case 'my_bets':
                $status = $_GET['status'] ?? null;
                if ($status && !in_array($status, ['pending', 'won', 'lost', 'void'])) $status = null;
                $limit  = min((int)($_GET['limit'] ?? 100), 500);
                $offset = max((int)($_GET['offset'] ?? 0), 0);
                echo json_encode(['success' => true, 'bets' => $agentMgr->getAgentBets($agentId, $status, $limit, $offset)]);
                break;

            // ═══ MY WITHDRAWALS ═══
            case 'my_withdrawals':
                $status = $_GET['status'] ?? null;
                if ($status && !in_array($status, ['pending', 'approved', 'rejected'])) $status = null;
                echo json_encode(['success' => true, 'withdrawals' => $walletMgr->getWithdrawalRequests($status, $agentId)]);
                break;

            // ═══ MY WALLET LEDGER ═══
            case 'my_ledger':
                $limit  = min((int)($_GET['limit'] ?? 100), 500);
                $offset = max((int)($_GET['offset'] ?? 0), 0);
                echo json_encode(['success' => true, 'ledger' => $walletMgr->getUserLedger($agentId, $limit, $offset)]);
                break;

            // ═══ MY P&L ═══
            case 'my_pnl':
                echo json_encode(['success' => true, 'pnl' => $walletMgr->getAgentPnL($agentId)]);
                break;

            // ═══ MY BALANCE ═══
            case 'my_balance':
                echo json_encode(['success' => true, 'balance' => $walletMgr->getBalance($agentId)]);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── POST requests (CSRF validated) ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    $csrf->validate($input['_csrf_token'] ?? null);
} catch (\RuntimeException $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token invalid. Refresh the page.']);
    exit;
}

try {
    switch ($action) {

        // ═══ PAYMENT METHOD CRUD ═══
        case 'create_method':
            $method = $paymentMgr->createPaymentMethod($agentId, $input);
            echo json_encode(['success' => true, 'method' => $method, 'message' => 'Payment method created']);
            break;

        case 'update_method':
            $methodId = (int)($input['method_id'] ?? 0);
            if (!$methodId) throw new \InvalidArgumentException('Method ID required');
            $method = $paymentMgr->updatePaymentMethod($methodId, $agentId, $input);
            echo json_encode(['success' => true, 'method' => $method, 'message' => 'Payment method updated']);
            break;

        case 'delete_method':
            $methodId = (int)($input['method_id'] ?? 0);
            $paymentMgr->deletePaymentMethod($methodId, $agentId);
            echo json_encode(['success' => true, 'message' => 'Payment method deactivated']);
            break;

        // ═══ DEPOSIT MANAGEMENT ═══
        case 'approve_deposit':
            $requestId = (int)($input['request_id'] ?? 0);
            $request = $paymentMgr->getDepositRequest($requestId);
            if (!$request || (int)$request['agent_id'] !== $agentId) {
                throw new \RuntimeException('Deposit request not found');
            }
            $result = $paymentMgr->approveDeposit($requestId, $agentId);
            echo json_encode(['success' => true, 'deposit' => $result, 'message' => 'Deposit approved + ledger recorded']);
            break;

        case 'reject_deposit':
            $requestId = (int)($input['request_id'] ?? 0);
            $reason    = trim($input['reason'] ?? '');
            $request = $paymentMgr->getDepositRequest($requestId);
            if (!$request || (int)$request['agent_id'] !== $agentId) {
                throw new \RuntimeException('Deposit request not found');
            }
            $result = $paymentMgr->rejectDeposit($requestId, $agentId, $reason);
            echo json_encode(['success' => true, 'deposit' => $result, 'message' => 'Deposit rejected']);
            break;

        // ═══ WITHDRAWAL REQUEST ═══
        case 'request_withdrawal':
            $amount         = (float)($input['amount'] ?? 0);
            $method         = trim($input['method'] ?? '');
            $accountDetails = trim($input['account_details'] ?? '');
            $note           = trim($input['note'] ?? '');
            if (!$method || !$accountDetails) {
                throw new \InvalidArgumentException('Method and account details are required');
            }
            $result = $walletMgr->createWithdrawalRequest($agentId, $amount, $method, $accountDetails, $note);
            echo json_encode(['success' => true, 'withdrawal' => $result, 'message' => 'Withdrawal request submitted']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (\InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\RuntimeException $e) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
