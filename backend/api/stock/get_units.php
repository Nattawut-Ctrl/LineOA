<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

header('Content-Type: application/json; charset=utf-8');

try {
    $res = $conn->query("
        SELECT id, name, symbol, description, status
        FROM product_units
        WHERE status = 'active'
        ORDER BY name ASC
    ");

    if (!$res) {
        throw new Exception('ไม่สามารถดึงข้อมูลหน่วยนับได้');
    }

    $units = [];
    while ($row = $res->fetch_assoc()) {
        $units[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $units
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
