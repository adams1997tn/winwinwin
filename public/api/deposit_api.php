<?php
/**
 * User Deposit API — Submit deposit requests with screenshot upload + dynamic fields.
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;
use App\Admin\PaymentManager;
use App\Admin\WalletManager;
use App\Core\CsrfProtection;

// Must be logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = Database::getInstance($config['db'])->getPdo();

$userStmt = $db->prepare('SELECT id, banned FROM users WHERE id = ?');
$userStmt->execute([$_SESSION['user_id']]);
$currentUser = $userStmt->fetch();
if (!$currentUser || $currentUser['banned']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Account suspended']);
    exit;
}

$walletMgr  = new WalletManager($db);
$paymentMgr = new PaymentManager($db, $walletMgr);
$csrf       = new CsrfProtection();
$userId     = (int)$_SESSION['user_id'];

// ── GET requests ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    try {
        switch ($action) {
            case 'csrf_token':
                echo json_encode(['success' => true, 'token' => $csrf->getToken()]);
                break;

            case 'methods':
                echo json_encode(['success' => true, 'methods' => $paymentMgr->getActivePaymentMethods()]);
                break;

            case 'history':
                $limit = min((int)($_GET['limit'] ?? 50), 200);
                echo json_encode(['success' => true, 'deposits' => $paymentMgr->getUserDepositRequests($userId, $limit)]);
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

// ── POST: Submit deposit request (multipart/form-data for file upload) ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    // CSRF validation (token comes in POST body for multipart forms)
    $csrf->validate($_POST['_csrf_token'] ?? null);
} catch (\RuntimeException $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token invalid. Refresh the page.']);
    exit;
}

try {
    if ($action !== 'submit_deposit') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }

    $methodId        = (int)($_POST['method_id'] ?? 0);
    $amount          = (float)($_POST['amount'] ?? 0);
    $transactionCode = trim($_POST['transaction_code'] ?? '');

    if (!$methodId || !$amount || !$transactionCode) {
        throw new \InvalidArgumentException('All fields are required');
    }

    // Handle screenshot upload
    $screenshotPath = null;
    if (!empty($_FILES['screenshot']) && $_FILES['screenshot']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = dirname(__DIR__, 2) . '/uploads/transactions';
        $screenshotPath = $paymentMgr->handleScreenshotUpload($_FILES['screenshot'], $uploadDir);
    }

    // Parse dynamic submitted_data from POST (keys prefixed with "field_")
    $submittedData = [];
    foreach ($_POST as $key => $value) {
        if (str_starts_with($key, 'field_')) {
            $fieldName = substr($key, 6); // strip "field_" prefix
            $submittedData[$fieldName] = $value;
        }
    }

    $deposit = $paymentMgr->createDepositRequest($userId, [
        'method_id'        => $methodId,
        'amount'           => $amount,
        'transaction_code' => $transactionCode,
        'screenshot_path'  => $screenshotPath,
        'submitted_data'   => $submittedData,
    ]);

    echo json_encode(['success' => true, 'deposit' => $deposit, 'message' => 'Deposit request submitted']);

} catch (\InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
