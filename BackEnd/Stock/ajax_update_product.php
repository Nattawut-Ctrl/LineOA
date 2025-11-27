<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
// ถ้ามีไฟล์ cloudinary_config.php ให้โหลด (ใช้สำหรับอัปโหลดรูปไป Cloudinary)
if (file_exists(UTILS_PATH . '/cloudinary_config.php')) {
    require_once UTILS_PATH . '/cloudinary_config.php';
}

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
$logDir  = __DIR__ . "/logs";   // ใช้ __DIR__ ง่ายสุดก่อน
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = $logDir . "/cloudinary_debug.log";

// เทสแบบบังคับเขียน log แน่นอน
error_log("[" . date("Y-m-d H:i:s") . "] TEST: ajax_update_product reached\n", 3, $logFile);

if (!empty($_POST['new_variant_name'])) {

    foreach ($_POST['new_variant_name'] as $i => $nvName) {

        $nvName = trim($nvName);
        if ($nvName === '') continue;  // ข้ามถ้าว่าง

        $nvPrice = floatval($_POST['new_variant_price'][$i] ?? 0);
        $nvStock = intval($_POST['new_variant_stock'][$i] ?? 0);

        // อัปโหลดรูป
        $imagePath = null;

        if (!empty($_FILES['new_variant_image']['name'][$i]) && $_FILES['new_variant_image']['error'][$i] === UPLOAD_ERR_OK) {

            if (class_exists('\\Cloudinary\\Uploader')) {

                error_log("[" . date("Y-m-d H:i:s") . "] FOUND: Cloudinary Uploader class OK\n", 3, $logFile);

                try {
                    $uploadResult = \Cloudinary\Uploader::upload(
                        $_FILES['new_variant_image']['tmp_name'][$i],
                        ['folder' => 'line-shop/variants']
                    );

                    if (!empty($uploadResult['secure_url'])) {
                        $imagePath = $uploadResult['secure_url']; // เก็บเป็น URL

                        error_log("[" . date("Y-m-d H:i:s") . "] SUCCESS: Uploaded to Cloudinary: {$imagePath}\n", 3, $logFile);
                    } else {
                        error_log("[" . date("Y-m-d H:i:s") . "] ERROR: Cloudinary returned no secure_url\n", 3, $logFile);
                    }
                } catch (Exception $e) {
                    error_log("[" . date("Y-m-d H:i:s") . "] EXCEPTION: " . $e->getMessage() . "\n", 3, $logFile);
                }
            } else {
                error_log("[" . date("Y-m-d H:i:s") . "] NOT FOUND: Cloudinary\\Uploader class missing\n", 3, $logFile);
            }

            // ถ้ายังไม่มีรูปจาก Cloudinary -> fallback เก็บ local
            if ($imagePath === null) {
                // โฟลเดอร์ที่จะเก็บรูป
                $uploadDir = BASE_PATH . "/uploads/variants/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // ชื่อไฟล์
                $filename = time() . "_" . basename($_FILES['new_variant_image']['name'][$i]);

                // path เต็ม
                $targetPath = $uploadDir . $filename;

                // ย้ายไฟล์จาก temp ไปโฟลเดอร์จริง
                if (move_uploaded_file($_FILES['new_variant_image']['tmp_name'][$i], $targetPath)) {
                    // path แบบที่เก็บในฐานข้อมูล (ไม่ใส่ ../../)
                    $imagePath = "uploads/variants/" . $filename;

                    error_log("[" . date("Y-m-d H:i:s") . "] FALLBACK: saved local as {$imagePath}\n", 3, $logFile);
                } else {
                    error_log("[" . date("Y-m-d H:i:s") . "] ERROR: move_uploaded_file failed for local fallback\n", 3, $logFile);
                }
            }
        }


        // บันทึกลงฐานข้อมูล
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
