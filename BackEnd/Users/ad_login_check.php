<?php
session_start();
require_once UTILS_PATH . '/db_with_log.php';
$conn = connectDBWithLog();

// ถ้าอยากเก็บว่าใครเป็นคนเพิ่ม (admin)
// สมมติ login admin เก็บไว้ใน $_SESSION['admin_id']
$adminId = $_SESSION['admin_id'] ?? null;

// -----------------------
// ฟังก์ชันช่วย clean input
// -----------------------
function clean($s)
{
    return trim($s ?? '');
}

// -----------------------
// 1) รับค่าจากฟอร์ม
// -----------------------
$name        = clean($_POST['name']        ?? '');
$category    = clean($_POST['category']    ?? '');
$price       = floatval($_POST['price']    ?? 0);
$stock       = intval($_POST['stock']      ?? 0);
$description = clean($_POST['description'] ?? '');

// ฟิลด์ใหม่
$sku    = clean($_POST['sku']   ?? '');
$unit   = clean($_POST['unit']  ?? '');
$status = $_POST['status']      ?? 'active';
if ($status !== 'active' && $status !== 'inactive') {
    $status = 'active';
}

// ตรวจสอบ input เบื้องต้น
if ($name === '' || $category === '' || $price <= 0) {

    // log case input ไม่ถูกต้อง
    writeLog(
        $conn,
        "INSERT products (invalid input)",
        [
            'name'     => $name,
            'category' => $category,
            'price'    => $price
        ],
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

if (!empty($_FILES['image']['name'])) {

    $targetDir = "../../uploads/products/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // ตั้งชื่อไฟล์ใหม่กันชื่อซ้ำ
    $ext       = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $fileName  = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        // เก็บ path ที่หน้า addStock.php ใช้งานได้
        $productImage = $targetFile;
    }
}

// -----------------------
// 3) INSERT สินค้า
// -----------------------
// name        = s
// category    = s
// price       = d
// stock       = i
// description = s
// image       = s
// sku         = s
// unit        = s
// status      = s

$resultProduct = db_exec(
    $conn,
    "INSERT INTO products (name, category, price, stock, description, image, sku, unit, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
    [$name, $category, $price, $stock, $description, $productImage, $sku, $unit, $status],
    "ssdisssss"
);

$product_id = $conn->insert_id;

if (!$resultProduct['ok'] || !$product_id) {

    writeLog(
        $conn,
        "INSERT products (failed)",
        [
            'name'     => $name,
            'category' => $category,
            'price'    => $price
        ],
        '',
        'error',
        'save_new_product: insert product failed'
    );

    header("Location: addStock.php?error=invalid_product_input");
    exit;
}

// log กรณีสำเร็จ
writeLog(
    $conn,
    "INSERT products (success)",
    [
        'product_id' => $product_id,
        'name'       => $name,
        'category'   => $category
    ],
    '',
    'success',
    null,
    $adminId
);

// -----------------------
// 4) INSERT Variants + รูป
// -----------------------
if (!empty($_POST['variant_name'])) {

    $variant_names  = $_POST['variant_name'];
    $variant_prices = $_POST['variant_price'];
    $variant_stocks = $_POST['variant_stock'];
    $variant_images = $_FILES['variant_image'];

    $targetDir = "../../uploads/variants/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    foreach ($variant_names as $i => $vname) {

        if (trim($vname) === '') {
            continue;
        }

        $vname  = clean($vname);
        $vprice = floatval($variant_prices[$i] ?? 0);
        $vstock = intval($variant_stocks[$i] ?? 0);
        $vimage = null;

        // --- upload รูป variant
        if (!empty($variant_images['name'][$i])) {

            $ext       = pathinfo($variant_images['name'][$i], PATHINFO_EXTENSION);
            $fileName  = 'var_' . $product_id . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($variant_images['tmp_name'][$i], $targetFile)) {
                $vimage = $targetFile;
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
