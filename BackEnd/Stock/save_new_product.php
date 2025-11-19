<?php
session_start();
require_once '../../utils/db_with_log.php';
$conn = connectDBWithLog();

$userId = $_SESSION['user_id'] ?? null;

// -----------------------
// 1) รับค่าจากฟอร์ม
// -----------------------
$name        = trim($_POST['name'] ?? '');
$category    = trim($_POST['category'] ?? '');
$price       = floatval($_POST['price'] ?? 0);
$stock       = intval($_POST['stock'] ?? 0);
$description = trim($_POST['description'] ?? '');

// ตรวจสอบ input
if ($name == '' || $category == '' || $price <= 0) {

    // log case input ไม่ถูกต้อง
    writeLog(
        $conn,
        "INSERT products (invalid input)",
        ['name' => $name, 'category' => $category, 'price' => $price],
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
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $fileName   = time() . "_" . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        $productImage = $targetFile;  // full path
    }
}

// -----------------------
// 3) INSERT สินค้า
// -----------------------
$resultProduct = db_exec(
    $conn,
    "INSERT INTO products (name, category, price, stock, description, image)
     VALUES (?, ?, ?, ?, ?, ?)",
    [$name, $category, $price, $stock, $description, $productImage],
    "ssdiss"
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

    $targetDir = "../../uploads/variants/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    foreach ($variant_names as $i => $vname) {

        if ($vname == '') continue;

        $vprice = floatval($variant_prices[$i] ?? 0);
        $vstock = intval($variant_stocks[$i] ?? 0);
        $vimage = null;

        // --- upload รูป variant
        if (!empty($variant_images['name'][$i])) {

            $fileName   = time() . "_" . basename($variant_images['name'][$i]);
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
?>
