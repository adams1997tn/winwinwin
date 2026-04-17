<?php
/**
 * Super Admin API — Full platform management with CSRF, wallet, ledger, bets, P&L.
 */
ob_start(); // Buffer any stray output (PHP warnings/notices)
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Prevent HTML error output in JSON responses
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/admin/super_guard.php';

use App\Admin\AgentManager;
use App\Admin\PaymentManager;
use App\Admin\WalletManager;
use App\Core\CsrfProtection;

$agentMgr   = new AgentManager($db);
$walletMgr  = new WalletManager($db);
$paymentMgr = new PaymentManager($db, $walletMgr);
$csrf       = new CsrfProtection();

// ── GET requests ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    ob_clean(); // Flush any stray PHP output before JSON response

    try {
        switch ($action) {
            case 'csrf_token':
                echo json_encode(['success' => true, 'token' => $csrf->getToken()]);
                break;

            case 'global_stats':
                $stats = $agentMgr->getGlobalStats();
                $depositStats = $paymentMgr->getGlobalDepositStats();
                echo json_encode(['success' => true, 'stats' => $stats, 'deposit_stats' => $depositStats]);
                break;

            case 'agents':
                echo json_encode(['success' => true, 'agents' => $agentMgr->getAllAgents()]);
                break;

            case 'users':
                $limit  = min((int)($_GET['limit'] ?? 100), 500);
                $offset = max((int)($_GET['offset'] ?? 0), 0);
                echo json_encode(['success' => true, 'users' => $agentMgr->getAllUsers($limit, $offset)]);
                break;

            case 'deposits':
                $status = $_GET['status'] ?? null;
                if ($status && !in_array($status, ['pending', 'approved', 'rejected'])) $status = null;
                $limit  = min((int)($_GET['limit'] ?? 100), 500);
                $offset = max((int)($_GET['offset'] ?? 0), 0);
                echo json_encode(['success' => true, 'deposits' => $paymentMgr->getAllDepositRequests($status, $limit, $offset)]);
                break;

            case 'deposit_chart':
                $days = min((int)($_GET['days'] ?? 7), 30);
                echo json_encode(['success' => true, 'chart' => $paymentMgr->getDepositChartData($days)]);
                break;

            case 'payment_methods':
                $agentId = (int)($_GET['agent_id'] ?? 0);
                if ($agentId) {
                    echo json_encode(['success' => true, 'methods' => $paymentMgr->getAgentPaymentMethods($agentId)]);
                } else {
                    echo json_encode(['success' => true, 'methods' => $paymentMgr->getActivePaymentMethods()]);
                }
                break;

            // ═══ WALLET LEDGER ═══
            case 'wallet_ledger':
                $userId = (int)($_GET['user_id'] ?? 0);
                $limit  = min((int)($_GET['limit'] ?? 200), 1000);
                $offset = max((int)($_GET['offset'] ?? 0), 0);
                if ($userId) {
                    echo json_encode(['success' => true, 'ledger' => $walletMgr->getUserLedger($userId, $limit, $offset)]);
                } else {
                    echo json_encode(['success' => true, 'ledger' => $walletMgr->getFullLedger($limit, $offset)]);
                }
                break;

            // ═══ WITHDRAWAL REQUESTS ═══
            case 'withdrawals':
                $status = $_GET['status'] ?? null;
                if ($status && !in_array($status, ['pending', 'approved', 'rejected'])) $status = null;
                $limit  = min((int)($_GET['limit'] ?? 100), 500);
                $offset = max((int)($_GET['offset'] ?? 0), 0);
                echo json_encode(['success' => true, 'withdrawals' => $walletMgr->getWithdrawalRequests($status, null, $limit, $offset)]);
                break;

            // ═══ BETS ═══
            case 'bets':
                $status = $_GET['status'] ?? null;
                if ($status && !in_array($status, ['pending', 'won', 'lost', 'void'])) $status = null;
                $limit  = min((int)($_GET['limit'] ?? 100), 500);
                $offset = max((int)($_GET['offset'] ?? 0), 0);
                echo json_encode(['success' => true, 'bets' => $agentMgr->getAllBets($status, $limit, $offset)]);
                break;

            // ═══ P&L ANALYTICS ═══
            case 'pnl_global':
                echo json_encode(['success' => true, 'pnl' => $walletMgr->getGlobalPnL()]);
                break;

            case 'pnl_per_agent':
                echo json_encode(['success' => true, 'agents' => $walletMgr->getPerAgentPnL()]);
                break;

            // ═══ PAYMENT GATEWAYS (all methods across all agents) ═══
            case 'all_payment_methods':
                $stmt = $db->query("
                    SELECT pm.*, u.username AS agent_name, u.agent_code
                    FROM payment_methods pm
                    JOIN users u ON u.id = pm.agent_id
                    ORDER BY pm.display_order ASC, pm.created_at DESC
                ");
                $methods = $stmt->fetchAll();
                foreach ($methods as &$m) {
                    $m['field_schema'] = json_decode($m['field_schema'] ?? '[]', true) ?: [];
                }
                echo json_encode(['success' => true, 'methods' => $methods]);
                break;

            // ═══ USER SEARCH (for wallet management) ═══
            case 'search_users':
                $q = trim($_GET['q'] ?? '');
                if (strlen($q) < 1) {
                    echo json_encode(['success' => true, 'users' => []]);
                    break;
                }
                $stmt = $db->prepare("SELECT id, username, balance, role, banned FROM users WHERE username LIKE ? OR id = ? ORDER BY username ASC LIMIT 20");
                $stmt->execute(['%' . $q . '%', (int)$q]);
                echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (\Throwable $e) {
        ob_clean();
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
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token invalid. Refresh the page.']);
    exit;
}

ob_clean(); // Flush any stray PHP output before JSON response

try {
    switch ($action) {

        // ═══ AGENT CRUD ═══
        case 'create_agent':
            $agent = $agentMgr->createAgent($input, $adminUser['id']);
            echo json_encode(['success' => true, 'agent' => $agent, 'message' => 'Agent created successfully']);
            break;

        case 'update_agent':
            $agentId = (int)($input['agent_id'] ?? 0);
            if (!$agentId) throw new \InvalidArgumentException('Agent ID required');
            $agent = $agentMgr->updateAgent($agentId, $input);
            echo json_encode(['success' => true, 'agent' => $agent, 'message' => 'Agent updated']);
            break;

        case 'toggle_agent_ban':
            $agentId = (int)($input['agent_id'] ?? 0);
            $banned  = (bool)($input['banned'] ?? false);
            $agentMgr->toggleAgentBan($agentId, $banned);
            echo json_encode(['success' => true, 'message' => $banned ? 'Agent banned' : 'Agent unbanned']);
            break;

        case 'set_agent_status':
            $agentId = (int)($input['agent_id'] ?? 0);
            $status  = $input['status'] ?? '';
            $agentMgr->setAgentStatus($agentId, $status);
            echo json_encode(['success' => true, 'message' => "Agent status set to {$status}"]);
            break;

        // ═══ USER MANAGEMENT ═══
        case 'create_user':
            $user = $agentMgr->createUser($input, $adminUser['id']);
            echo json_encode(['success' => true, 'user' => $user, 'message' => 'User created successfully']);
            break;

        case 'change_role':
            $userId  = (int)($input['user_id'] ?? 0);
            $newRole = $input['new_role'] ?? '';
            $agentMgr->changeUserRole($userId, $newRole, $adminUser['id']);
            echo json_encode(['success' => true, 'message' => "Role changed to {$newRole}"]);
            break;

        // ═══ DEPOSIT MANAGEMENT ═══
        case 'approve_deposit':
            $requestId = (int)($input['request_id'] ?? 0);
            $result = $paymentMgr->approveDeposit($requestId, $adminUser['id']);
            echo json_encode(['success' => true, 'deposit' => $result, 'message' => 'Deposit approved + ledger recorded']);
            break;

        case 'reject_deposit':
            $requestId = (int)($input['request_id'] ?? 0);
            $reason    = trim($input['reason'] ?? '');
            $result = $paymentMgr->rejectDeposit($requestId, $adminUser['id'], $reason);
            echo json_encode(['success' => true, 'deposit' => $result, 'message' => 'Deposit rejected']);
            break;

        // ═══ WALLET MANUAL ADJUSTMENTS ═══
        case 'wallet_add':
            $userId = (int)($input['user_id'] ?? 0);
            $amount = (float)($input['amount'] ?? 0);
            $reason = trim($input['reason'] ?? '');
            if (!$userId || $amount <= 0 || !$reason) {
                throw new \InvalidArgumentException('User ID, positive amount, and reason are required');
            }
            $db->beginTransaction();
            try {
                $result = $walletMgr->adminAddFunds($userId, $amount, $reason, $adminUser['id']);
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }
            ob_clean();
            echo json_encode(['success' => true, 'result' => $result, 'message' => "Added €{$amount} to user #{$userId}. Your balance: €{$result['admin_balance']}"]);
            break;

        case 'wallet_remove':
            $userId = (int)($input['user_id'] ?? 0);
            $amount = (float)($input['amount'] ?? 0);
            $reason = trim($input['reason'] ?? '');
            if (!$userId || $amount <= 0 || !$reason) {
                throw new \InvalidArgumentException('User ID, positive amount, and reason are required');
            }
            $db->beginTransaction();
            try {
                $result = $walletMgr->adminRemoveFunds($userId, $amount, $reason, $adminUser['id']);
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }
            ob_clean();
            echo json_encode(['success' => true, 'result' => $result, 'message' => "Removed €{$amount} from user #{$userId}. Your balance: €{$result['admin_balance']}"]);
            break;

        // ═══ WITHDRAWAL MANAGEMENT ═══
        case 'approve_withdrawal':
            $requestId = (int)($input['request_id'] ?? 0);
            $result = $walletMgr->approveWithdrawal($requestId, $adminUser['id']);
            echo json_encode(['success' => true, 'withdrawal' => $result, 'message' => 'Withdrawal approved + balance debited']);
            break;

        case 'reject_withdrawal':
            $requestId = (int)($input['request_id'] ?? 0);
            $reason    = trim($input['reason'] ?? '');
            $result = $walletMgr->rejectWithdrawal($requestId, $adminUser['id'], $reason);
            echo json_encode(['success' => true, 'withdrawal' => $result, 'message' => 'Withdrawal rejected']);
            break;

        // ═══ BET MANAGEMENT ═══
        case 'void_bet':
            $betId = (int)($input['bet_id'] ?? 0);
            if (!$betId) throw new \InvalidArgumentException('Bet ID required');
            $result = $agentMgr->voidBet($betId, $adminUser['id'], $walletMgr);
            echo json_encode(['success' => true, 'bet' => $result, 'message' => 'Bet voided + stake refunded']);
            break;

        // ═══ PAYMENT GATEWAY CRUD (super admin can manage any agent's methods) ═══
        case 'create_gateway':
            $agentId = (int)($input['agent_id'] ?? 0);
            if (!$agentId) throw new \InvalidArgumentException('Agent ID required');
            $method = $paymentMgr->createPaymentMethod($agentId, $input);
            echo json_encode(['success' => true, 'method' => $method, 'message' => 'Payment gateway created']);
            break;

        case 'update_gateway':
            $methodId = (int)($input['method_id'] ?? 0);
            $agentId  = (int)($input['agent_id'] ?? 0);
            if (!$methodId || !$agentId) throw new \InvalidArgumentException('Method ID and Agent ID required');
            $method = $paymentMgr->updatePaymentMethod($methodId, $agentId, $input);
            echo json_encode(['success' => true, 'method' => $method, 'message' => 'Payment gateway updated']);
            break;

        case 'delete_gateway':
            $methodId = (int)($input['method_id'] ?? 0);
            $agentId  = (int)($input['agent_id'] ?? 0);
            if (!$methodId || !$agentId) throw new \InvalidArgumentException('Method ID and Agent ID required');
            $paymentMgr->deletePaymentMethod($methodId, $agentId);
            echo json_encode(['success' => true, 'message' => 'Payment gateway deactivated']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (\InvalidArgumentException $e) {
    ob_clean();
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\RuntimeException $e) {
    ob_clean();
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
