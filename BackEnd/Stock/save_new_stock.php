<?php
session_start();
require_once '../../config.php';
$conn = connectDB();

$userId = $_SESSION['user_id'] ?? null;

// รับค่าจากฟอร์ม
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
$add_stock  = isset($_POST['add_stock']) ? intval($_POST['add_stock']) : 0;

// ตรวจสอบข้อมูล
if ($product_id <= 0 || $add_stock <= 0) {

    // log input ไม่ถูกต้อง
    log_db_action(
        $conn,
        $userId,
        'UPDATE',
        'products',
        $product_id,
        "save_new_stock: invalid input (product_id=$product_id, add_stock=$add_stock, variant_id=$variant_id)",
        'fail'
    );

    header("Location: addStock.php?error=invalid_input");
    exit;
}

// -------------------------------------------
// 1️⃣ ถ้าเลือกตัวเลือกสินค้า → อัปเดต product_variants
// -------------------------------------------
if (!empty($variant_id)) {

    $sql = "UPDATE product_variants 
            SET stock = stock + ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $add_stock, $variant_id);
    $ok = $stmt->execute();
    $stmt->close();

    // log การเพิ่ม stock ของ variant
    log_db_action(
        $conn,
        $userId,
        'UPDATE',
        'product_variants',
        $variant_id,
        "save_new_stock: add stock +$add_stock to variant_id=$variant_id (product_id=$product_id)",
        $ok ? 'success' : 'fail'
    );

    if (!$ok) {
        header("Location: addStock.php?error=invalid_input");
        exit;
    }

    header("Location: addStock.php?success=variant_stock_added");
    exit;
}

// -------------------------------------------
// 2️⃣ ถ้าไม่มีตัวเลือก → เพิ่ม stock ในสินค้า (products)
// -------------------------------------------
$sql = "UPDATE products 
        SET stock = stock + ? 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $add_stock, $product_id);
$ok = $stmt->execute();
$stmt->close();

// log การเพิ่ม stock ของสินค้า
log_db_action(
    $conn,
    $userId,
    'UPDATE',
    'products',
    $product_id,
    "save_new_stock: add stock +$add_stock to product_id=$product_id (no variant)",
    $ok ? 'success' : 'fail'
);

if (!$ok) {
    header("Location: addStock.php?error=invalid_input");
    exit;
}

header("Location: addStock.php?success=product_stock_added");
exit;
?>