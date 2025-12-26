<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';

require_once SERVICES_PATH . '/slipService.php';
require_once SERVICES_PATH . '/cartService.php';

$conn = connectDBWithLog();
$user_id = $_SESSION['user_id'];

$mode = $_POST['mode'] ?? 'single';

$transfer_date = $_POST['transfer_date'] ?? null; // YYYY-MM-DD
$transfer_time = $_POST['transfer_time'] ?? null; // HH:MM

if (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
    die("กรุณาอัปโหลดสลิป");
}

// --------------- Upload Slip ----------------
$ext = strtolower(pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif','webp'];
if (!in_array($ext, $allowed)) {
    die("ไฟล์สลิปต้องเป็นรูปภาพเท่านั้น (jpg/jpeg/png/gif/webp)");
}

$filename = time() . "_" . uniqid() . "." . $ext;

// โฟลเดอร์เก็บสลิปบนเครื่อง
$slipDirFs = rtrim(UPLOAD_BASE_DIR, '/\\') . '/slips';
if (!is_dir($slipDirFs)) {
    @mkdir($slipDirFs, 0777, true);
}

$uploadPath = $slipDirFs . "/" . $filename;

if (!move_uploaded_file($_FILES['slip']['tmp_name'], $uploadPath)) {
    die("อัปโหลดสลิปไม่สำเร็จ");
}

// path เก็บลง DB
$slip_path = rtrim(UPLOAD_BASE_URL_PATH, '/\\') . "/slips/" . $filename;
// -------------------------------------------

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

    $conn->begin_transaction();

    $payment_id = createPayment($conn, [
        'user_id'        => $user_id,
        'product_id'     => null,
        'variant_id'     => null,
        'items_json'     => $items_json,
        'amount'         => $total,
        'slip_path'      => $slip_path,
        'mode'           => 'cart',
        'transfer_date'  => $transfer_date,
        'transfer_time'  => $transfer_time,
    ]);

    if ($payment_id <= 0) {
        $conn->rollback();
        die("บันทึกข้อมูลการชำระเงินไม่สำเร็จ");
    }

    // กันสต็อกสำหรับ payment นี้
    if (!reserveStockForPayment($conn, $payment_id)) {
        $conn->rollback();
        die("สินค้าบางรายการสต็อกไม่เพียงพอ หรือถูกจองเต็มแล้ว");
    }

    // ลบจากตะกร้าเมื่อกันสต็อกสำเร็จ
    clearCartForProducts($conn, $user_id, $product_ids);

    $conn->commit();

    header("Location: " . FRONTEND_URL . "/pages/buyer/payment.php?success=1");
    exit;
}


// ---------- Payment Single ----------
$product_id  = (int)($_POST['product_id'] ?? 0);
$variant_id  = (int)($_POST['variant_id'] ?? 0);
$quantity    = (int)($_POST['quantity'] ?? 1);
$amount      = (float)($_POST['total'] ?? 0);

$item = [
    'product_id'   => $product_id,
    'variant_id'   => $variant_id,
    'name'         => $_POST['product_name'],
    'variant_name' => $_POST['variant_name'],
    'price'        => $amount,
    'quantity'     => $quantity
];

$conn->begin_transaction();

$payment_id = createPayment($conn, [
    'user_id'        => $user_id,
    'product_id'     => $product_id,
    'variant_id'     => $variant_id,
    'items_json'     => json_encode([$item], JSON_UNESCAPED_UNICODE),
    'amount'         => $amount,
    'slip_path'      => $slip_path,
    'mode'           => 'single',
    'transfer_date'  => $transfer_date,
    'transfer_time'  => $transfer_time,
]);

if ($payment_id <= 0) {
    $conn->rollback();
    die("บันทึกข้อมูลการชำระเงินไม่สำเร็จ");
}

if (!reserveStockForPayment($conn, $payment_id)) {
    $conn->rollback();
    die("สินค้าไม่เพียงพอ หรือถูกจองเต็มแล้ว");
}

clearSingleCartItem($conn, $user_id, $product_id, $variant_id);

$conn->commit();

header("Location: " . FRONTEND_URL . "/pages/buyer/payment.php?success=1");
exit;