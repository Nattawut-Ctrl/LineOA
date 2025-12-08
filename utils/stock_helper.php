<?php
// utils/stock_helper.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db_with_log.php';

/**
 * คืนค่า stock ที่ "ยังขายได้" = stock - reserved_stock
 * ถ้า $variantId มีค่า → ดูจากตาราง product_variants
 * ถ้า $variantId เป็น null หรือ <= 0 → ดูจากตาราง products
 */
function getAvailableStock(mysqli $conn, int $productId, ?int $variantId = null): int
{
    // 👉 เคสระบุ variant_id ชัดเจน → ดูแถวเดียว
    if ($variantId !== null && $variantId > 0) {
        $sql = "SELECT stock, reserved_stock
                FROM product_variants
                WHERE id = ? AND product_id = ?";
        $res = db_query($conn, $sql, [$variantId, $productId], "ii");

        if (!$res || $res->num_rows === 0) {
            return 0;
        }

        $row      = $res->fetch_assoc();
        $stock    = (int)($row['stock'] ?? 0);
        $reserved = (int)($row['reserved_stock'] ?? 0);

        $available = $stock - $reserved;
        return $available > 0 ? $available : 0;
    }

    // 👉 เคสไม่ได้ระบุ variant_id
    //    ถ้าสินค้ามีตัวเลือก ให้ SUM จาก product_variants แทน
    $check = db_query(
        $conn,
        "SELECT COUNT(*) AS c FROM product_variants WHERE product_id = ?",
        [$productId],
        "i"
    );
    $rowCheck = $check ? $check->fetch_assoc() : null;
    $hasVariants = $rowCheck && (int)$rowCheck['c'] > 0;

    if ($hasVariants) {
        // รวม stock - reserved_stock ของทุก variant
        $sql = "SELECT SUM(stock - reserved_stock) AS available
                FROM product_variants
                WHERE product_id = ?";
        $res = db_query($conn, $sql, [$productId], "i");
        $row = $res ? $res->fetch_assoc() : null;

        $available = (int)($row['available'] ?? 0);
        return $available > 0 ? $available : 0;
    }

    // ถ้าไม่มีตัวเลือกเลย → ค่อยไปอ่านจาก products
    $sql = "SELECT stock, reserved_stock
            FROM products
            WHERE id = ?";
    $res = db_query($conn, $sql, [$productId], "i");

    if (!$res || $res->num_rows === 0) {
        return 0;
    }

    $row      = $res->fetch_assoc();
    $stock    = (int)($row['stock'] ?? 0);
    $reserved = (int)($row['reserved_stock'] ?? 0);

    $available = $stock - $reserved;
    return $available > 0 ? $available : 0;
}
