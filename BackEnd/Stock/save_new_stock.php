<?php
session_start();
require_once '../../utils/db_with_log.php';
$conn = connectDBWithLog();

$userId = $_SESSION['user_id'] ?? null;

// รับค่าจากฟอร์ม
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
$add_stock  = isset($_POST['add_stock']) ? intval($_POST['add_stock']) : 0;

// ตรวจสอบข้อมูลเบื้องต้น
if ($product_id <= 0 || $add_stock <= 0) {

    // log กรณี input ไม่ถูกต้อง (ไม่มี SQL ให้ยิง เลยใช้ writeLog ตรง ๆ)
    writeLog(
        $conn,
        "UPDATE products (invalid stock input)",
        [
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'add_stock'  => $add_stock,
        ],
        '',
        'error',
        'save_new_stock: invalid input'
    );

    header("Location: addStock.php?error=invalid_input");
    exit;
}

// -------------------------------------------
// 1️⃣ กรณีมีตัวเลือกสินค้า → อัปเดต product_variants
// -------------------------------------------
if (!empty($variant_id)) {

    $result = db_exec(
        $conn,
        "UPDATE product_variants 
         SET stock = stock + ? 
         WHERE id = ?",
        [$add_stock, $variant_id],
        "ii"
    );

    if (!$result['ok']) {
        header("Location: addStock.php?error=invalid_input");
        exit;
    }

    // log สรุป (db_exec ก็ log แล้ว แต่ตัวนี้เอาไว้ดูภาพรวม)
    writeLog(
        $conn,
        "UPDATE product_variants (add stock)",
        [
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'add_stock'  => $add_stock,
        ],
        '',
        'success',
        null,
        $variant_id
    );

    header("Location: addStock.php?success=variant_stock_added");
    exit;
}

// -------------------------------------------
// 2️⃣ ถ้าไม่มีตัวเลือก → เพิ่ม stock ใน products
// -------------------------------------------
$result = db_exec(
    $conn,
    "UPDATE products 
     SET stock = stock + ? 
     WHERE id = ?",
    [$add_stock, $product_id],
    "ii"
);

if (!$result['ok']) {
    header("Location: addStock.php?error=invalid_input");
    exit;
}

// log สรุปฝั่ง product
writeLog(
    $conn,
    "UPDATE products (add stock)",
    [
        'product_id' => $product_id,
        'add_stock'  => $add_stock,
    ],
    '',
    'success',
    null,
    $product_id
);

header("Location: addStock.php?success=product_stock_added");
exit;
?>
