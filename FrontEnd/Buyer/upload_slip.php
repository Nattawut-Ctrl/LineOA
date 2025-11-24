<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';

require_once SERVICES_PATH . '/SlipService.php';
require_once SERVICES_PATH . '/CartService.php';

$conn = connectDBWithLog();
$user_id = $_SESSION['user_id'];

$mode = $_POST['mode'] ?? 'single';

if (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
    die("กรุณาอัปโหลดสลิป");
}

// --------------- Upload Slip ----------------
$ext = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
$filename = time() . "_" . uniqid() . "." . $ext;
$uploadPath = BASE_PATH . "/uploads/slips/" . $filename;

move_uploaded_file($_FILES['slip']['tmp_name'], $uploadPath);

$slip_path = "uploads/slips/" . $filename;


// ---------- Payment From Cart ----------
if ($mode === 'cart') {

    $product_ids = $_POST['product_id'];
    $variant_ids = $_POST['variant_id'];
    $quantities  = $_POST['quantity'];
    $names       = $_POST['product_name'];
    $vn_names    = $_POST['variant_name'];
    $prices      = $_POST['price'];

    $items = [];
    $total = 0;

    foreach ($product_ids as $i => $pid) {
        $items[] = [
            'product_id'   => (int)$pid,
            'variant_id'   => (int)$variant_ids[$i],
            'name'         => $names[$i],
            'variant_name' => $vn_names[$i],
            'price'        => (float)$prices[$i],
            'quantity'     => (int)$quantities[$i]
        ];
        $total += $prices[$i] * $quantities[$i];
    }

    $items_json = json_encode($items, JSON_UNESCAPED_UNICODE);

    $payment_id = createPayment($conn, [
        'user_id'    => $user_id,
        'product_id' => null,
        'variant_id' => null,
        'items_json' => $items_json,
        'amount'     => $total,
        'slip_path'  => $slip_path,
        'mode'       => 'cart'
    ]);

    // clear cart
    clearCartForProducts($conn, $user_id, $product_ids);

    header("Location: payment.php?success=1");
    exit;
}


// ---------- Payment Single ----------
$product_id  = (int)($_POST['product_id'] ?? 0);
$variant_id  = (int)($_POST['variant_id'] ?? 0);
$quantity    = (int)($_POST['quantity'] ?? 1);
$amount      = (float)($_POST['amount'] ?? 0);

$item = [
    'product_id'   => $product_id,
    'variant_id'   => $variant_id,
    'name'         => $_POST['product_name'],
    'variant_name' => $_POST['variant_name'],
    'price'        => $amount,
    'quantity'     => $quantity
];

$payment_id = createPayment($conn, [
    'user_id'    => $user_id,
    'product_id' => $product_id,
    'variant_id' => $variant_id,
    'items_json' => json_encode([$item], JSON_UNESCAPED_UNICODE),
    'amount'     => $amount,
    'slip_path'  => $slip_path,
    'mode'       => 'single'
]);

clearSingleCartItem($conn, $user_id, $product_id, $variant_id);

header("Location: payment.php?success=1");
exit;
