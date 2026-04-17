<?php
/**
 * Admin API — Handles admin actions (balance edit, ban, settlement, logs)
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/admin/guard.php';

use App\Core\Database;

$db = Database::getInstance($config['db'])->getPdo();

// ── GET requests ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'settlement_logs':
            $stmt = $db->query('SELECT * FROM settlement_log ORDER BY created_at DESC LIMIT 20');
            echo json_encode(['logs' => $stmt->fetchAll()]);
            break;

        case 'pending_bets':
            $stmt = $db->query("
                SELECT b.id, u.username, b.stake, b.total_odds, b.potential_payout, b.created_at
                FROM bets b JOIN users u ON u.id = b.user_id
                WHERE b.status = 'pending'
                ORDER BY b.created_at DESC
                LIMIT 50
            ");
            echo json_encode(['bets' => $stmt->fetchAll()]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

// ── POST requests ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {

        // ═══ EDIT BALANCE ═══
        case 'edit_balance':
            $userId    = (int)($input['user_id'] ?? 0);
            $newBalance = isset($input['new_balance']) ? round((float)$input['new_balance'], 2) : null;
            $reason    = trim($input['reason'] ?? 'Manual adjustment');

            if (!$userId || $newBalance === null || $newBalance < 0) {
                throw new \InvalidArgumentException('Invalid parameters');
            }

            // Can't edit admin balance through this endpoint
            $check = $db->prepare('SELECT id, balance, role FROM users WHERE id = ?');
            $check->execute([$userId]);
            $target = $check->fetch();
            if (!$target) throw new \RuntimeException('User not found');
            if ($target['role'] === 'admin') throw new \RuntimeException('Cannot edit admin balance here');

            $oldBalance = (float)$target['balance'];
            $diff = $newBalance - $oldBalance;

            $db->beginTransaction();

            $db->prepare('UPDATE users SET balance = ? WHERE id = ?')->execute([$newBalance, $userId]);

            // Log the transaction
            $db->prepare(
                'INSERT INTO transactions (user_id, type, amount, balance_after, description, created_by) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $userId,
                'manual_adjustment',
                $diff,
                $newBalance,
                $reason,
                $adminUser['id'],
            ]);

            $db->commit();

            echo json_encode([
                'success'     => true,
                'new_balance' => $newBalance,
                'message'     => "Balance updated: €{$oldBalance} → €{$newBalance}",
            ]);
            break;

        // ═══ BAN / UNBAN ═══
        case 'toggle_ban':
            $userId = (int)($input['user_id'] ?? 0);
            $banned = (int)($input['banned'] ?? 0);

            if (!$userId) throw new \InvalidArgumentException('Invalid user ID');

            $check = $db->prepare('SELECT role FROM users WHERE id = ?');
            $check->execute([$userId]);
            $target = $check->fetch();
            if (!$target) throw new \RuntimeException('User not found');
            if ($target['role'] === 'admin') throw new \RuntimeException('Cannot ban an admin');

            $db->prepare('UPDATE users SET banned = ? WHERE id = ?')->execute([$banned ? 1 : 0, $userId]);

            echo json_encode([
                'success' => true,
                'message' => $banned ? 'User banned' : 'User unbanned',
            ]);
            break;

        // ═══ RUN SETTLEMENT ═══
        case 'settle':
            require_once dirname(__DIR__, 2) . '/src/Settlement/SettlementEngine.php';

            $engine = new \App\Settlement\SettlementEngine($db, $config);
            $result = $engine->run();

            echo json_encode([
                'success' => true,
                'result'  => $result,
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (\InvalidArgumentException $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
