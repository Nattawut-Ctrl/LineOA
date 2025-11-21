<?php
session_start();
require_once UTILS_PATH . '/db_with_log.php';
$conn = connectDBWithLog();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$variantId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($variantId <= 0) {
    writeLog(
        $conn,
        'DELETE product_variants (invalid id)',
        [],
        '',
        'error',
        'Invalid variant id in ajax_delete_variant',
        $variantId
    );

    http_response_code(400);
    exit('Invalid ID');
}

// 1) ลบไฟล์รูปของ variant
$resV = db_query(
    $conn,
    "SELECT image FROM product_variants WHERE id = ?",
    [$variantId],
    "i"
);

$v = $resV ? $resV->fetch_assoc() : null;

if ($v && !empty($v['image']) && file_exists($v['image'])) {
    unlink($v['image']);
}

// 2) ลบข้อมูลใน DB
$resultVariant = db_exec(
    $conn,
    "DELETE FROM product_variants WHERE id = ?",
    [$variantId],
    "i"
);

if (!$resultVariant['ok']) {
    http_response_code(500);
    echo "error";
    exit;
}

echo "success";
