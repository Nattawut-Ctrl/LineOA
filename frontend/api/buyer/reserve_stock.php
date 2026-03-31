<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/slipService.php';

header('Content-Type: application/json');

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$mode = $_POST['mode'] ?? 'single';
$address_id = (int)($_POST['address_id'] ?? 0);
$transfer_date = $_POST['transfer_date'] ?? null;
$transfer_time = $_POST['transfer_time'] ?? null;

$conn = connectDBWithLog();

// ✅ ดึงข้อมูลที่อยู่
$address_json = null;
if ($address_id > 0) {
    $sql = "SELECT id, full_name, phone, address_line, subdistrict, district, province, postal_code, label 
            FROM user_addresses WHERE id = ? AND user_id = ? AND deleted_at IS NULL";
    $res = db_query($conn, $sql, [$address_id, $user_id], "ii");
    if ($res && $row = $res->fetch_assoc()) {
        $address_json = json_encode($row, JSON_UNESCAPED_UNICODE);
    }
}

if (!$address_json) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid address']);
    exit;
}

$conn->begin_transaction();

try {
    if ($mode === 'cart') {
        $product_ids = $_POST['product_id'] ?? [];
        $variant_ids = $_POST['variant_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['price'] ?? [];

        $items = [];
        $total = 0;

        foreach ($product_ids as $i => $pid) {
            $qty = (int)($quantities[$i] ?? 1);
            if ($qty <= 0) $qty = 1;

            $unitPrice = (float)($prices[$i] ?? 0);
            $lineTotal = $unitPrice * $qty;

            $items[] = [
                'product_id' => (int)$pid,
                'variant_id' => (int)$variant_ids[$i],
                'name' => $_POST['product_name'][$i] ?? '',
                'variant_name' => $_POST['variant_name'][$i] ?? '',
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'quantity' => $qty
            ];
            $total += $lineTotal;
        }

        $items_json = json_encode($items, JSON_UNESCAPED_UNICODE);

        $payment_id = createPayment($conn, [
            'user_id' => $user_id,
            'address_id' => $address_id,
            'product_id' => null,
            'variant_id' => null,
            'items_json' => $items_json,
            'address_json' => $address_json,
            'amount' => $total,
            'slip_path' => '', // ยังไม่มี slip ในขั้นนี้
            'mode' => 'cart',
            'transfer_date' => $transfer_date,
            'transfer_time' => $transfer_time,
        ]);
    } else {
        // single mode
        $product_id = (int)($_POST['product_id'] ?? 0);
        $variant_id = (int)($_POST['variant_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $amount = (float)($_POST['total'] ?? 0);

        $unitPrice = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : 0;
        if ($unitPrice <= 0) {
            $unitPrice = ($quantity > 0) ? ($amount / $quantity) : $amount;
        }
        $lineTotal = $unitPrice * max(1, $quantity);
        $amount = $lineTotal;

        $item = [
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'name' => $_POST['product_name'] ?? '',
            'variant_name' => $_POST['variant_name'] ?? '',
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'quantity' => $quantity
        ];

        $payment_id = createPayment($conn, [
            'user_id' => $user_id,
            'address_id' => $address_id,
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'items_json' => json_encode([$item], JSON_UNESCAPED_UNICODE),
            'address_json' => $address_json,
            'amount' => $lineTotal,
            'slip_path' => '',
            'mode' => 'single',
            'transfer_date' => $transfer_date,
            'transfer_time' => $transfer_time,
        ]);
    }

    if ($payment_id <= 0) {
        throw new Exception('Payment creation failed');
    }

    // จองสต็อก
    if (!reserveStockForPayment($conn, $payment_id)) {
        throw new Exception('Stock reservation failed: insufficient stock or already reserved');
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'payment_id' => $payment_id,
        'message' => 'Stock reserved successfully'
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
