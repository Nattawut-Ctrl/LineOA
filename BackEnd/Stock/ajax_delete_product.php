<?php
require_once '../../config.php';
$conn = connectDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    exit('Invalid ID');
}

// ------------------------
// 1) ลบไฟล์รูปสินค้า
// ------------------------
$p = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc();
if ($p && !empty($p['image']) && file_exists($p['image'])) {
    unlink($p['image']);
}

// ------------------------
// 2) ลบไฟล์รูป variant ทั้งหมด
// ------------------------
$v = $conn->query("SELECT image FROM product_variants WHERE product_id = $id");
while ($row = $v->fetch_assoc()) {
    if (!empty($row['image']) && file_exists($row['image'])) {
        unlink($row['image']);
    }
}

// ------------------------
// 3) ลบข้อมูลที่เกี่ยวข้องใน DB
// ------------------------

// ลบของที่อยู่ในตะกร้า
$conn->query("DELETE FROM cart_items WHERE product_id = $id");

// ลบตัวเลือกสินค้า
$conn->query("DELETE FROM product_variants WHERE product_id = $id");

// ลบสินค้า
$conn->query("DELETE FROM products WHERE id = $id");

// ตอบกลับ
header('Content-Type: text/plain; charset=utf-8');
echo "success";
