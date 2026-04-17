<?php
/**
 * Admin Guard — include at top of every admin page.
 * Redirects non-admin users to the public site.
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;

// Must be logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /bet/public/index.php');
    exit;
}

// Verify admin role from DB (not just session — prevents privilege escalation)
$db = Database::getInstance($config['db'])->getPdo();
$stmt = $db->prepare('SELECT role, banned FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !in_array($user['role'], ['admin', 'super_admin'], true) || $user['banned']) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body style="background:#0d0f14;color:#ef4444;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0"><h1>403 — Access Denied</h1></body></html>';
    exit;
}

// Admin user info available to pages
$adminUser = [
    'id'       => (int)$_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role'     => $user['role'],
];
