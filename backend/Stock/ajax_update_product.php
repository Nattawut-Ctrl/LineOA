<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/upload_image.php';
require_once UTILS_PATH . '/product_image_helper.php';

if (!function_exists('writeLog')) {
    die('DEBUG: db_with_log loaded from = ' . UTILS_PATH . '/db_with_log.php<br>แต่ไม่เจอฟังก์ชัน writeLog()');
} else {
    // แค่ทดสอบว่ามีจริง
    // echo 'DEBUG: writeLog() ถูกโหลดแล้ว'; exit;
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

// โหลด image เดิมของ product
$resP = db_query(
    $conn,
    "SELECT image FROM products WHERE id = ?",
    [$id],
    "i"
);
$rowP = $resP ? $resP->fetch_assoc() : null;
$oldProductImage = $rowP['image'] ?? null;

// ----------------------
// 1) ข้อมูลหลักของ product
// ----------------------
$name        = $_POST['name'] ?? '';
$unit        = trim($_POST['unit'] ?? '');
$description = $_POST['description'] ?? '';

$priceInput  = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$stockInput  = isset($_POST['stock']) ? intval($_POST['stock']) : 0;

$hasVariants = (!empty($_POST['variant_id']) || !empty($_POST['new_variant_name']));

if ($name === '' || $unit === '') {
    http_response_code(400);
    echo "invalid_input";
    exit;
}

if (!$hasVariants && $priceInput <= 0) {
    http_response_code(400);
    echo "invalid_price";
    exit;
}

// ----------------------
// 1.1 จัดการรูปหลักของสินค้า
// ----------------------
$deleteMainReq = !empty($_POST['product_image_delete']);
$newMainImagePath = null;

if (!empty($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {

    $file = [
        'name'     => $_FILES['product_image']['name'],
        'type'     => $_FILES['product_image']['type'],
        'tmp_name' => $_FILES['product_image']['tmp_name'],
        'error'    => $_FILES['product_image']['error'],
        'size'     => $_FILES['product_image']['size'],
    ];

    // โฟลเดอร์ของ product main image
    $uploaded = uploadImageWithFallback($file, 'products', 'line-shop/products');
    if ($uploaded) {
        $newMainImagePath = $uploaded;
    }
}

// ตัดสินใจรูปหลักสุดท้าย
$finalProductImage = $oldProductImage;

if ($deleteMainReq) {
    $finalProductImage = null;
}

if ($newMainImagePath !== null) {
    $finalProductImage = $newMainImagePath;
}

// ถ้ามีการเปลี่ยนรูปหลักจริง ๆ → update + ลบไฟล์เก่า
if ($finalProductImage !== $oldProductImage) {
    // ลบไฟล์เก่า (ถ้าเป็น local)
    if ($oldProductImage && ($deleteMainReq || $newMainImagePath)) {
        deleteImageFileIfLocal($oldProductImage);
    }
}

// ----------------------
// 1.2 UPDATE products (ยังไม่สรุปราคา/stock)
// ----------------------
$resultProduct = db_exec(
    $conn,
    "UPDATE products 
     SET name = ?, price = ?, stock = ?, unit = ?, description = ?, image = ?
     WHERE id = ?",
    [$name, $priceInput, $stockInput, $unit, $description, $finalProductImage, $id],
    "sdisssi"
);

// ----------------------
// 2) จัดการ variants เดิม + รูป
// ----------------------
$deleteReq = $_POST['variant_image_delete'] ?? [];
$files     = $_FILES['variant_image'] ?? null;

$deleteSet = [];
if (is_array($deleteReq)) {
    foreach ($deleteReq as $vid) {
        $deleteSet[(int)$vid] = true;
    }
}

$oldVarImages = [];
if (!empty($_POST['variant_id'])) {
    $resOld = db_query(
        $conn,
        "SELECT id, image 
         FROM product_variants 
         WHERE product_id = ?",
        [$id],
        "i"
    );

    if ($resOld) {
        while ($r = $resOld->fetch_assoc()) {
            $oldVarImages[(int)$r['id']] = $r['image'];
        }
    }
}

$okVariantsAll = true;

if (!empty($_POST['variant_id'])) {
    foreach ($_POST['variant_id'] as $i => $vidRaw) {

        $vid = intval($vidRaw);
        if ($vid <= 0) {
            continue;
        }

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

        $oldImage   = $oldVarImages[$vid] ?? null;
        $needDelete = isset($deleteSet[$vid]);
        $newImagePath = null;

        if ($files && isset($files['name'][$vid]) && $files['error'][$vid] !== UPLOAD_ERR_NO_FILE) {
            $file = [
                'name'     => $files['name'][$vid],
                'type'     => $files['type'][$vid],
                'tmp_name' => $files['tmp_name'][$vid],
                'error'    => $files['error'][$vid],
                'size'     => $files['size'][$vid],
            ];

            $uploaded = uploadImageWithFallback($file, 'variants', 'line-shop/variants');
            if ($uploaded) {
                $newImagePath = $uploaded;
            }
        }

        $finalImage = $oldImage;

        if ($needDelete) {
            $finalImage = null;
        }

        if ($newImagePath !== null) {
            $finalImage = $newImagePath;
        }

        if ($finalImage !== $oldImage) {
            db_exec(
                $conn,
                "UPDATE product_variants
                 SET image = ?
                 WHERE id = ?",
                [$finalImage, $vid],
                "si"
            );

            if ($oldImage && ($needDelete || $newImagePath) && $oldImage !== $finalImage) {
                deleteImageFileIfLocal($oldImage);
            }
        }
    }
}

// ----------------------
// 3) เพิ่ม variants ใหม่ (พร้อมรูป)
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
// 3.1 สรุปราคา/stock จาก variants
// ----------------------
$summaryPrice = $priceInput;
$summaryStock = $stockInput;

$sumRes = db_query(
    $conn,
    "SELECT MIN(price) AS min_price, SUM(stock) AS total_stock
     FROM product_variants
     WHERE product_id = ?",
    [$id],
    "i"
);

$okSummary = true;
if ($sumRes && ($row = $sumRes->fetch_assoc()) && $row['min_price'] !== null) {

    $summaryPrice = floatval($row['min_price']);
    $summaryStock = intval($row['total_stock']);

    $summaryUpdate = db_exec(
        $conn,
        "UPDATE products
         SET price = ?, stock = ?
         WHERE id = ?",
        [$summaryPrice, $summaryStock, $id],
        "dii"
    );

    if (!$summaryUpdate['ok']) {
        $okSummary = false;
    }
} else {
    $okSummary = false;
}

// ----------------------
// 4) ประเมินผล & log
// ----------------------
$statusOverall = ($resultProduct['ok'] && $okVariantsAll && $okNewVariants && $okSummary) ? 'success' : 'error';

writeLog(
    $conn,
    "UPDATE products + variants",
    [
        'product_id'  => $id,
        'name'        => $name,
        'price'       => $summaryPrice,
        'stock'       => $summaryStock,
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
