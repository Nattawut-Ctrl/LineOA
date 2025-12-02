<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

// =============================
// 1) รับค่าจากฟอร์ม
// =============================
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0; // ถ้าไม่มีจะเป็น 0
$add_stock  = isset($_POST['add_stock']) ? (int)$_POST['add_stock'] : 0;

// ตรวจสอบเบื้องต้น
if ($product_id <= 0 || $add_stock <= 0) {
    header("Location: addStock.php?error=invalid_input");
    exit;
}

// =============================
// 2) เช็กว่าสินค้ามีอยู่จริงไหม
// =============================
$resProduct = db_query(
    $conn,
    "SELECT id FROM products WHERE id = ?",
    [$product_id],
    "i"
);

if (!$resProduct || $resProduct->num_rows === 0) {
    header("Location: addStock.php?error=product_not_found");
    exit;
}

// =============================
// 3) กรณีมี variant → เพิ่ม stock variant + คำนวณยอดรวมใหม่
// =============================
if ($variant_id > 0) {

    // 3.1 อัปเดต stock ของ variant ที่เลือก
    $resultVariant = db_exec(
        $conn,
        "UPDATE product_variants 
         SET stock = stock + ? 
         WHERE id = ? AND product_id = ?",
        [$add_stock, $variant_id, $product_id],
        "iii"
    );

    if (!$resultVariant['ok'] || $resultVariant['affected'] <= 0) {
        // อัปเดตไม่สำเร็จ
        header("Location: addStock.php?error=variant_update_failed");
        exit;
    }

    // 3.2 ดึง SUM(stock) ของทุก variant ของสินค้านี้
    $resSum = db_query(
        $conn,
        "SELECT COALESCE(SUM(stock), 0) AS total_stock
         FROM product_variants
         WHERE product_id = ?",
        [$product_id],
        "i"
    );

    $rowSum      = $resSum ? $resSum->fetch_assoc() : null;
    $totalStock  = $rowSum ? (int)$rowSum['total_stock'] : 0;

    // 3.3 อัปเดต stock ในตาราง products ให้เท่ากับผลรวม variant
    db_exec(
        $conn,
        "UPDATE products 
         SET stock = ? 
         WHERE id = ?",
        [$totalStock, $product_id],
        "ii"
    );

} else {
    // =============================
    // 4) กรณีไม่มี variant → เพิ่ม stock ให้สินค้าหลักโดยตรง
    // =============================

    $resultProduct = db_exec(
        $conn,
        "UPDATE products 
         SET stock = stock + ? 
         WHERE id = ?",
        [$add_stock, $product_id],
        "ii"
    );

    if (!$resultProduct['ok'] || $resultProduct['affected'] <= 0) {
        header("Location: addStock.php?error=product_stock_update_failed");
        exit;
    }
}

// =============================
// 5) เสร็จ → redirect กลับไปหน้า addStock
// =============================
header("Location: addStock.php?success=product_stock_added");
exit;
