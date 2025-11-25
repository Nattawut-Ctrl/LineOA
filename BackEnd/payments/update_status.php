<?php
session_start();
require_once __DIR__ . '/../../config.php';
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

// ถ้ามีการตัด/คืน stock หลายตาราง → ทำใน transaction
$conn->begin_transaction();

try {
    updatePaymentStatus($conn, $id, $status);

    $conn->commit();
    header("Location: list.php?ok=1");
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());
    header("Location: list.php?error=1");
    exit;
}
