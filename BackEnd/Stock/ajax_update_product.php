<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/upload_image.php';

require_admin();
$conn = connectDBWithLog();

$userId = $_SESSION['user_id'] ?? null;

// รับเฉพาะ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Product ID
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {

    // log error
    writeLog(
        $conn,
        "UPDATE products (invalid id)",
        [],
        '',
        'error',
        'ajax_update_product: invalid product id',
        $id
    );

    http_response_code(400);
    exit('Invalid ID');
}

// ----------------------
// 1) อัปเดต products
// ----------------------
$name        = $_POST['name'] ?? '';
$sku         = trim($_POST['sku'] ?? '');
$price       = floatval($_POST['price'] ?? 0);
$stock       = intval($_POST['stock'] ?? 0);
$unit        = trim($_POST['unit'] ?? '');
$description = $_POST['description'] ?? '';

if ($name === '' || $sku === '' || $unit === '' || $price <= 0) {
    http_response_code(400);
    echo "invalid_input";
    exit;
}

$resultProduct = db_exec(
    $conn,
    "UPDATE products 
     SET sku = ?, name = ?, price = ?, stock = ?, unit = ?, description = ?
     WHERE id = ?",
    [$sku, $name, $price, $stock, $unit, $description, $id],
    "ssdissi"
);

// ----------------------
// 3) เพิ่ม variants ใหม่ (รองรับอัปโหลดรูป)
// ----------------------
$okNewVariants = true;

if (!empty($_POST['new_variant_name'])) {
    $newNames  = $_POST['new_variant_name'];
    $newPrices = $_POST['new_variant_price'] ?? [];
    $newStocks = $_POST['new_variant_stock'] ?? [];
    $newImages = $_FILES['new_variant_image'] ?? null;

    foreach ($newNames as $i => $nvName) {
        $nvName = trim($nvName);
        if ($nvName === '') {
            continue;
        }

        $nvPrice = isset($newPrices[$i]) ? floatval($newPrices[$i]) : 0;
        $nvStock = isset($newStocks[$i]) ? intval($newStocks[$i]) : 0;

        $imagePath = null;
        if ($newImages && !empty($newImages['name'][$i]) && $newImages['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name'     => $newImages['name'][$i],
                'type'     => $newImages['type'][$i],
                'tmp_name' => $newImages['tmp_name'][$i],
                'error'    => $newImages['error'][$i],
                'size'     => $newImages['size'][$i],
            ];

            $imagePath = uploadImageWithFallback($file, 'variants', 'line-shop/variants');
        }

        $insert = db_exec(
            $conn,
            "INSERT INTO product_variants (product_id, variant_name, image, price, stock)
             VALUES (?, ?, ?, ?, ?)",
            [$id, $nvName, $imagePath, $nvPrice, $nvStock],
            "issdi"
        );

        if (!$insert['ok']) {
            $okNewVariants = false;
        }
    }
}

// ----------------------
// 2) อัปเดต variants
// ----------------------
$okVariantsAll = true;

if (!empty($_POST['variant_id'])) {

    foreach ($_POST['variant_id'] as $i => $vid) {

        $vid    = intval($vid);
        $vName  = $_POST['variant_name'][$i] ?? '';
        $vPrice = floatval($_POST['variant_price'][$i] ?? 0);
        $vStock = intval($_POST['variant_stock'][$i] ?? 0);

        $resultVariant = db_exec(
            $conn,
            "UPDATE product_variants 
             SET variant_name = ?, price = ?, stock = ?
             WHERE id = ?",
            [$vName, $vPrice, $vStock, $vid],
            "sdii"
        );

        if (!$resultVariant['ok']) {
            $okVariantsAll = false;
        }
    }
}

// ----------------------
// 3) ประเมินผลทั้งหมด
// ----------------------
$statusOverall = ($resultProduct['ok'] && $okVariantsAll && $okNewVariants) ? 'success' : 'error';

// Log final result ของการอัปเดต product
writeLog(
    $conn,
    "UPDATE products + variants",
    [
        'product_id' => $id,
        'name'       => $name,
        'price'      => $price,
        'stock'      => $stock,
        'description' => $description
    ],
    '',
    $statusOverall,
    $statusOverall === 'success' ? null : 'One or more update failed',
    $id
);

if ($statusOverall !== 'success') {
    http_response_code(500);
    echo "error";
    exit;
}

echo "success";
