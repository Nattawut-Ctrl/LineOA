<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

require_once '../../config.php';
$conn = connectDB();

$user_id = (int)$_SESSION['user_id'];

// อ่าน JSON จาก body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!isset($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'invalid_cart']);
    exit;
}

$cart = $data['cart'];

// ลบของเก่าในตะกร้าของ user นี้ก่อน (sync แบบเขียนทับทั้งตะกร้า)
$stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// เตรียม INSERT
$stmt = $conn->prepare("
    INSERT INTO cart_items (user_id, product_id, variant_id, quantity, price)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($cart as $item) {
    $product_id = (int)$item['product_id'];
    $variant_id = isset($item['variant_id']) ? (int)$item['variant_id'] : null;
    $quantity   = max(1, (int)$item['quantity']);
    $price      = (float)$item['price'];

    // bind_param ใช้ "i i i i d" แต่ variant_id อาจเป็น null → แยกจัดการนิดนึง
    if ($variant_id > 0) {
        $stmt->bind_param("iiiid", $user_id, $product_id, $variant_id, $quantity, $price);
    } else {
        // ถ้า variant_id เป็น null → ส่งเป็น NULL ใน SQL
        $null = null;
        $stmt->bind_param("iiiid", $user_id, $product_id, $null, $quantity, $price);
    }

    $stmt->execute();
}

$stmt->close();

echo json_encode(['status' => 'ok']);
exit;
