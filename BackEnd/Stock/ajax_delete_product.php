<?php
session_start();
require_once '../../utils/db_with_log.php';
$conn = connectDBWithLog();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// ------------------------
// ตรวจ ID ให้ถูกต้องก่อน
// ------------------------
if ($id <= 0) {
    // ถ้าอยาก log case นี้ด้วย (ไม่มี SQL จริง) ก็ใช้ writeLog ได้
    writeLog(
        $conn,
        'DELETE products (invalid id)',
        [],
        '',
        'error',
        'Invalid product id in ajax_delete_product',
        $id
    );

    http_response_code(400);
    exit('Invalid ID');
}

// ------------------------
// 1) ลบไฟล์รูปสินค้า
// ------------------------
$resP = db_query(
    $conn,
    "SELECT image FROM products WHERE id = ?",
    [$id],
    "i"
);

$p = $resP ? $resP->fetch_assoc() : null;

if ($p && !empty($p['image']) && file_exists($p['image'])) {
    unlink($p['image']);
}

// ------------------------
// 2) ลบไฟล์รูป variant ทั้งหมด
// ------------------------
$resV = db_query(
    $conn,
    "SELECT image FROM product_variants WHERE product_id = ?",
    [$id],
    "i"
);

while ($row = $resV->fetch_assoc()) {
    if (!empty($row['image']) && file_exists($row['image'])) {
        unlink($row['image']);
    }
}

// ------------------------
// 3) ลบข้อมูลที่เกี่ยวข้องใน DB
//    ใช้ db_exec → log ให้เอง
// ------------------------
$resultCart = db_exec(
    $conn,
    "DELETE FROM cart_items WHERE product_id = ?",
    [$id],
    "i"
);

$resultVariant = db_exec(
    $conn,
    "DELETE FROM product_variants WHERE product_id = ?",
    [$id],
    "i"
);

$resultProduct = db_exec(
    $conn,
    "DELETE FROM products WHERE id = ?",
    [$id],
    "i"
);

// ประเมินสถานะจากทั้ง 3 คำสั่ง
$status = (
    $resultCart['ok'] &&
    $resultVariant['ok'] &&
    $resultProduct['ok']
) ? 'success' : 'error';

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
