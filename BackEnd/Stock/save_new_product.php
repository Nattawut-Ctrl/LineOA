<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

// ถ้ามีไฟล์ cloudinary_config.php ให้โหลดเพื่อใช้อัปโหลดรูปไป Cloudinary
if (file_exists(UTILS_PATH . '/cloudinary_config.php')) {
    require_once UTILS_PATH . '/cloudinary_config.php';
}


require_admin();
$conn = connectDBWithLog();
$adminId = $_SESSION['admin_id'] ?? null;


// -----------------------
// 1) รับค่าจากฟอร์ม
// -----------------------
$name        = trim($_POST['name'] ?? '');
$category    = trim($_POST['category'] ?? '');
$sku         = trim($_POST['sku'] ?? '');
$price       = floatval($_POST['price'] ?? 0);
$stock       = intval($_POST['stock'] ?? 0);
$unit        = trim($_POST['unit'] ?? '');
$description = trim($_POST['description'] ?? '');

// ตรวจสอบ input
if ($name == '' || $category == '' || $price <= 0) {

    // log case input ไม่ถูกต้อง
    writeLog(
        $conn,
        "INSERT products (invalid input)",
        ['name' => $name, 'category' => $category, 'sku' => $sku, 'unit' => $unit, 'price' => $price],
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

    // ถ้าเคยตั้งค่า Cloudinary แล้ว (มี class และ config)
    if (class_exists('Cloudinary\\Uploader')) {
        try {
            $uploadResult = Cloudinary\Uploader::upload(
                $_FILES['image']['tmp_name'],
                [
                    'folder' => 'line-shop/products'
                ]
            );
            if (!empty($uploadResult['secure_url'])) {
                $productImage = $uploadResult['secure_url']; // เก็บเป็น URL ตรงจาก Cloudinary
            }
        } catch (Exception $e) {
            // ถ้า Cloudinary ใช้ไม่ได้ ให้ fallback ไปเก็บในโฟลเดอร์ local
        }
    }

    // ถ้ายังไม่มีรูปจาก Cloudinary หรือใช้ Cloudinary ไม่ได้ -> ใช้เก็บ local แบบเดิม
    if ($productImage === null) {
        // โฟลเดอร์จริงในเครื่อง
        $uploadDirFs = BASE_PATH . "/uploads/products/";   // เช่น C:\xampp\htdocs\LineOA\uploads\products\
        if (!is_dir($uploadDirFs)) {
            mkdir($uploadDirFs, 0777, true);
        }

        $ext      = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $fileName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

        // path ที่ใช้ move_upload (ฝั่ง filesystem)
        $targetFs = $uploadDirFs . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFs)) {
            // path ที่เก็บลงฐานข้อมูล (ไม่ใส่ ../../)
            $productImage = "uploads/products/" . $fileName;
        }
    }
}



// -----------------------
// 3) INSERT สินค้า
// -----------------------
$resultProduct = db_exec(
    $conn,
    "INSERT INTO products (sku, name, category, price, stock, unit, description, image)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    [$sku, $name, $category, $price, $stock, $unit, $description, $productImage],
    "sssdisss"
);

$product_id = $conn->insert_id;

if (!$resultProduct['ok'] || !$product_id) {
    header("Location: addStock.php?error=invalid_product_input");
    exit;
}

// -----------------------
// 4) INSERT Variants + รูป
// -----------------------
if (!empty($_POST['variant_name'])) {

    $variant_names  = $_POST['variant_name'];
    $variant_prices = $_POST['variant_price'];
    $variant_stocks = $_POST['variant_stock'];
    $variant_images = $_FILES['variant_image'];

    // โฟลเดอร์จริงสำหรับ variant
    $variantDirFs = BASE_PATH . "/uploads/variants/";
    if (!is_dir($variantDirFs)) {
        mkdir($variantDirFs, 0777, true);
    }

    foreach ($variant_names as $i => $vname) {

        if ($vname == '') continue;

        $vprice = floatval($variant_prices[$i] ?? 0);
        $vstock = intval($variant_stocks[$i] ?? 0);
        $vimage = null;

        // --- upload รูป variant
        if (!empty($variant_images['name'][$i])) {

            $ext      = pathinfo($variant_images['name'][$i], PATHINFO_EXTENSION);
            $fileName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

            $targetFs = $variantDirFs . $fileName;

            if (move_uploaded_file($variant_images['tmp_name'][$i], $targetFs)) {
                // path ที่เก็บลง DB
                $vimage = "uploads/variants/" . $fileName;
            }
        }

        // --- insert variant
        db_exec(
            $conn,
            "INSERT INTO product_variants (product_id, variant_name, price, stock, image)
             VALUES (?, ?, ?, ?, ?)",
            [$product_id, $vname, $vprice, $vstock, $vimage],
            "isdis"
        );
    }
}

// -----------------------
// 5) เสร็จ → redirect
// -----------------------
header("Location: addStock.php?success=new_product_created");
exit;
