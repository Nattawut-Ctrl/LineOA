<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/upload_image.php';

// ถ้ามีไฟล์ cloudinary_config.php ให้โหลด (ใช้สำหรับอัปโหลดรูปไป Cloudinary)
if (file_exists(UTILS_PATH . '/cloudinary_config.php')) {
    require_once UTILS_PATH . '/cloudinary_config.php';
}

require_admin();
$conn = connectDBWithLog();
$adminId = $_SESSION['admin_id'] ?? null;

// =======================
// ตั้งค่าระบบ LOG ลงไฟล์
// =======================
$logDir = BASE_PATH . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/save_new_product.log';

function logProductDebug(string $msg)
{
    global $logFile;
    error_log("[" . date("Y-m-d H:i:s") . "] " . $msg . "\n", 3, $logFile);
}

logProductDebug("==== save_new_product.php CALLED ====");

// -----------------------
// 1) รับค่าจากฟอร์ม
// -----------------------
$name        = trim($_POST['name'] ?? '');
$category    = trim($_POST['category'] ?? '');
$sku         = '';
$price       = 0;
$stock       = 0;
$unit        = trim($_POST['unit'] ?? '');
$description = trim($_POST['description'] ?? '');

logProductDebug("INPUT: name={$name}, category={$category}, sku={$sku}, price={$price}, stock={$stock}, unit={$unit}");

// ตรวจสอบ input
if ($name === '' || $category === '') {
    logProductDebug("ERROR: invalid product input (name/category missing)");

    writeLog(
        $conn,
        "INSERT products (invalid input)",
        ['name' => $name, 'category' => $category, 'unit' => $unit],
        '',
        'error',
        'save_new_product: invalid product input'
    );

    header("Location: addStock.php?error=invalid_product_input");
    exit;
}

// -----------------------
// 2) อัปโหลดรูปสินค้า
// -----------------------
$productImage = null;

if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    logProductDebug("PRODUCT IMAGE: file received: " . $_FILES['image']['name']);

    $productImage = uploadImageWithFallback(
        $_FILES['image'],
        'products',
        'line-shop/products'
    );

    if ($productImage !== null) {
        logProductDebug("PRODUCT IMAGE SAVED: " . $productImage);
    } else {
        logProductDebug("ERROR: uploadImageWithFallback FAILED for product image");
    }
} else {
    logProductDebug("PRODUCT IMAGE: no file uploaded or upload error code=" . ($_FILES['image']['error'] ?? 'N/A'));
}

// -----------------------
// 3) INSERT สินค้า
// -----------------------
logProductDebug("INSERTING PRODUCT into DB...");

$resultProduct = db_exec(
    $conn,
    "INSERT INTO products (sku, name, category, price, stock, unit, description, image)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    [$sku, $name, $category, $price, $stock, $unit, $description, $productImage],
    "sssdisss"
);

// ใช้ insert_id จากผลลัพธ์ db_exec โดยตรง
$product_id = (int)($resultProduct['insert_id'] ?? 0);

if (!$resultProduct['ok'] || !$product_id) {
    logProductDebug("ERROR: INSERT PRODUCT FAILED. result=" . json_encode($resultProduct) . ", product_id=" . $product_id);

    header("Location: addStock.php?error=invalid_product_input");
    exit;
}

logProductDebug("PRODUCT INSERT SUCCESS: product_id=" . $product_id . ", image=" . ($productImage ?? 'NULL'));

if (!$resultProduct['ok'] || !$product_id) {
    logProductDebug("ERROR: INSERT PRODUCT FAILED. result=" . json_encode($resultProduct) . ", product_id=" . $product_id);

    header("Location: addStock.php?error=invalid_product_input");
    exit;
}

logProductDebug("PRODUCT INSERT SUCCESS: product_id=" . $product_id . ", image=" . ($productImage ?? 'NULL'));

// -----------------------
// 4) INSERT Variants + รูป
// -----------------------
$totalStock = 0;
$minPrice   = null;

if (!empty($_POST['variant_name'])) {
    $variant_names  = $_POST['variant_name'];
    $variant_skus   = $_POST['variant_sku'] ?? [];
    $variant_prices = $_POST['variant_price'] ?? [];
    $variant_stocks = $_POST['variant_stock'] ?? [];
    $variant_images = $_FILES['variant_image'] ?? null;

    logProductDebug("VARIANTS: received count=" . count($variant_names));

    foreach ($variant_names as $i => $vname) {
        $vnameTrim = trim($vname);
        if ($vnameTrim === '') {
            logProductDebug("VARIANT[$i]: skipped (empty name)");
            continue;
        }

        $vsku   = trim($variant_skus[$i] ?? '');
        $vprice = isset($variant_prices[$i]) ? floatval($variant_prices[$i]) : 0;
        $vstock = isset($variant_stocks[$i]) ? intval($variant_stocks[$i]) : 0;

        if ($vprice > 0 && ($minPrice === null || $vprice < $minPrice)) {
            $minPrice = $vprice;
        }
        if ($vstock > 0) {
            $totalStock += $vstock;
        }

        logProductDebug("VARIANT[$i]: name={$vnameTrim}, sku={$vsku}, price={$vprice}, stock={$vstock}");

        // --- upload รูป variant (ใช้ Cloudinary + fallback local)
        $vimage = null;
        if ($variant_images && !empty($variant_images['name'][$i])) {
            $file = [
                'name'     => $variant_images['name'][$i],
                'type'     => $variant_images['type'][$i],
                'tmp_name' => $variant_images['tmp_name'][$i],
                'error'    => $variant_images['error'][$i],
                'size'     => $variant_images['size'][$i],
            ];

            $vimage = uploadImageWithFallback($file, 'variants', 'line-shop/variants');

            if ($vimage !== null) {
                logProductDebug("VARIANT[$i]: IMAGE SAVED: " . $vimage);
            } else {
                logProductDebug("VARIANT[$i]: IMAGE UPLOAD FAILED");
            }
        } else {
            logProductDebug("VARIANT[$i]: no image uploaded");
        }

        $finalPrice = $minPrice ?? 0;

        // --- insert variant
        $resVar = db_exec(
            $conn,
            "INSERT INTO product_variants (product_id, sku, variant_name, price, stock, image)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$product_id, $vsku, $vnameTrim, $vprice, $vstock, $vimage],
            "issdis"
        );

        if (!$resVar['ok']) {
            logProductDebug("VARIANT[$i]: ERROR INSERT FAILED. result=" . json_encode($resVar));
        } else {
            logProductDebug("VARIANT[$i]: INSERT SUCCESS");
        }
    }

    // หลัง loop variant
    if ($minPrice !== null || $totalStock > 0) {
        $finalPrice = $minPrice ?? 0;

        db_exec(
            $conn,
            "UPDATE products SET price = ?, stock = ? WHERE id = ?",
            [$finalPrice, $totalStock, $product_id],
            "dii"
        );

        logProductDebug("UPDATE PRODUCT SUMMARY: price={$finalPrice}, stock={$totalStock}");
    }
} else {
    logProductDebug("NO VARIANTS SENT in form.");
}

// -----------------------
// 5) เสร็จ → redirect
// -----------------------
logProductDebug("DONE: redirect to addStock.php?success=new_product_created");
header("Location: addStock.php?success=new_product_created");
exit;
