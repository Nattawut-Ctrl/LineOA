<?php
session_start();
// ป้องกัน browser cache (กันกด Back แล้วเห็นหน้าเก่า)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once dirname(__DIR__, 3) . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . FRONTEND_URL . "/pages/users/line-entry.php");
    exit;
}

require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/userService.php';
require_once SERVICES_PATH . '/slipService.php';
require_once FRONTEND_PATH . '/services/AddressService.php';
require_once SERVICES_PATH . '/paymentIntentService.php';

$conn = connectDBWithLog();
$user_id = (int)$_SESSION['user_id'];

// ✅ ดึงข้อมูลผู้ใช้และที่อยู่
$user = getUserById($conn, $user_id);
$addresses = getUserAddresses($conn, $user_id);

// หา default address ถ้ามี
$default_address = null;
foreach ($addresses as $addr) {
    if ((int)$addr['is_default'] === 1) {
        $default_address = $addr;
        break;
    }
}

// ถ้าไม่มี default ให้ใช้ตัวแรก
if (!$default_address && !empty($addresses)) {
    $default_address = $addresses[0];
}

// ถ้าไม่มีที่อยู่เลย ให้เด้งกลับ
if (empty($addresses)) {
    header("Location: " . FRONTEND_URL . "/pages/buyer/Buyer.php?error=no_address");
    exit;
}

// ถ้าไม่ส่ง mode มา ให้ถือเป็น single (ซื้อทีละชิ้น)
$mode = $_POST['mode'] ?? ($_GET['mode'] ?? 'single');

$items = [];
$total = 0;
$intent_id = null;
$reservation_error = null;
$totalItems = 0;

$intent_id = (int)($_GET['intent_id'] ?? 0);
$current_intent = null;

// allow skipping redirect to existing active intent when user intentionally starts a new purchase
$force_new = isset($_REQUEST['force_new']) && ((string)$_REQUEST['force_new'] === '1' || $_REQUEST['force_new'] === 'true');

$session_intent_id = (int)($_SESSION['active_intent_id'] ?? 0);
if ($intent_id <= 0 && $session_intent_id > 0 && !$force_new) {
    $sess_intent = getIntentById($conn, $session_intent_id);
    if (
        $sess_intent && (int)$sess_intent['user_id'] === $user_id
        && ($sess_intent['status'] ?? '') === 'active'
        && !isIntentExpired($sess_intent)
    ) {
        header("Location: " . FRONTEND_URL . "/pages/buyer/payment.php?intent_id=" . (int)$session_intent_id);
        exit;
    }
    unset($_SESSION['active_intent_id']);
}

// If user forced a new intent, clear session active intent so it won't interfere later
if ($force_new) {
    unset($_SESSION['active_intent_id']);
}

if ($intent_id > 0) {
    $current_intent = getIntentById($conn, $intent_id);
    if (!$current_intent || (int)$current_intent['user_id'] !== $user_id) {
        $intent_id = 0;
        $current_intent = null;
    } else {
        // ✅ ใช้ข้อมูลจาก intent โดยตรง (รองรับ refresh)
        $items = json_decode($current_intent['items_json'] ?? '[]', true) ?: [];
        // $total = (float)($current_intent['amount'] ?? 0);
        $mode  = $current_intent['mode'] ?? $mode;

        $total = 0.0;
        foreach ($items as $it) {
            $qty = (int)($it['quantity'] ?? 0);
            $price = (float)($it['price'] ?? 0);
            $line = isset($it['line_total']) ? (float)$it['line_total'] : ($qty * $price);

            $total += $line;
        }
        $total = round($total, 2);

        if (($current_intent['status'] ?? '') === 'converted' && !empty($current_intent['converted_payment_id'])) {
            unset($_SESSION['active_intent_id']);
            header("Location: " . FRONTEND_URL . "/pages/buyer/confirm_payment.php?payment_id=" . (int)$current_intent['converted_payment_id']);
            exit;
        }

        if (($current_intent['status'] ?? '') !== 'active') {
            unset($_SESSION['active_intent_id']);
            header("Location: " . FRONTEND_URL . "/pages/buyer/Buyer.php");
            exit;
        }
    }
}


if (!$current_intent) {
    if ($mode === 'cart') {

        $product_ids = $_POST['product_id'] ?? [];
        $variant_ids = $_POST['variant_id'] ?? [];
        $quantities  = $_POST['quantity'] ?? [];

        foreach ($product_ids as $i => $pid) {
            $pid = (int)$pid;
            $vid = (int)($variant_ids[$i] ?? 0);
            $qty = (int)($quantities[$i] ?? 1);
            if ($qty < 1) $qty = 1;

            // ---------- ดึงข้อมูลจริงจาก DB ----------
            if ($vid > 0) {
                $sql = "
                SELECT pv.id, pv.price, pv.stock,
                       pv.variant_name,
                       p.name AS product_name
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                WHERE pv.id = ? AND p.id = ?
            ";
                $res = db_query($conn, $sql, [$vid, $pid], "ii");
            } else {
                $sql = "
                SELECT id, price, stock,
                       name AS product_name
                FROM products
                WHERE id = ?
            ";
                $res = db_query($conn, $sql, [$pid], "i");
            }
            $row = $res ? $res->fetch_assoc() : null;
            if (!$row) continue;

            $stock        = (int)($row['stock'] ?? 0);
            $price        = (float)$row['price'];
            $product_name = $row['product_name'];
            $variant_name = $vid > 0 ? ($row['variant_name'] ?? null) : null;

            // ---------- เช็ค stock ----------
            if ($stock > 0 && $qty > $stock) {
                $qty = $stock;
                if ($qty <= 0) continue;
            }

            $line_total = $price * $qty;
            $total     += $line_total;

            $items[] = [
                'product_id'   => $pid,
                'variant_id'   => $vid,
                'product_name' => $product_name,
                'variant_name' => $variant_name,
                'quantity'     => $qty,
                'price'        => $price,
                'line_total'   => $line_total,
            ];
        }
    } else {
        // ------------------- ✅ โหมด single (ซื้อทีละชิ้น) -------------------
        // รับจาก POST เป็นหลัก ถ้าเข้าตรงก็รองรับ GET ให้ด้วย
        $pid = (int)($_POST['product_id'] ?? ($_GET['product_id'] ?? 0));
        $vid = (int)($_POST['variant_id'] ?? ($_GET['variant_id'] ?? 0));
        $qty = (int)($_POST['quantity'] ?? ($_GET['quantity'] ?? 1));
        if ($qty < 1) $qty = 1;

        if ($pid > 0) {

            if ($vid > 0) {
                $sql = "
                SELECT pv.id, pv.price, pv.stock,
                       pv.variant_name,

                       p.name AS product_name
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                WHERE pv.id = ? AND p.id = ?
            ";
                $res = db_query($conn, $sql, [$vid, $pid], "ii");
            } else {
                $sql = "
                SELECT id, price, stock,
                       name AS product_name
                FROM products
                WHERE id = ?
            ";
                $res = db_query($conn, $sql, [$pid], "i");
            }

            $row = $res ? $res->fetch_assoc() : null;

            if ($row) {
                $stock        = (int)($row['stock'] ?? 0);
                $price        = (float)$row['price'];
                $product_name = $row['product_name'];
                $variant_name = $vid > 0 ? ($row['variant_name'] ?? null) : null;

                if ($stock > 0 && $qty > $stock) {
                    $qty = $stock;
                }
                if ($qty > 0) {
                    $line_total = $price * $qty;
                    $total = $line_total;

                    $items[] = [
                        'product_id'   => $pid,
                        'variant_id'   => $vid,
                        'product_name' => $product_name,
                        'variant_name' => $variant_name,
                        'quantity'     => $qty,
                        'price'        => $price,
                        'line_total'   => $line_total,
                    ];
                }
            }
        }
    }
}

// ถ้าไม่มี items เลย ให้เด้งกลับร้าน (กัน error)
if (empty($items)) {
    header("Location: " . FRONTEND_URL . "/pages/buyer/Buyer.php?error=empty_payment");
    exit;
}

function calculateShippingFee($totalItems): float
{
    if ($totalItems <= 2) {
        return 60.00;
    }
    return 60.00 + (($totalItems - 2) * 10.00);
}

$totalItems = 0;
foreach ($items as $it) {
    $totalItems += (int)$it['quantity'];
}

$shippingFee = calculateShippingFee($totalItems);
$total = round($total, 2);
$grandTotal = round($total + $shippingFee, 2);

if ($intent_id <= 0) {
    $conn->begin_transaction();

    try {
        $address_json = null;
        if ($default_address) {
            $address_json = json_encode($default_address, JSON_UNESCAPED_UNICODE);
        }

        if ($mode === 'cart') {
            $items_json = json_encode($items, JSON_UNESCAPED_UNICODE);
            $product_id = null;
            $variant_id = null;
        } else {
            $item = $items[0];
            $items_json = json_encode($items, JSON_UNESCAPED_UNICODE);
            $product_id = $item['product_id'];
            $variant_id = $item['variant_id'];
        }

        $intent_id = createPaymentIntent($conn, [
            'user_id'      => $user_id,
            'mode'         => $mode,
            'product_id'   => $product_id,
            'variant_id'   => $variant_id,
            'items_json'   => $items_json,
            'amount'       => $grandTotal,
            'address_id'   => (int)($default_address['id'] ?? 0),
            'address_json' => $address_json,
        ]);


        if ($intent_id <= 0) {
            throw new Exception('ไม่สามารถสร้างรายการชำระเงินได้');
        }

        if (!reserveStockForIntent($conn, $intent_id)) {
            throw new Exception('สินค้าบางรายการสต็อกไม่เพียงพอ หรือถูกจองเต็มแล้ว');
        }

        $conn->commit();

        $_SESSION['active_intent_id'] = (int)$intent_id;
        header("Location: " . FRONTEND_URL . "/pages/buyer/payment.php?intent_id=" . (int)$intent_id);
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        $reservation_error = $e->getMessage();
        $intent_id = null;
    }
}

$time_remaining_minutes = 0;
if ($intent_id) {
    $current_intent = getIntentById($conn, $intent_id);
    if ($current_intent) {
        $intentAmount = (float)($current_intent['amount'] ?? 0);

        // ถ้า amount ใน intent ยังเป็นยอดเก่า (ไม่รวมค่าส่ง) ให้ sync ให้ตรงกับ grandTotal
        if (abs($intentAmount - $grandTotal) > 0.01) {
            $stmt = $conn->prepare("UPDATE payment_intents SET amount = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $grandTotal, $intent_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    if ($current_intent && isIntentExpired($current_intent)) {
        // หมดเวลา จึงคืนสต็อก
        $conn->begin_transaction();
        if (expireAndReleaseStockForIntent($conn, $intent_id)) {
            $conn->commit();
            $reservation_error = 'หมดเวลาการจองแล้ว (30 นาที) สต็อกได้ถูกคืนเรียบร้อยแล้ว';
            $intent_id = null;
            unset($_SESSION['active_intent_id']);
        } else {
            $conn->rollback();
        }
    } else if ($current_intent) {
        $time_remaining_minutes = getIntentTimeRemaining($current_intent);
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน | Line-Shop</title>
    <?php require_once SHARED_PARTIALS_PATH . '/bootstrap.php'; ?>
    <?php require_once SHARED_PARTIALS_PATH . '/sweetalert.php'; ?>

    <style>
        .address-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .address-card:hover {
            background-color: rgba(238, 77, 45, 0.08) !important;
            border-color: rgba(238, 77, 45, 0.3) !important;
        }

        .bg-danger-light {
            background-color: rgba(238, 77, 45, 0.12) !important;
        }

        .address-radio {
            accent-color: #ee4d2d;
            cursor: pointer;
            width: 20px;
            height: 20px;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="Buyer.php">Line-Shop</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="card shadow-sm mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <h4 class="text-center mb-4">💳 ชำระเงิน</h4>

                <?php if ($reservation_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>⚠️ เกิดข้อผิดพลาด</strong><br>
                        <?= htmlspecialchars($reservation_error) ?>
                        <hr>
                        <a href="<?= FRONTEND_URL ?>/pages/buyer/Buyer.php" class="btn btn-sm btn-outline-danger">กลับไปยังร้านค้า</a>
                    </div>
                <?php endif; ?>
                <?php if ($intent_id && $time_remaining_minutes > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>⏱️ เวลาในการชำระเงิน</strong><br>
                        เหลือเวลา <strong><span id="payCountdown"><?= (int)$time_remaining_minutes ?>:00</span></strong><br>
                        <small>หลังจากหมดเวลา ระบบจะปิดรายการอัตโนมัติ</small>
                    </div>
                <?php endif; ?>

                <div class="card mb-4 border-0 bg-light">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-semibold"><i class="bi bi-geo-alt-fill text-danger me-2"></i>ที่อยู่จัดส่ง</h6>
                            <a href="buyer_address.php" class="btn btn-link btn-sm p-0 text-decoration-none small">เปลี่ยนที่อยู่</a>
                        </div>

                        <div id="addressContainer" class="mb-3">
                            <!-- Address list will be generated here -->
                        </div>

                        <a href="buyer_address_form.php" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มที่อยู่ใหม่
                        </a>
                    </div>
                </div>

                <!-- Product Items Table -->
                <div class="mb-4">
                    <h6 class="fw-semibold mb-2">📦 รายการสินค้า</h6>
                    <div class="alert alert-info mb-3">
                        ค่าส่งจะคำนวณตามจำนวนสินค้าที่สั่งซื้อ <strong>สินค้า 1-2 ชิ้น ค่าส่ง 60 บาท</strong><br>
                        สินค้าชิ้นต่อไป <strong>คิดเพิ่มชิ้นละ 10 บาท</strong> (เช่น 3 ชิ้น = 70 บาท, 4 ชิ้น = 80 บาท)
                    </div>
                    <table class="table table-bordered text-center align-middle small">
                        <tr>
                            <th>สินค้า</th>
                            <th>จำนวน</th>
                            <th>ราคาต่อชิ้น</th>
                            <th>รวมย่อย</th>
                        </tr>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($it['product_name']) ?>
                                    <?php if (!empty($it['variant_name'])): ?>
                                        <br><small class="text-muted">(<?= htmlspecialchars($it['variant_name']) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= $it['quantity'] ?></td>
                                <td><?= number_format($it['price'], 2) ?> บาท</td>
                                <td><?= number_format($it['line_total'], 2) ?> บาท</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-warning">
                            <th colspan="3">ราคาสินค้า</th>
                            <th><strong><?= number_format($total, 2) ?> บาท</strong></th>
                        </tr>
                        <tr>
                            <th colspan="3">
                                <i class="bi bi-truck text-info me-2"></i>ค่าส่ง
                                <small class="text-muted">(<span id="totalItemsDisplay"><?= $totalItems ?></span> ชิ้น)</small>
                            </th>
                            <th><strong id="shippingDisplay"><?= number_format($shippingFee, 2) ?> บาท</strong></th>
                        </tr>
                        <tr class="table-success">
                            <th colspan="3">รวมทั้งหมด</th>
                            <th><strong id="grandTotalDisplay"><?= number_format($grandTotal, 2) ?> บาท</strong></th>
                        </tr>
                    </table>
                </div>
                <div class="text-center my-4">
                    <h6 class="fw-bold mb-2">📱 สแกนเพื่อชำระเงิน</h6>
                    <img src="<?= rtrim(SHARED_ASSETS_URL, '/') ?>/img/qr-payment.png" class="img-fluid rounded border" style="max-width:220px;">
                </div>
                <div class="text-center mb-4">
                    <div class="alert alert-success mb-0">
                        ชื่อบัญชี: <strong>ร้านค้า Line-Shop</strong><br>
                        เลขที่บัญชี: <strong>123-4-56789-0</strong> (ธนาคารกรุงเทพ)
                    </div>

                </div>

                <form action="<?= FRONTEND_URL ?>/actions/buyer/upload_slip.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
                    <input type="hidden" name="address_id" id="selectedAddressId" value="<?= isset($default_address) ? (int)$default_address['id'] : '' ?>">    

                    <?php if ($mode === 'cart'): ?>
                        <?php foreach ($items as $it): ?>
                            <input type="hidden" name="product_id[]" value="<?= $it['product_id'] ?>">
                            <input type="hidden" name="variant_id[]" value="<?= $it['variant_id'] ?>">
                            <input type="hidden" name="product_name[]" value="<?= htmlspecialchars($it['product_name']) ?>">
                            <input type="hidden" name="variant_name[]" value="<?= htmlspecialchars($it['variant_name'] ?? '') ?>">
                            <input type="hidden" name="quantity[]" value="<?= $it['quantity'] ?>">
                            <input type="hidden" name="price[]" value="<?= $it['price'] ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php $it = $items[0]; ?>
                        <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                        <input type="hidden" name="variant_id" value="<?= $it['variant_id'] ?>">
                        <input type="hidden" name="product_name" value="<?= htmlspecialchars($it['product_name']) ?>">
                        <input type="hidden" name="variant_name" value="<?= htmlspecialchars($it['variant_name'] ?? '') ?>">
                        <input type="hidden" name="quantity" value="<?= $it['quantity'] ?>">
                        <input type="hidden" name="price" value="<?= $it['price'] ?>">
                    <?php endif; ?>

                    <input type="hidden" name="total" value="<?= $total ?>">
                    <input type="hidden" name="shipping_fee" value="<?= $shippingFee ?>">
                    <input type="hidden" name="grand_total" value="<?= $grandTotal ?>">

                    <div class="mb-3">
                        <label class="form-label">📤 อัปโหลดสลิป</label>
                        <input type="file" id="slipInput" class="form-control" name="slip" accept="image/*" required>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">📅 วันที่โอน (ตามสลิปที่อัปโหลด)</label>
                        <div class="row g-2">
                            <div class="col-auto flex-grow-1">
                                <label class="form-label small mb-1">วัน</label>
                                <select name="transfer_day" id="transfer_day" class="form-select form-select-sm" required>
                                    <option value="">-- เลือกวัน --</option>
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?= $d ?>"><?= $d ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-auto flex-grow-1">
                                <label class="form-label small mb-1">เดือน</label>
                                <select name="transfer_month" id="transfer_month" class="form-select form-select-sm" required>
                                    <option value="">-- เลือกเดือน --</option>
                                    <option value="1">ม.ค.</option>
                                    <option value="2">ก.พ.</option>
                                    <option value="3">มี.ค.</option>
                                    <option value="4">เม.ย.</option>
                                    <option value="5">พ.ค.</option>
                                    <option value="6">มิ.ย.</option>
                                    <option value="7">ก.ค.</option>
                                    <option value="8">ส.ค.</option>
                                    <option value="9">ก.ย.</option>
                                    <option value="10">ต.ค.</option>
                                    <option value="11">พ.ย.</option>
                                    <option value="12">ธ.ค.</option>
                                </select>
                            </div>
                            <div class="col-auto flex-grow-1">
                                <label class="form-label small mb-1">ปี (พ.ศ.)</label>
                                <select name="transfer_year" id="transfer_year" class="form-select form-select-sm" required>
                                    <option value="">-- เลือกปี --</option>
                                    <?php
                                    $currentThaiYear = date('Y') + 543;
                                    for ($y = $currentThaiYear; $y >= $currentThaiYear - 6; $y--):
                                    ?>
                                        <option value="<?= $y - 543 ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">🕐 เวลาโอน (ตามสลิปที่อัปโหลด)</label>
                        <div class="row g-2">
                            <div class="col-auto flex-grow-1">
                                <label class="form-label small mb-1">ชั่วโมง</label>
                                <select name="transfer_hour" id="transfer_hour" class="form-select form-select-sm" required>
                                    <option value="">-- เลือกชั่วโมง --</option>
                                    <?php for ($h = 0; $h < 24; $h++): ?>
                                        <option value="<?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-auto flex-grow-1">
                                <label class="form-label small mb-1">นาที</label>
                                <select name="transfer_minute" id="transfer_minute" class="form-select form-select-sm" required>
                                    <option value="">-- เลือกนาที --</option>
                                    <?php for ($m = 0; $m <= 59; $m++): ?>
                                        <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-danger w-100" id="submitBtn">ยืนยันการชำระเงิน</button>
                </form>

            </div>
        </div>
    </div>

    <?php $conn->close(); ?>
    <script>
        // ✅ ข้อมูลที่อยู่ทั้งหมด
        const addresses = <?= json_encode($addresses, JSON_UNESCAPED_UNICODE) ?>;
        const defaultAddressId = <?= isset($default_address) ? (int)$default_address['id'] : 'null' ?>;
        const pageIntentId = <?= $intent_id ? (int)$intent_id : 'null' ?>;
        const pagePaymentId = <?= (int)($_GET['payment_id'] ?? 0) ?: 'null' ?>;
        const baseAmount = <?= $total ?>;

        // ✅ ฟังก์ชันคำนวณค่าส่ง
        function calculateShippingFee(totalItems) {
            if (totalItems <= 2) {
                return 60.00;
            }
            return 60.00 + ((totalItems - 2) * 10.00);
        }

        // ✅ ฟังก์ชันอัปเดตราคารวม
        function updateTotalPrice() {
            const totalItems = <?= $totalItems ?>;
            const shippingFee = calculateShippingFee(totalItems);
            const grandTotal = baseAmount + shippingFee;

            document.getElementById('totalItemsDisplay').textContent = totalItems;
            document.getElementById('shippingDisplay').textContent = shippingFee.toFixed(2) + ' บาท';
            document.getElementById('grandTotalDisplay').textContent = grandTotal.toFixed(2) + ' บาท';

            // อัปเดต hidden fields
            document.querySelector('input[name="shipping_fee"]').value = shippingFee.toFixed(2);
            document.querySelector('input[name="grand_total"]').value = grandTotal.toFixed(2);
        }

        function formatPhone(phone) {
            const digits = phone.replace(/\D/g, '');
            if (/^(\d{3})(\d{3})(\d{4})$/.test(digits)) {
                const m = digits.match(/^(\d{3})(\d{3})(\d{4})$/);
                return `${m[1]}-${m[2]}-${m[3]}`;
            }
            return phone;
        }

        function renderAddressList() {
            const container = document.getElementById('addressContainer');
            container.innerHTML = '';

            addresses.forEach(addr => {
                const isDefault = defaultAddressId && Number(addr.id) === Number(defaultAddressId);
                const isSelected = document.getElementById('selectedAddressId').value == addr.id;

                const html = `
                    <div class="address-card mb-2 p-3 border rounded-2 cursor-pointer ${isSelected ? 'border-danger border-2 bg-danger-light shadow-sm' : 'border-secondary-subtle'}" onclick="selectAddress(${addr.id})">
                        <div class="d-flex align-items-start gap-3">
                            <input type="radio" name="address_select" value="${addr.id}" 
                                ${isSelected ? 'checked' : ''} 
                                class="mt-2 address-radio"
                                onchange="selectAddress(${addr.id})">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold mb-1">
                                            ${addr.full_name}
                                            <span class="text-muted fw-normal ms-2 small">${formatPhone(addr.phone)}</span>
                                        </div>
                                        <div class="text-muted small lh-lg">
                                            เลขที่ ${addr.address_line}, ตำบล${addr.subdistrict}, อำเภอ${addr.district}, จังหวัด${addr.province} ${addr.postal_code}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        ${addr.label ? `<span class="badge text-bg-secondary small me-2">${addr.label}</span>` : ''}
                                        ${Number(addr.is_default) === 1 ? `<span class="badge text-bg-danger small">ค่าเริ่มต้น</span>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                const div = document.createElement('div');
                div.innerHTML = html;
                container.appendChild(div.firstElementChild);
            });
        }

        function selectAddress(addressId) {
            document.getElementById('selectedAddressId').value = addressId;
            renderAddressList();

            // ✅ SweetAlert notification
            const selectedAddr = addresses.find(a => a.id == addressId);
            if (selectedAddr) {
                Swal.fire({
                    title: 'เลือกที่อยู่แล้ว',
                    text: `${selectedAddr.full_name}\n${selectedAddr.address_line}`,
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        }

        // Render on page load
        document.addEventListener('DOMContentLoaded', () => {
            renderAddressList();
            updateTotalPrice();
        });

        // ✅ Submit form to upload slip with existing payment_id
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // ✅ สร้าง transfer_date และ transfer_time จากค่า dropdown
                const day = document.getElementById('transfer_day').value;
                const month = document.getElementById('transfer_month').value;
                const year = document.getElementById('transfer_year').value;
                const hour = document.getElementById('transfer_hour').value;
                const minute = document.getElementById('transfer_minute').value;

                if (!day || !month || !year || !hour || !minute) {
                    Swal.fire({
                        title: 'ยังไม่เสร็จ',
                        text: 'กรุณาเลือกวันที่และเวลาให้ครบถ้วน',
                        icon: 'warning',
                        confirmButtonText: 'ตกลง'
                    });
                    return false;
                }

                // สร้าง date string ในรูปแบบ YYYY-MM-DD
                const transferDate = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const transferTime = `${hour}:${minute}`;

                // เพิ่ม hidden inputs สำหรับ transfer_date และ transfer_time
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'transfer_date';
                dateInput.value = transferDate;
                form.appendChild(dateInput);

                const timeInput = document.createElement('input');
                timeInput.type = 'hidden';
                timeInput.name = 'transfer_time';
                timeInput.value = transferTime;
                form.appendChild(timeInput);

                if (!pageIntentId) {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถหา intent ID ได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return false;
                }

                const slipFile = form.querySelector('input[name="slip"]');

                if (!transferDate || !transferTime) {
                    Swal.fire({
                        title: 'กรุณากรอกวันและเวลาโอนเงิน',
                        icon: 'warning',
                        confirmButtonText: 'ตกลง'
                    });
                    return false;
                }

                if (!slipFile.files || slipFile.files.length === 0) {
                    Swal.fire({
                        title: 'กรุณาเลือกไฟล์สลิป',
                        icon: 'warning',
                        confirmButtonText: 'ตกลง'
                    });
                    return false;
                }

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>กำลังประมวลผล...';
                // ลบ input เก่าออกเพื่อป้องกันการซ้ำซ้อน
                form.querySelectorAll('input[name="transfer_date"], input[name="transfer_time"]').forEach(el => el.remove());

                const formData = new FormData(form);
                formData.append('intent_id', pageIntentId);
                formData.append('transfer_date', transferDate);
                formData.append('transfer_time', transferTime);

                try {
                    const response = await fetch('<?= FRONTEND_URL ?>/actions/buyer/upload_slip.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }

                    if (response.redirected) {
                        window.location.replace(response.url);
                    } else {
                        const raw = await response.text();

                        if (raw.includes('error') || raw.includes('Error')) {
                            throw new Error(raw);
                        }

                        // พยายามอ่าน payment_id จาก response (กรณีไม่ได้ redirect)
                        let pid = null;

                        try {
                            const data = JSON.parse(raw);
                            pid = data.payment_id ?? data.id ?? null;
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                                return;
                            }
                        } catch (e) {
                            // not json
                        }

                        if (!pid) {
                            const m = raw.match(/payment_id\s*=\s*(\d+)/) || raw.match(/"payment_id"\s*:\s*(\d+)/);
                            if (m) pid = m[1];
                        }

                        if (pid) {
                            window.location.href = '<?= FRONTEND_URL ?>/pages/buyer/confirm_payment.php?payment_id=' + pid;
                        } else {
                            // fallback
                            window.location.href = '<?= FRONTEND_URL ?>/pages/buyer/order-history.php';
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด',
                        text: error.message || 'ไม่สามารถอัปโหลดสลิปได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'ยืนยันการชำระเงิน';
                }
            });
        }
    </script>

    <script>
        (function() {
            const intentId = <?= (int)($intent_id ?? 0) ?>;
            const countdownEl = document.getElementById('payCountdown');
            const submitBtn = document.getElementById('submitBtn');

            // expiresAt ของ server (seconds)
            const expiresAtTs = <?= (int)strtotime($current_intent['expires_at'] ?? 'now') ?>;
            const serverNowTs = <?= time() ?>;

            if (!intentId || !expiresAtTs) return;

            // ทำให้ client "เดินตามเวลา server"
            const clientNowTs = Math.floor(Date.now() / 1000);
            const offsetTs = serverNowTs - clientNowTs;

            let fired = false;

            function fmtFromSeconds(sec) {
                sec = Math.max(0, Math.floor(sec));
                const m = Math.floor(sec / 60);
                const s = sec % 60;
                return `${m}:${String(s).padStart(2, '0')}`;
            }

            function disablePaymentUI() {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'หมดเวลาการชำระเงิน';
                }
                // ปิด input ที่เกี่ยวข้องกันการส่งต่อ
                document.querySelectorAll('input, button, select, textarea').forEach(el => {
                    if (el.id === 'submitBtn') return;
                    // ยังให้กดลิงก์/ปุ่มอื่นได้ตามต้องการ ถ้าคุณอยากล็อกทั้งหมดให้เอาเงื่อนไขออก
                });
            }

            function fireExpire() {
                if (fired) return;
                fired = true;

                disablePaymentUI();

                const url = '<?= FRONTEND_URL ?>/actions/buyer/expire_intent.php';

                // sendBeacon (best-effort)
                if (navigator.sendBeacon) {
                    try {
                        const body = new URLSearchParams({
                            intent_id: String(intentId)
                        });
                        navigator.sendBeacon(url, body);
                    } catch (e) {}
                }

                // fetch ซ้ำเพื่อความชัวร์
                const fd = new FormData();
                fd.append('intent_id', String(intentId));

                fetch(url, {
                        method: 'POST',
                        body: fd,
                        keepalive: true
                    })
                    .catch(() => {})
                    .finally(() => {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'info',
                                title: 'หมดเวลาการชำระเงิน',
                                text: 'กรุณาทำรายการใหม่'
                            }).then(() => {
                                location.href = '<?= FRONTEND_URL ?>/pages/buyer/order-history.php';
                            });
                        } else {
                            alert('หมดเวลาการชำระเงิน กรุณาทำรายการใหม่');
                            location.href = '<?= FRONTEND_URL ?>/pages/buyer/order-history.php';
                        }
                    });
            }

            function tick() {
                const nowTs = Math.floor(Date.now() / 1000) + offsetTs;
                const remainingSec = expiresAtTs - nowTs;

                if (countdownEl) countdownEl.textContent = fmtFromSeconds(remainingSec);

                if (remainingSec <= 0) {
                    fireExpire();
                    return;
                }
                setTimeout(tick, 250);
            }

            tick();
        })();
    </script>

</body>

</html>