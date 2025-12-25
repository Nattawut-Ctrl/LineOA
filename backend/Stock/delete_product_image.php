<?php
// backend/Stock/delete_product_image.php

session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/upload_image.php';
require_once UTILS_PATH . '/product_image_helper.php';

require_admin();
$conn = connectDBWithLog();

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "method_not_allowed";
    exit;
}

$imageId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($imageId <= 0) {
    http_response_code(400);
    echo "invalid_id";
    exit;
}

// ดึง path เดิม
$res = db_query(
    $conn,
    "SELECT image_path FROM product_images WHERE id = ?",
    [$imageId],
    "i"
);
$row = $res ? $res->fetch_assoc() : null;

$imagePath = $row['image_path'] ?? null;

// ลบไฟล์ local ถ้ามี
if ($imagePath) {
    deleteImageFileIfLocal($imagePath);
}

// ลบจาก DB
db_exec(
    $conn,
    "DELETE FROM product_images WHERE id = ?",
    [$imageId],
    "i"
);

echo "success";