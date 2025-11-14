<?php
session_start();
require_once '../../config.php';
$conn = connectDB();

// -----------------------
// 1) รับค่าจากฟอร์มสินค้าใหม่
// -----------------------
$name        = $_POST['name'] ?? '';
$category    = $_POST['category'] ?? '';
$price       = floatval($_POST['price'] ?? 0);
$stock       = intval($_POST['stock'] ?? 0);
$description = $_POST['description'] ?? '';

if ($name == '' || $category == '' || $price <= 0) {
    header("Location: addStock.php?error=invalid_product_input");
    exit;
}

// -----------------------
// 2) อัปโหลดรูปหลักสินค้า & เก็บ Full Path
// -----------------------
$productImage = null;

if (!empty($_FILES['image']['name'])) {

    $targetDir = "../../uploads/products/";

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        // 🟢 เก็บ full path
        $productImage = $targetFile;
    }
}

// -----------------------
// 3) บันทึกข้อมูลสินค้า
// -----------------------
$sql = "INSERT INTO products (name, category, price, stock, description, image)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdiss", $name, $category, $price, $stock, $description, $productImage);
$stmt->execute();

$product_id = $stmt->insert_id;
$stmt->close();

// -----------------------
// 4) บันทึก Variants + รูป + Full Path
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

        if ($vname == '') continue;

        $vprice = floatval($variant_prices[$i] ?? 0);
        $vstock = intval($variant_stocks[$i] ?? 0);
        $vimage = null;

        // -------- อัปโหลดรูป variant & เก็บ full path
        if (!empty($variant_images['name'][$i])) {

            $fileName = time() . "_" . basename($variant_images['name'][$i]);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($variant_images['tmp_name'][$i], $targetFile)) {
                $vimage = $targetFile;  // 🟢 บันทึก full path
            }
        }

        // -------- บันทึกลง DB
        $sql = "INSERT INTO product_variants (product_id, variant_name, price, stock, image)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdis", $product_id, $vname, $vprice, $vstock, $vimage);
        $stmt->execute();
        $stmt->close();
    }
}

// -----------------------
// 5) เสร็จ
// -----------------------
header("Location: addStock.php?success=new_product_created");
exit;
