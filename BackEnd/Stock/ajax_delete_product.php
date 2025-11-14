<?php
session_start();
require_once '../../config.php';
$conn = connectDB();

$userId = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {

    log_db_action(
        $conn,
        $userId,
        'DELETE',
        'products',
        $id,
        'Invalid product id in ajax_delete_product',
        'fail'
    );

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
$okCart    = $conn->query("DELETE FROM cart_items WHERE product_id = $id");
$okVariant = $conn->query("DELETE FROM product_variants WHERE product_id = $id");
$okProduct = $conn->query("DELETE FROM products WHERE id = $id");

// ประเมินสถานะ
$status = ($okCart && $okVariant && $okProduct) ? 'success' : 'fail';

// บันทึก log
log_db_action(
    $conn,
    $userId,
    'DELETE',
    'products',
    $id,
    "Delete product id=$id from products, product_variants, cart_items",
    $status
);

// ถ้าลบไม่สำเร็จ ส่ง error
if ($status !== 'success') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "error";
    exit;
}

// ตอบกลับปกติ
header('Content-Type: text/plain; charset=utf-8');
echo "success";
