<?php
// services/CartService.php

/**
 * ดึงรายการในตะกร้าของ user พร้อมข้อมูลสินค้า / variant
 * โครง:
 * [
 *   [
 *     'product_id'   => ...,
 *     'variant_id'   => ...,
 *     'product_name' => ...,
 *     'variant_name' => ...,
 *     'price'        => ...,
 *     'quantity'     => ...,
 *     'line_total'   => ...
 *   ],
 *   ...
 * ]
 */
function getCartItems(mysqli $conn, int $user_id): array
{
    $items = [];

    $sql = "
        SELECT 
            c.product_id,
            c.variant_id,
            c.quantity,

            -- ✅ ชื่อ + รูปตามที่ JS เดิมใช้
            p.name  AS name,
            p.image AS image,

            -- ชื่อ/รูป variant (เผื่อใช้ต่อ)
            v.variant_name AS variant_name,
            v.image        AS variant_image,

            -- ราคา
            COALESCE(v.price, p.price) AS price
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN product_variants v ON c.variant_id = v.id
        WHERE c.user_id = ?
        ORDER BY c.id DESC
    ";

    $res = db_query($conn, $sql, [$user_id], "i");

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $qty   = (int)$row['quantity'];
            $price = (float)$row['price'];

            $items[] = [
                'product_id'    => (int)$row['product_id'],
                'variant_id'    => (int)($row['variant_id'] ?? 0),
                'name'          => $row['name'],          // ✅ key เดิม
                'image'         => $row['image'],         // ✅ key เดิม
                'variant_name'  => $row['variant_name'] ?? null,
                'variant_image' => $row['variant_image'] ?? null,
                'price'         => $price,
                'quantity'      => $qty,
                'line_total'    => $price * $qty,
            ];
        }
    }

    return $items;
}


/**
 * ลบสินค้าหลายตัวจากตะกร้า (ใช้ตอนจ่ายทั้ง cart)
 */
function clearCartForProducts(mysqli $conn, int $user_id, array $product_ids): void
{
    if (empty($product_ids)) {
        return;
    }

    $sql = "
        DELETE FROM cart_items
        WHERE user_id = ? AND product_id = ?
    ";

    foreach ($product_ids as $pid) {
        $pid = (int)$pid;
        db_query($conn, $sql, [$user_id, $pid], "ii");
    }
}

/**
 * ลบสินค้าทีละชิ้นจากตะกร้า (ใช้ตอนโหมด single)
 * ถ้า $variant_id = 0 แปลว่าลบตัวที่ไม่มี variant
 */
function clearSingleCartItem(mysqli $conn, int $user_id, int $product_id, int $variant_id): void
{
    $sql = "
        DELETE FROM cart_items
        WHERE user_id = ?
          AND product_id = ?
          AND (variant_id = ? OR ? = 0)
    ";

    db_query(
        $conn,
        $sql,
        [$user_id, $product_id, $variant_id, $variant_id],
        "iiii"
    );
}
