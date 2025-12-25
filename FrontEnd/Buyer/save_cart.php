<?php
// pages/Shop/save_cart.php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/stock_helper.php';

header('Content-Type: application/json; charset=utf-8');

// ---- 1) ต้องมี user_id ----
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'not_logged_in'
    ]);
    exit;
}

$conn = connectDBWithLog();

// ---- 2) รับ JSON จาก fetch ----
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$cart = $data['cart'] ?? [];
if (!is_array($cart)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'invalid_payload'
    ]);
    exit;
}

try {
    // ใช้ transaction กันพังกลางทาง
    $conn->begin_transaction();

    // ---- 3) ลบตะกร้าเก่าของ user คนนี้ก่อน ----
    db_query($conn, "DELETE FROM cart_items WHERE user_id = ?", [$user_id], "i");

    // ---- 4) ใส่ทุกรายการจาก JS cart ลง DB ใหม่ ----
    foreach ($cart as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $qty        = max(1, (int)($item['quantity'] ?? 1));

        // ดึง variant_id แบบปลอดภัย
        $variant_raw = $item['variant_id'] ?? null;
        // แปลง '' / null → null จริง ๆ
        $variant_id  = ($variant_raw === '' || $variant_raw === null)
            ? null
            : (int)$variant_raw;

        if ($product_id <= 0) {
            // ข้ามถ้าข้อมูลไม่ครบ
            continue;
        }

        $available = getAvailableStock($conn, $product_id, $variant_id);

        if ($available <= 0 || $qty > $available) {
            $conn->rollback();
            echo json_encode([
                'status'  => 'error',
                'message' => 'out_of_stock',
                'detail'  => 'สินค้านี้สต็อกไม่พอหรือถูกจองเต็มแล้ว',
            ]);
            exit;
        }

        // 👉 แยก 2 เคสให้ชัด: มี/ไม่มี variant
        if ($variant_id === null) {
            // สินค้าไม่มีตัวเลือก
            $sql    = "INSERT INTO cart_items (user_id, product_id, quantity)
                       VALUES (?, ?, ?)";
            $params = [$user_id, $product_id, $qty];
            $types  = "iii";
        } else {
            // สินค้ามีตัวเลือก
            $sql    = "INSERT INTO cart_items (user_id, product_id, variant_id, quantity)
                       VALUES (?, ?, ?, ?)";
            $params = [$user_id, $product_id, $variant_id, $qty];
            $types  = "iiii";
        }

        db_query($conn, $sql, $params, $types);
        // ❌ ห้ามมี return/exit ใน loop ตรงนี้เด็ดขาด ไม่งั้นจะทำได้แค่ชิ้นแรก


    }

    $conn->commit();

    echo json_encode([
        'status' => 'ok'
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode([
        'status'  => 'error',
        'message' => 'db_error',
        'detail'  => $e->getMessage()
    ]);
}
