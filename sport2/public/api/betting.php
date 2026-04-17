<?php
/**
 * Sport2 — Betting API Endpoint
 * Handles: place_bet, my_bets, cashout, cancel_bet, verify_selections, bet_settings, ggr
 */

// Boot both frameworks
require_once dirname(__DIR__, 2) . '/bootstrap.php';        // Sport2 (sport2 DB)

// Parent app bootstrap — guard against constant redefinition
$parentBoot = dirname(__DIR__, 3) . '/bootstrap.php';
if (!defined('BASE_PATH')) {
    require_once $parentBoot;
} else {
    // Autoloader and config already loaded, just ensure $config is available
    if (!isset($config)) {
        $config = require dirname(__DIR__, 3) . '/config/config.php';
    }
}

use Sport2\Core\Database as Sport2Db;
use App\Core\Database as MainDb;
use Sport2\Betting\BettingManager;
use Sport2\Betting\CashoutManager;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = $_GET['action'] ?? '';

try {
    $sportDb = Sport2Db::get();
    $mainDb  = MainDb::getInstance($config['db'])->getPdo();

    $betting  = new BettingManager($sportDb, $mainDb);
    $cashout  = new CashoutManager($sportDb, $mainDb);

    // Auth check helper
    $requireAuth = function() {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Please log in to continue']);
            exit;
        }
        return (int)$_SESSION['user_id'];
    };

    switch ($action) {

        // ─── PLACE BET ───────────────────────────────────
        case 'place_bet':
            $userId = $requireAuth();
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                throw new \InvalidArgumentException('Invalid request body');
            }

            $betType    = $input['bet_type'] ?? 'single';
            $stake      = (float)($input['stake'] ?? 0);
            $selections = $input['selections'] ?? [];

            // Validate selections structure
            foreach ($selections as &$sel) {
                if (empty($sel['selection_id']) || !isset($sel['odds'])) {
                    throw new \InvalidArgumentException('Each selection must have selection_id and odds');
                }
                $sel['selection_id'] = trim((string)$sel['selection_id']);
                $sel['odds'] = (float)$sel['odds'];
            }
            unset($sel);

            $bet = $betting->placeBet($userId, $betType, $stake, $selections);

            // Update session balance
            $balStmt = $mainDb->prepare("SELECT balance FROM users WHERE id = ?");
            $balStmt->execute([$userId]);
            $_SESSION['balance'] = (float)$balStmt->fetchColumn();

            echo json_encode([
                'ok'      => true,
                'bet'     => $bet,
                'balance' => $_SESSION['balance'],
            ]);
            break;

        // ─── MY BETS ────────────────────────────────────
        case 'my_bets':
            $userId = $requireAuth();
            $filter = $_GET['filter'] ?? 'all';
            $limit  = min((int)($_GET['limit'] ?? 50), 100);
            $offset = max((int)($_GET['offset'] ?? 0), 0);

            $result = $betting->getUserBets($userId, $filter, $limit, $offset);

            echo json_encode(['ok' => true, ...$result]);
            break;

        // ─── SINGLE BET DETAIL ──────────────────────────
        case 'bet_detail':
            $userId = $requireAuth();
            $betId  = (int)($_GET['bet_id'] ?? 0);

            $bet = $betting->getBet($betId);
            if (!$bet || (int)$bet['user_id'] !== $userId) {
                throw new \RuntimeException('Bet not found');
            }

            // Attach cashout info
            $bet['cashout'] = $cashout->calculateCashout($betId);

            echo json_encode(['ok' => true, 'bet' => $bet]);
            break;

        // ─── CANCEL BET ─────────────────────────────────
        case 'cancel_bet':
            $userId = $requireAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            $betId = (int)($input['bet_id'] ?? 0);

            $bet = $betting->cancelBet($betId, $userId);

            $balStmt = $mainDb->prepare("SELECT balance FROM users WHERE id = ?");
            $balStmt->execute([$userId]);
            $_SESSION['balance'] = (float)$balStmt->fetchColumn();

            echo json_encode([
                'ok'      => true,
                'bet'     => $bet,
                'balance' => $_SESSION['balance'],
            ]);
            break;

        // ─── CASHOUT ────────────────────────────────────
        case 'cashout_check':
            $userId = $requireAuth();
            $betId  = (int)($_GET['bet_id'] ?? 0);

            $bet = $betting->getBet($betId);
            if (!$bet || (int)$bet['user_id'] !== $userId) {
                throw new \RuntimeException('Bet not found');
            }

            $result = $cashout->calculateCashout($betId);
            echo json_encode(['ok' => true, 'cashout' => $result]);
            break;

        case 'cashout_execute':
            $userId = $requireAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            $betId = (int)($input['bet_id'] ?? 0);

            $bet = $cashout->executeCashout($betId, $userId);

            $balStmt = $mainDb->prepare("SELECT balance FROM users WHERE id = ?");
            $balStmt->execute([$userId]);
            $_SESSION['balance'] = (float)$balStmt->fetchColumn();

            echo json_encode([
                'ok'      => true,
                'bet'     => $bet,
                'balance' => $_SESSION['balance'],
            ]);
            break;

        // ─── VERIFY SELECTIONS (live odds sync) ─────────
        case 'verify_selections':
            $ids = $_GET['ids'] ?? '';
            $selectionIds = array_filter(array_map('trim', explode(',', $ids)));

            if (empty($selectionIds)) {
                echo json_encode(['ok' => true, 'data' => []]);
                break;
            }

            $data = $betting->verifySelections($selectionIds);
            echo json_encode(['ok' => true, 'data' => $data]);
            break;

        // ─── BET SETTINGS (public read) ──────────────────
        case 'bet_settings':
            echo json_encode(['ok' => true, 'settings' => $betting->getSettings()]);
            break;

        // ─── UPDATE SETTINGS (admin only) ────────────────
        case 'update_settings':
            $userId = $requireAuth();
            $stmt = $mainDb->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $role = $stmt->fetchColumn();
            if (!in_array($role, ['admin', 'super_admin'])) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Admin access required']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            foreach ($input as $key => $value) {
                $betting->updateSetting($key, (string)$value);
            }

            echo json_encode(['ok' => true, 'settings' => $betting->getSettings()]);
            break;

        // ─── GGR STATS (admin) ──────────────────────────
        case 'ggr':
            $userId = $requireAuth();
            $stmt = $mainDb->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $role = $stmt->fetchColumn();
            if (!in_array($role, ['admin', 'super_admin'])) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Admin access required']);
                exit;
            }

            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo   = $_GET['date_to'] ?? null;
            $stats = $betting->getGGR($dateFrom, $dateTo);
            $recent = $betting->getRecentBets(30);

            echo json_encode(['ok' => true, 'stats' => $stats, 'recent_bets' => $recent]);
            break;

        // ─── RECENT BETS (admin) ────────────────────────
        case 'admin_bets':
            $userId = $requireAuth();
            $stmt = $mainDb->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $role = $stmt->fetchColumn();
            if (!in_array($role, ['admin', 'super_admin'])) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Admin access required']);
                exit;
            }

            $limit = min((int)($_GET['limit'] ?? 50), 200);
            $bets = $betting->getRecentBets($limit);
            echo json_encode(['ok' => true, 'bets' => $bets]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid action. Available: place_bet, my_bets, bet_detail, cancel_bet, cashout_check, cashout_execute, verify_selections, bet_settings, update_settings, ggr, admin_bets']);
    }

} catch (\InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (\RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
