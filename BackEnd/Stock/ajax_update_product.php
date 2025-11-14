<?php
require_once '../../config.php';
$conn = connectDB();

// อัปเดตสินค้า
$id = $_POST['id'];
$stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=?, description=? WHERE id=?");
$stmt->bind_param("sdisi", $_POST['name'], $_POST['price'], $_POST['stock'], $_POST['description'], $id);
$stmt->execute();

// อัปเดต variants
if (!empty($_POST['variant_id'])) {
    foreach ($_POST['variant_id'] as $i => $vid) {
        
        $name  = $_POST['variant_name'][$i];
        $price = $_POST['variant_price'][$i];
        $stock = $_POST['variant_stock'][$i];

        $stmt = $conn->prepare("UPDATE product_variants SET variant_name=?, price=?, stock=? WHERE id=?");
        $stmt->bind_param("sdii", $name, $price, $stock, $vid);
        $stmt->execute();
    }
}

echo "success";
