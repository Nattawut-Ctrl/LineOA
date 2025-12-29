<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/csrf.php';
require_once SERVICES_PATH . '/slipService.php';

require_admin();
$conn = connectDBWithLog();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

csrf_verify($_POST['csrf_token'] ?? null);

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
    http_response_code(400);
    exit('Invalid input');
}

$conn->begin_transaction();

try {
    if ($status === 'approved') {
        $ok = approvePaymentAndApplyStock($conn, $id);
    } else { // 'rejected'
        $ok = rejectPaymentAndReleaseStock($conn, $id);
    }

    if (!$ok) {
        throw new Exception('Cannot change payment status or update stock');
    }

    $conn->commit();
    header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/list.php?ok=1');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());
    header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/list.php?error=1');
    exit;
}
