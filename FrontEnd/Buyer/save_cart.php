<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

require_once UTILS_PATH . '/db_with_log.php';

$conn    = connectDBWithLog();
$user_id = (int)$_SESSION['user_id'];

// อ่าน JSON
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'invalid_cart']);
    exit;
}

$cart = $data['cart'];

try {
    // 1) ลบของเก่าในตะกร้า
    db_query(
        $conn,
        "DELETE FROM cart_items WHERE user_id = ?",
        [$user_id],
        "i"
    );

    // 2) INSERT รายการใหม่ทั้งหมด
    foreach ($cart as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $variant_id = $item['variant_id'] ?? null;
        $variant_id = ($variant_id === '' ? null : $variant_id);
        $variant_id = $variant_id !== null ? (int)$variant_id : null;
        $quantity   = max(1, (int)($item['quantity'] ?? 1));
        $price      = (float)($item['price'] ?? 0);

        if ($product_id <= 0 || $quantity <= 0) {
            continue;
        }

        if ($variant_id === null) {
            db_query(
                $conn,
                "INSERT INTO cart_items (user_id, product_id, variant_id, quantity, price)
                 VALUES (?, ?, NULL, ?, ?)",
                [$user_id, $product_id, $quantity, $price],
                "iiid"
            );
        } else {
            db_query(
                $conn,
                "INSERT INTO cart_items (user_id, product_id, variant_id, quantity, price)
                 VALUES (?, ?, ?, ?, ?)",
                [$user_id, $product_id, $variant_id, $quantity, $price],
                "iiiid"
            );
        }
    }

    echo json_encode(['status' => 'ok', 'count' => count($cart)]);
} catch (Throwable $e) {
    // ถ้ามี error ใด ๆ ให้ส่งเป็น JSON กลับไป
    echo json_encode([
        'status'  => 'error',
        'message' => 'db_error',
        'error'   => $e->getMessage()
    ]);
}
exit;
