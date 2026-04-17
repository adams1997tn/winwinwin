<?php
/**
 * Auth API — login, register (with agent_code tracking), logout, me
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;
use App\Core\CsrfProtection;

$action = $_GET['action'] ?? '';

try {
    $db   = Database::getInstance($config['db'])->getPdo();
    $csrf = new CsrfProtection();

    switch ($action) {

        case 'login':
            $input    = json_decode(file_get_contents('php://input'), true);
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';

            if (!$username || !$password) {
                throw new \InvalidArgumentException('Username and password are required');
            }

            $stmt = $db->prepare('SELECT id, username, password_hash, balance, role, banned FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
                exit;
            }

            if ($user['banned']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Account suspended']);
                exit;
            }

            // Regenerate session + CSRF on login
            session_regenerate_id(true);
            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['balance']  = (float)$user['balance'];
            $csrf->regenerate();

            echo json_encode([
                'success'  => true,
                'user'     => [
                    'id'       => (int)$user['id'],
                    'username' => $user['username'],
                    'balance'  => (float)$user['balance'],
                    'role'     => $user['role'],
                ],
            ]);
            break;

        case 'register':
            $input     = json_decode(file_get_contents('php://input'), true);
            $username  = trim($input['username'] ?? '');
            $password  = $input['password'] ?? '';
            $agentCode = trim($input['agent_code'] ?? '');

            if (strlen($username) < 3 || strlen($username) > 50) {
                throw new \InvalidArgumentException('Username must be 3-50 characters');
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                throw new \InvalidArgumentException('Username can only contain letters, numbers, underscores');
            }
            if (strlen($password) < 6) {
                throw new \InvalidArgumentException('Password must be at least 6 characters');
            }

            $check = $db->prepare('SELECT id FROM users WHERE username = ?');
            $check->execute([$username]);
            if ($check->fetch()) {
                throw new \InvalidArgumentException('Username already taken');
            }

            // Resolve agent from agent_code
            $referredByAgent = null;
            if ($agentCode !== '') {
                $agentStmt = $db->prepare("SELECT id FROM users WHERE agent_code = ? AND role = 'agent' AND agent_status = 'active' AND banned = 0");
                $agentStmt->execute([$agentCode]);
                $agentRow = $agentStmt->fetch();
                if (!$agentRow) {
                    throw new \InvalidArgumentException('Invalid agent code');
                }
                $referredByAgent = (int)$agentRow['id'];
            }

            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare('INSERT INTO users (username, password_hash, balance, referred_by_agent) VALUES (?, ?, 1000.000, ?)');
            $stmt->execute([$username, $hash, $referredByAgent]);
            $newId = (int)$db->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['user_id']  = $newId;
            $_SESSION['username'] = $username;
            $_SESSION['balance']  = 1000.00;
            $csrf->regenerate();

            echo json_encode([
                'success' => true,
                'user'    => [
                    'id'       => $newId,
                    'username' => $username,
                    'balance'  => 1000.00,
                ],
            ]);
            break;

        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;

        case 'me':
            if (empty($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'user' => null]);
                exit;
            }
            $stmt = $db->prepare('SELECT id, username, balance, role FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['balance'] = (float)$user['balance'];
                echo json_encode([
                    'success' => true,
                    'user'    => [
                        'id'       => (int)$user['id'],
                        'username' => $user['username'],
                        'balance'  => (float)$user['balance'],
                        'role'     => $user['role'],
                    ],
                ]);
            } else {
                session_destroy();
                echo json_encode(['success' => false, 'user' => null]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (\InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
