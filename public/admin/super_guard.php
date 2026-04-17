<?php
/**
 * Super Admin Guard — only super_admin role can access.
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
$stmt = $db->prepare('SELECT role, banned, balance FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'super_admin' || $user['banned']) {
    http_response_code(403);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    echo '<!DOCTYPE html><html><body style="background:#0d0f14;color:#ef4444;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><h1>403 — Super Admin Access Only</h1></body></html>';
    exit;
}

$adminUser = [
    'id'       => (int)$_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role'     => $user['role'],
    'balance'  => (float)$user['balance'],
];
