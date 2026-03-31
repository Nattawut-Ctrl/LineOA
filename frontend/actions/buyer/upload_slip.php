<?php
session_start();

// ป้องกัน browser cache (กันกด Back แล้วเห็นหน้าเก่า)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';

require_once SERVICES_PATH . '/slipService.php';

require_once SERVICES_PATH . '/paymentIntentService.php';
require_once SERVICES_PATH . '/cartService.php';
require_once FRONTEND_PATH . '/services/AddressService.php';
require_once BACKEND_PATH . '/services/adminEmailService.php';

$conn = connectDBWithLog();
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header("Location: " . FRONTEND_URL . "/pages/users/line-entry.php");
    exit;
}

// ตรวจสอบว่ามี intent_id หรือไม่ (จากการจองสต็อก)
$intent_id = (int)($_POST['intent_id'] ?? 0);
if ($intent_id <= 0) {
    header("Location: " . FRONTEND_URL . "/pages/buyer/Buyer.php?error=invalid_intent");
    exit;
}

$intent = getIntentById($conn, $intent_id);
if (!$intent || (int)$intent['user_id'] !== $user_id) {
    header("Location: " . FRONTEND_URL . "/pages/buyer/Buyer.php?error=intent_not_found");
    exit;
}

if (($intent['status'] ?? '') !== 'active') {
    header("Location: " . FRONTEND_URL . "/pages/buyer/Buyer.php?error=intent_not_active");
    exit;
}

if (isIntentExpired($intent)) {
    // ถ้าคุณมี release reservation ของ intent ให้เรียกตรงนี้ด้วย
    expireIntent($conn, $intent_id);
    header("Location: " . FRONTEND_URL . "/pages/buyer/Buyer.php?error=intent_expired");
    exit;
}

if (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
    header("Location: " . FRONTEND_URL . "/pages/buyer/payment.php?intent_id=" . $intent_id . "&error=no_slip");
    exit;
}

// --------------- Upload Slip ----------------
$uploadDir = rtrim(UPLOAD_BASE_DIR, '/\\') . '/slips';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$ext = strtolower(pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $allowed, true)) {
    header("Location: " . FRONTEND_URL . "/pages/buyer/payment.php?intent_id=" . $intent_id . "&error=invalid_file");
    exit;
}

$filename = 'slip_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$targetPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($_FILES['slip']['tmp_name'], $targetPath)) {
    header("Location: " . FRONTEND_URL . "/pages/buyer/payment.php?intent_id=" . $intent_id . "&error=upload_failed");
    exit;
}

// path เก็บลง DB
$slip_path = rtrim(UPLOAD_BASE_URL_PATH, '/\\') . "/slips/" . $filename;

$conn->begin_transaction();
try {
    $sql = "
        INSERT INTO payments (
            user_id, address_id, product_id, variant_id,
            items_json, address_json,
            subtotal, shipping_fee, grand_total,
            amount, slip_path, transfer_date, transfer_time,
            mode, status, created_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, 'pending', NOW()
        )
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("prepare failed: " . $conn->error);

    $address_id = isset($intent['address_id']) ? (int)$intent['address_id'] : 0;
    $product_id = isset($intent['product_id']) ? (int)$intent['product_id'] : 0;
    $variant_id = isset($intent['variant_id']) ? (int)$intent['variant_id'] : 0;

    $items_json   = (string)$intent['items_json'];
    $address_json = $intent['address_json'] ?? '';
    $amount       = (float)$intent['amount'];
    $mode         = (string)$intent['mode'];

    $items = json_decode($items_json, true) ?: [];
    $subtotal = 0.0;

    foreach ($items as $it) {
        $qty = max(1, (int)($it['quantity'] ?? 1));
        $unit = (float)($it['unit_price'] ?? ($it['price'] ?? 0));
        $subtotal += $qty * $unit;
    }

    $subtotal = round($subtotal, 2);
    $grandTotal  = (float)$amount;
    $shippingFee = max(0.0, round($grandTotal - $subtotal, 2));

    $transfer_date = trim((string)($_POST['transfer_date'] ?? ''));
    $transfer_time = trim((string)($_POST['transfer_time'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $transfer_date)) {
        throw new Exception("รูปแบบวันที่โอนเงินไม่ถูกต้อง (ต้องเป็น YYYY-MM-DD)");
    }

    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $transfer_time)) {
        throw new Exception("รูปแบบเวลาที่โอนเงินไม่ถูกต้อง (ต้องเป็น HH:MM หรือ HH:MM:SS)");
    }

    if (strlen($transfer_time) === 5) {
        // เพิ่มวินาทีเป็น :00 ถ้าไม่มี
        $transfer_time .= ':00';
    }

    $stmt->bind_param(
        "iiiissddddssss",
        $user_id,
        $address_id,
        $product_id,
        $variant_id,
        $items_json,
        $address_json,
        $subtotal,
        $shippingFee,
        $grandTotal,
        $amount,
        $slip_path,
        $transfer_date,
        $transfer_time,
        $mode
    );

    if (!$stmt->execute()) throw new Exception("execute failed: " . $stmt->error);

    $payment_id = (int)$stmt->insert_id;
    if ($payment_id <= 0) throw new Exception("insert_id invalid");

    // mark intent converted
    if (!markIntentConverted($conn, $intent_id, $payment_id)) {
        throw new Exception("markIntentConverted failed");
    }

    $conn->commit();

    if (!notifyAdminNewSlipOnce($conn, $payment_id)) {
        throw new Exception("Failed to notify admin");
    }

    // ส่ง JSON response แทนการ redirect เพื่อให้ fetch ไม่ตัวแปร
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'payment_id' => $payment_id,
        'redirect_url' => FRONTEND_URL . "/pages/buyer/confirm_payment.php?payment_id=" . $payment_id
    ]);
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());

    // ส่ง JSON error response
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'redirect_url' => FRONTEND_URL . "/pages/buyer/payment.php?intent_id=" . $intent_id . "&error=" . urlencode($e->getMessage())
    ]);
    exit;
}

