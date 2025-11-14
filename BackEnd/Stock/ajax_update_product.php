<?php
session_start();
require_once '../../config.php';
$conn = connectDB();

$userId = $_SESSION['user_id'] ?? null;

// ให้รับเฉพาะ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ตรวจสอบ id สินค้า
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {

    // log กรณี id ไม่ถูกต้อง
    log_db_action(
        $conn,
        $userId,
        'UPDATE',
        'products',
        $id,
        'ajax_update_product: invalid product id',
        'fail'
    );

    http_response_code(400);
    exit('Invalid ID');
}

// ----------------------
// 1) อัปเดตสินค้า products
// ----------------------
$name        = $_POST['name'] ?? '';
$price       = $_POST['price'] ?? 0;
$stock       = $_POST['stock'] ?? 0;
$description = $_POST['description'] ?? '';

$stmt = $conn->prepare("
    UPDATE products 
    SET name = ?, price = ?, stock = ?, description = ?
    WHERE id = ?
");
$stmt->bind_param("sdisi", $name, $price, $stock, $description, $id);
$okProduct = $stmt->execute();
$stmt->close();

// ----------------------
// 2) อัปเดต variants (ถ้ามี)
// ----------------------
$okVariantsAll = true;

if (!empty($_POST['variant_id'])) {
    foreach ($_POST['variant_id'] as $i => $vid) {

        $vid   = (int)$vid;
        $vName = $_POST['variant_name'][$i]  ?? '';
        $vPrice = $_POST['variant_price'][$i] ?? 0;
        $vStock = $_POST['variant_stock'][$i] ?? 0;

        $stmt = $conn->prepare("
            UPDATE product_variants 
            SET variant_name = ?, price = ?, stock = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sdii", $vName, $vPrice, $vStock, $vid);
        $okThis = $stmt->execute();
        $stmt->close();

        if (!$okThis) {
            $okVariantsAll = false;
        }

        // log การอัปเดต variant ทีละตัว
        log_db_action(
            $conn,
            $userId,
            'UPDATE',
            'product_variants',
            $vid,
            "ajax_update_product: update variant id=$vid for product id=$id",
            $okThis ? 'success' : 'fail'
        );
    }
}

// ----------------------
// 3) log การอัปเดตสินค้า
// ----------------------
$statusOverall = ($okProduct && $okVariantsAll) ? 'success' : 'fail';

log_db_action(
    $conn,
    $userId,
    'UPDATE',
    'products',
    $id,
    "ajax_update_product: update product id=$id (name, price, stock, description, variants)",
    $statusOverall
);

// ถ้าอัปเดตมีปัญหา ส่ง error code
if ($statusOverall !== 'success') {
    http_response_code(500);
    echo "error";
    exit;
}

// ทุกอย่างผ่าน
echo "success";
?>