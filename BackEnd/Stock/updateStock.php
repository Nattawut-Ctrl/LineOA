<?php
session_start();
require_once '../../config.php';
$conn = connectDB();

$product_id = (int)$_POST['product_id'];

// ถ้าเป็นสินค้าธรรมดา (ไม่มี variant)
if (isset($_POST['product_stock'])) {

    $addStock = (int)$_POST['product_stock'];

    if ($addStock > 0) {
        $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt->bind_param("ii", $addStock, $product_id);
        $stmt->execute();
    }

    header("Location: addStock.php?updated=1");
    exit;
}

// ถ้าเป็นสินค้าแบบมี variant
if (isset($_POST['variant_stock'])) {
    foreach ($_POST['variant_stock'] as $variantId => $addStock) {

        $addStock = (int)$addStock;

        if ($addStock > 0) {
            $stmt = $conn->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?");
            $stmt->bind_param("ii", $addStock, $variantId);
            $stmt->execute();
        }
    }

    header("Location: addStock.php?updated=1");
    exit;
}

echo "ไม่มีข้อมูลอัปเดต";
