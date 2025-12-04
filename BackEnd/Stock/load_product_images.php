<?php
// backend/Stock/load_product_images.php

session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/product_image_helper.php';

require_admin();
$conn = connectDBWithLog();

header('Content-Type: application/json; charset=utf-8');

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($productId <= 0) {
    echo json_encode([]);
    exit;
}

$rows = [];

$res = db_query(
    $conn,
    "SELECT id, image_path, sort_order
     FROM product_images
     WHERE product_id = ?
     ORDER BY sort_order ASC, id ASC",
    [$productId],
    "i"
);

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'id'  => (int)$r['id'],
            'url' => buildImageUrlFromPath($r['image_path']),
        ];
    }
}

echo json_encode($rows);
