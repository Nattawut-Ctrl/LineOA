<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode([
        'status' => 'ok',
        'count'  => 0,
        'items'  => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conn = connectDBWithLog();

    $sql = "
        SELECT 
            id AS payment_id,
            amount,
            status,
            created_at
        FROM payments
        WHERE user_id = ?
          AND status IN ('approved', 'rejected')
          AND buyer_notified = 0
        ORDER BY id DESC
    ";

    $rows = [];
    $res  = db_query($conn, $sql, [$userId], "i");

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }

    echo json_encode([
        'status' => 'ok',
        'count'  => count($rows),
        'items'  => $rows,
        'order_count' => count($pending),
        'pending' => $pending,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
