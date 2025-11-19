<?php
session_start();
require_once '../../utils/db_with_log.php';
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
$price       = floatval($_POST['price'] ?? 0);
$stock       = intval($_POST['stock'] ?? 0);
$description = $_POST['description'] ?? '';

$resultProduct = db_exec(
    $conn,
    "UPDATE products 
     SET name = ?, price = ?, stock = ?, description = ?
     WHERE id = ?",
    [$name, $price, $stock, $description, $id],
    "sdisi"
);

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
$statusOverall = ($resultProduct['ok'] && $okVariantsAll) ? 'success' : 'error';

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
?>
