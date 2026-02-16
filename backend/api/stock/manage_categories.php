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
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            throw new Exception('ชื่อหมวดหมู่ห้ามว่าง');
        }

        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        // Check if category already exists
        $checkRes = $conn->query("SELECT id FROM product_categories WHERE name = '" . $conn->real_escape_string($name) . "'");
        if ($checkRes && $checkRes->num_rows > 0) {
            throw new Exception('หมวดหมู่นี้มีอยู่แล้ว');
        }

        $stmt = $conn->prepare("
            INSERT INTO product_categories (name, slug, description, status)
            VALUES (?, ?, ?, 'active')
        ");

        $stmt->bind_param('sss', $name, $slug, $description);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'เพิ่มหมวดหมู่เรียบร้อยแล้ว'
            ]);
        } else {
            throw new Exception('ไม่สามารถเพิ่มหมวดหมู่ได้');
        }

        $stmt->close();

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('ID หมวดหมู่ไม่ถูกต้อง');
        }

        $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'ลบหมวดหมู่เรียบร้อยแล้ว'
            ]);
        } else {
            throw new Exception('ไม่สามารถลบหมวดหมู่ได้');
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
