<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conn = connectDBWithLog();

    $sql = "
        UPDATE payments
        SET buyer_notified = 1
        WHERE user_id = ?
          AND status IN ('approved', 'rejected')
          AND buyer_notified = 0
    ";

    db_exec($conn, $sql, [$userId], "i");

    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
