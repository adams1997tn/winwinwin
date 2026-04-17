<?php
/**
 * Agent Guard — only agent role can access.
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;

if (empty($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    header('Location: /bet/public/index.php');
    exit;
}

$db = Database::getInstance($config['db'])->getPdo();
$stmt = $db->prepare('SELECT role, banned, agent_status FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'agent' || $user['banned'] || $user['agent_status'] !== 'active') {
    http_response_code(403);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    echo '<!DOCTYPE html><html><body style="background:#0d0f14;color:#ef4444;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><h1>403 — Agent Access Only</h1></body></html>';
    exit;
}

$agentUser = [
    'id'           => (int)$_SESSION['user_id'],
    'username'     => $_SESSION['username'],
    'role'         => $user['role'],
    'agent_status' => $user['agent_status'],
];
