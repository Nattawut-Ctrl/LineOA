<?php
require_once '../../config.php';
$conn = connectDB();

$id = intval($_POST['id']);

// ลบรูปสินค้าและ variant ด้วย
$p = $conn->query("SELECT image FROM products WHERE id=$id")->fetch_assoc();
if ($p && file_exists($p['image'])) unlink($p['image']);

$v = $conn->query("SELECT image FROM product_variants WHERE product_id=$id");
while ($row = $v->fetch_assoc()) {
    if ($row['image'] && file_exists($row['image'])) unlink($row['image']);
}

// ลบใน DB
$conn->query("DELETE FROM product_variants WHERE product_id = $id");
$conn->query("DELETE FROM products WHERE id = $id");

echo "success";
