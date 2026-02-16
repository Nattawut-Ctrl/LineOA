<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_POST['action'] ?? null;

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $symbol = trim($_POST['symbol'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            throw new Exception('ชื่อหน่วยนับห้ามว่าง');
        }

        // Check if unit already exists
        $checkRes = $conn->query("SELECT id FROM product_units WHERE name = '" . $conn->real_escape_string($name) . "'");
        if ($checkRes && $checkRes->num_rows > 0) {
            throw new Exception('หน่วยนับนี้มีอยู่แล้ว');
        }

        $stmt = $conn->prepare("
            INSERT INTO product_units (name, symbol, description, status)
            VALUES (?, ?, ?, 'active')
        ");

        $stmt->bind_param('sss', $name, $symbol, $description);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มหน่วยนับเรียบร้อยแล้ว'
            ]);
        } else {
            throw new Exception('ไม่สามารถเพิ่มหน่วยนับได้');
        }

        $stmt->close();

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('ID หน่วยนับไม่ถูกต้อง');
        }

        $stmt = $conn->prepare("DELETE FROM product_units WHERE id = ?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'ลบหน่วยนับเรียบร้อยแล้ว'
            ]);
        } else {
            throw new Exception('ไม่สามารถลบหน่วยนับได้');
        }

        $stmt->close();

    } else {
        throw new Exception('การดำเนินการไม่ถูกต้อง');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
