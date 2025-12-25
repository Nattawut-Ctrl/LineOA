<?php
// BackEnd/Stock/upload_product_image.php

session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/upload_image.php';
require_once UTILS_PATH . '/product_image_helper.php';

require_admin();
$conn = connectDBWithLog();

header('Content-Type: application/json; charset=utf-8');

// อนุญาตเฉพาะ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

// รับ product id จากทั้ง 2 key ทั้ง GET และ POST
$productId = 0;

if (isset($_REQUEST['product_id'])) {
    $productId = (int)$_REQUEST['product_id'];
} elseif (isset($_REQUEST['id'])) {
    $productId = (int)$_REQUEST['id'];
}

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode([
        'error'      => 'invalid_product',
        'debug_post' => $_REQUEST, // จะได้เห็นทุกอย่าง
    ]);
    exit;
}

/**
 * 2) รับไฟล์จาก key ที่ Dropzone ส่งมา
 *    - ถ้าใช้ paramName: "file"  → $_FILES['file']
 *    - เผื่อกรณีเก่าใช้ชื่อ image  → $_FILES['image']
 */
$uploadKey = null;
if (isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
    $uploadKey = 'file';
} elseif (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $uploadKey = 'image';
}

if ($uploadKey === null) {
    http_response_code(400);
    echo json_encode([
        'error'       => 'no_file',
        'debug_files' => $_FILES,
    ]);
    exit;
}

$src = $_FILES[$uploadKey];

// เตรียม array ให้เข้ากับ uploadImageWithFallback
$file = [
    'name'     => $src['name'],
    'type'     => $src['type'],
    'tmp_name' => $src['tmp_name'],
    'error'    => $src['error'],
    'size'     => $src['size'],
];

/**
 * 3) อัปโหลดผ่าน helper เดิม (Cloudinary + local fallback)
 */
$imagePath = uploadImageWithFallback($file, 'product_gallery', 'line-shop/product-gallery');
if (!$imagePath) {
    http_response_code(500);
    echo json_encode(['error' => 'upload_failed']);
    exit;
}

/**
 * 4) บันทึกเข้า product_images
 */
$result = db_exec(
    $conn,
    "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)",
    [$productId, $imagePath],
    "is"
);

$imageId = $result['insert_id'] ?? $conn->insert_id;

echo json_encode([
    'id'  => (int)$imageId,
    'url' => buildImageUrlFromPath($imagePath),
]);
