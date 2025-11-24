<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/SlipService.php';

$conn = connectDBWithLog();

$id     = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

if (!in_array($status, ['approved', 'rejected'])) {
    die("Invalid status");
}

updatePaymentStatus($conn, $id, $status);

header("Location: list.php");
exit;
