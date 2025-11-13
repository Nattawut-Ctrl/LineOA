<?php
require_once '../../config.php';
$conn = connectDB();

// รับค่าจากฟอร์ม
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
$add_stock  = isset($_POST['add_stock']) ? intval($_POST['add_stock']) : 0;

// ตรวจสอบข้อมูล
if ($product_id <= 0 || $add_stock <= 0) {
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
    $stmt->execute();
    $stmt->close();

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
$stmt->execute();
$stmt->close();

header("Location: addStock.php?success=product_stock_added");
exit;
