<?php
session_start();
header('Content-Type: application/json');

// ยังไม่ล็อกอิน → ส่ง error json ออกไป
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

// ✅ ใช้ db_with_log.php แทน config.php
require_once '../../utils/db_with_log.php';

$conn    = connectDBWithLog();
$user_id = (int)($_SESSION['user_id'] ?? 0);

// อ่าน JSON จาก body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!isset($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'invalid_cart']);
    exit;
}

$cart = $data['cart'];

// -----------------------------
// 1) ลบของเก่าในตะกร้าของ user นี้ก่อน
// -----------------------------
$sqlDelete = "DELETE FROM cart_items WHERE user_id = ?";
$delResult = db_exec($conn, $sqlDelete, [$user_id], "i");

// ถ้าอยากเช็ค error ก็ได้
if (!$delResult['ok']) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'delete_failed',
        'error'   => $delResult['error']
    ]);
    exit;
}

// -----------------------------
// 2) INSERT รายการใหม่ทั้งหมด
// -----------------------------
$sqlInsert = "
    INSERT INTO cart_items (user_id, product_id, variant_id, quantity, price)
    VALUES (?, ?, ?, ?, ?)
";

foreach ($cart as $item) {
    $product_id = isset($item['product_id']) ? (int)$item['product_id'] : 0;
    $variant_id = isset($item['variant_id']) ? (int)$item['variant_id'] : null;
    $quantity   = isset($item['quantity']) ? (int)$item['quantity'] : 1;
    $price      = isset($item['price']) ? (float)$item['price'] : 0;

    $quantity = max(1, $quantity);

    // ให้ variant_id เป็น null ได้เลย (คอลัมน์ต้อง allow NULL)
    $params = [
        $user_id,
        $product_id,
        $variant_id,   // null หรือ int
        $quantity,
        $price
    ];

    // types: user_id(i), product_id(i), variant_id(i), quantity(i), price(d)
    $insResult = db_exec($conn, $sqlInsert, $params, "iiiid");

    if (!$insResult['ok']) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'insert_failed',
            'error'   => $insResult['error']
        ]);
        exit;
    }
}

// ปิดคอนเน็กชัน (ถ้าอยากปิด)
$conn->close();

echo json_encode(['status' => 'ok']);
exit;
