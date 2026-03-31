<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once UTILS_PATH . '/image_helper.php';
require_once UTILS_PATH . '/product_image_helper.php';
require_once SERVICES_PATH . '/userService.php';

require_once SERVICES_PATH . '/paymentIntentService.php';
require_once UTILS_PATH . '/order_history_helper.php';


$conn    = connectDBWithLog();
$user_id = require_user_id();


// ✅ กัน intent หมดเวลาให้ถูกอัปเดตใน DB
cleanupExpiredIntentsForUser($conn, $user_id);

$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/pages/users/line-entry.php?from=register");
    exit;
}

// ───────────────────────── Config: จำนวน card ล่าสุด ─────────────────────────
$MAX_PENDING_CARDS = 10;  // รอชำระเงิน
$MAX_SLIP_CARDS    = 10;  // ส่งสลิปแล้ว

// ───────────────────────── ดึงออเดอร์จาก payments (ทั้งหมด) ─────────────────────────
$sql = "SELECT 
            p.*,
            pr.name AS product_name, 
            pr.image AS product_image, 
            v.variant_name, 
            v.image AS variant_image
        FROM payments p
        LEFT JOIN products pr ON p.product_id = pr.id
        LEFT JOIN product_variants v ON p.variant_id = v.id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT 100";

$res = db_query($conn, $sql, [$user_id], "i");
$allOrders = [];
while ($row = $res->fetch_assoc()) {
    $allOrders[] = $row;
}

// จำกัดล่าสุดในฝั่ง helper (orders ถูก ORDER BY DESC มาแล้ว)
$allOrders = limitLatest($allOrders, 100);

// แยก orders ตามสถานะ
$ordersApproved = array_filter($allOrders, fn($o) => ($o['status'] ?? '') === 'approved');
$ordersPending  = array_filter($allOrders, fn($o) => ($o['status'] ?? '') === 'pending');
$ordersRejected = array_filter($allOrders, fn($o) => ($o['status'] ?? '') === 'rejected');

// จำกัด 10 รายการต่อสถานะ
$ordersApproved = array_slice($ordersApproved, 0, $MAX_SLIP_CARDS);
$ordersPending  = array_slice($ordersPending, 0, $MAX_SLIP_CARDS);
$ordersRejected = array_slice($ordersRejected, 0, $MAX_SLIP_CARDS);

// ───────────────────────── ดึง intent ที่ยังไม่หมดเวลา ─────────────────────────
// แนะนำกรองตั้งแต่ SQL ด้วย expires_at > NOW() จะเร็วและชัวร์กว่า
$sqlI = "SELECT 
            pi.*,
            pr.name AS product_name, 
            pr.image AS product_image, 
            v.variant_name, 
            v.image AS variant_image
        FROM payment_intents pi
        LEFT JOIN products pr ON pi.product_id = pr.id
        LEFT JOIN product_variants v ON pi.variant_id = v.id
        WHERE pi.user_id = ?
          AND pi.status = 'active'
          AND pi.expires_at > NOW()
        ORDER BY pi.created_at DESC";

$resI = db_query($conn, $sqlI, [$user_id], "i");
$intents = [];
while ($row = $resI->fetch_assoc()) {
    $intents[] = $row;
}

// กรองซ้ำอีกชั้น + จำกัดจำนวน card ล่าสุด
$intents = filterVisibleIntents($intents, $MAX_PENDING_CARDS);

// ───────────────────────── UI Helpers ─────────────────────────
function statusBadgeClass($status)
{
    switch ($status) {
        case 'approved':
            return 'bg-success-subtle text-success';
        case 'rejected':
            return 'bg-danger-subtle text-danger';
        case 'pending':
        default:
            return 'bg-warning-subtle text-warning';
    }
}

function statusText($status)
{
    switch ($status) {
        case 'approved':
            return 'อนุมัติแล้ว';
        case 'rejected':
            return 'ปฏิเสธ / ไม่ผ่าน';
        case 'pending':
        default:
            return 'รอตรวจสอบ';
    }
}

function intentBadgeClass($status)
{
    switch ($status) {
        case 'active':
            return 'bg-primary-subtle text-primary';
        case 'expired':
        default:
            return 'bg-secondary-subtle text-secondary';
    }
}

function intentStatusText($status)
{
    switch ($status) {
        case 'active':
            return 'รอชำระเงิน';
        case 'expired':
        default:
            return 'หมดเวลา';
    }
}

function getItemImageUrl(mysqli $conn, int $productId, ?int $variantId): string
{
    if ($variantId && $variantId > 0) {
        $res = db_query($conn, "SELECT image FROM product_variants WHERE id = ? LIMIT 1", [$variantId], "i");
        $row = $res ? $res->fetch_assoc() : null;
        if (!empty($row['image'])) return buildImageUrl($row['image']);
    }

    if ($productId > 0) {
        return getProductMainImageUrl($conn, $productId);
    }

    return 'https://via.placeholder.com/64?text=No+Image';
}

$activeMenu = 'orders';

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออเดอร์ของฉัน | Line-Shop</title>
    <?php require_once SHARED_PARTIALS_PATH . '/bootstrap.php'; ?>
    <?php require_once SHARED_PATH . '/partials/sweetalert.php'; ?>

    <style>
        body {
            background: linear-gradient(135deg, #f0f2f5 0%, #e4e8eb 100%);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            padding-bottom: 80px;
            color: #1a1a1a;
        }

        .topbar {
            background: linear-gradient(135deg, #ee4d2d 0%, #ff6b35 100%) !important;
            box-shadow: 0 4px 16px rgba(238, 77, 45, 0.3);
        }

        .topbar .navbar-brand {
            font-size: clamp(1rem, 3vw, 1.25rem) !important;
            font-weight: 700 !important;
            letter-spacing: -0.3px;
        }

        .order-card {
            border-radius: 14px;
            border: 1px solid #e0e0e5;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fff;
        }

        .order-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.1);
            border-color: #ee4d2d;
        }

        .order-card .card-header {
            background: linear-gradient(135deg, #fafbfc 0%, #f5f7f9 100%);
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 16px !important;
        }

        .order-card .card-body {
            padding: 14px 16px !important;
        }

        .order-card .card-footer {
            padding: 12px 16px !important;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .order-card .card-footer.intent-footer {
            background: linear-gradient(135deg, #fff9f0 0%, #fff5e6 100%) !important;
            border-top: 2px solid #ffe4cc !important;
        }

        .order-card .card-footer.intent-footer .h5 {
            font-size: clamp(1.2rem, 4vw, 1.5rem);
            color: #ee4d2d;
            font-weight: 700;
            margin: 0;
        }

        .nav-tabs {
            border-bottom: 2px solid #d5d9e2;
            gap: 8px;
        }

        .nav-tabs .nav-link {
            color: #555;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            transition: all 0.2s ease;
            margin-bottom: -2px;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            padding: 12px clamp(8px, 2vw, 16px);
        }

        .nav-tabs .nav-link:hover {
            color: #ee4d2d;
            border-bottom-color: #ff9b88;
        }

        .nav-tabs .nav-link.active {
            color: #ee4d2d;
            background-color: transparent;
            border-bottom-color: #ee4d2d;
            font-weight: 700;
        }

        .nav-pills {
            gap: 6px;
            flex-wrap: wrap;
        }

        .nav-pills .nav-link {
            border-radius: 12px;
            padding: 8px clamp(10px, 2vw, 14px);
            font-weight: 600;
            background-color: #e8eaed;
            color: #555;
            transition: all 0.15s ease;
            font-size: clamp(0.85rem, 2.2vw, 0.95rem);
        }

        .nav-pills .nav-link:hover {
            background-color: #dbe0e8;
            color: #222;
            transform: translateY(-1px);
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #ee4d2d 0%, #ff6b35 100%);
            color: #fff;
            font-weight: 700;
        }

        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 18px 0 14px;
            padding-bottom: 12px;
            border-bottom: 2px solid #d5d9e2;
            flex-wrap: wrap;
        }

        .section-title .left {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: clamp(1rem, 3vw, 1.1rem);
            color: #1a1a1a;
        }

        .pill {
            border-radius: 999px;
            padding: 6px clamp(10px, 2vw, 14px);
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }

        .muted {
            color: #757575;
            font-weight: 500;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }

        .toggle-items-btn {
            border-radius: 999px;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #e8eaed;
            transition: all 0.15s ease;
            font-weight: 600;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            color: #333;
        }

        .toggle-items-btn:hover {
            background-color: #dbe0e8;
            transform: translateY(-1px);
        }

        .toggle-items-btn .chev {
            transition: transform 0.15s ease;
        }

        .toggle-items-btn[aria-expanded="true"] .chev {
            transform: rotate(180deg);
        }

        .items-panel {
            background: #f7f8fa;
            border-radius: 12px;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            margin-top: 8px;
        }

        .item-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #f0f0f2;
            box-shadow: 0 2px 4px rgba(15, 23, 42, 0.04);
        }

        .order-item-img {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;

            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06);
        }
        .badge {
            font-weight: 600;
            letter-spacing: .3px;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>


    <nav class="navbar topbar navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white ps-0" onclick="window.location.href='Buyer.php'" title="กลับไป">
                <i class="bi bi-chevron-left" style="font-size: 1.5rem;"></i>
            </button>
            <span class="navbar-brand mx-auto fw-bold" style="font-size: 1.2rem;">📦 ออเดอร์ของฉัน</span>
            <span class="me-3 text-white opacity-75 small d-none d-sm-inline">
                👤 <?php echo htmlspecialchars($user['first_name']); ?>
            </span>
        </div>
    </nav>

    <main class="container py-3">
        <?php if (empty($intents) && empty($allOrders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 mb-3 d-block text-muted" style="opacity: 0.6;"></i>
                <p class="text-muted fw-500">📭 ยังไม่มีประวัติการสั่งซื้อ</p>
                <small class="text-muted d-block">เริ่มสั่งซื้อสินค้าวันนี้!</small>
            </div>
        <?php else: ?>
            <?php
            $waitingCount = count($intents);
            // แสดงจำนวนรวมที่ badge: ให้เป็นผลรวมของรายการที่แสดงจริง
            $totalOrders  = count($ordersApproved) + count($ordersPending) + count($ordersRejected);
            $defaultTab   = ($waitingCount > 0) ? 'waiting' : 'slip';
            ?>

            <ul class="nav nav-tabs mb-3" id="orderHistoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link <?= ($defaultTab === 'waiting') ? 'active' : '' ?>"
                        id="tabWaitingBtn"
                        data-bs-toggle="tab"
                        data-bs-target="#tabWaiting"
                        type="button"
                        role="tab"
                        aria-controls="tabWaiting"
                        aria-selected="<?= ($defaultTab === 'waiting') ? 'true' : 'false' ?>">
                        ⏳ รอชำระเงิน
                        <?php if ($waitingCount > 0): ?>
                            <span class="badge bg-primary-subtle text-primary ms-1"><?= (int)$waitingCount ?></span>
                        <?php endif; ?>
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link <?= ($defaultTab === 'slip') ? 'active' : '' ?>"
                        id="tabSlipBtn"
                        data-bs-toggle="tab"
                        data-bs-target="#tabSlip"
                        type="button"
                        role="tab"
                        aria-controls="tabSlip"
                        aria-selected="<?= ($defaultTab === 'slip') ? 'true' : 'false' ?>">
                        📤 ประวัติการส่งสลิป
                        <?php if ($totalOrders > 0): ?>
                            <span class="badge bg-warning-subtle text-warning ms-1"><?= (int)$totalOrders ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                <!-- ================== รอชำระเงิน ================== -->
                <div class="tab-pane fade <?= ($defaultTab === 'waiting') ? 'show active' : '' ?>" id="tabWaiting" role="tabpanel" aria-labelledby="tabWaitingBtn">
                    <?php if (empty($intents)): ?>
                        <div class="text-center text-muted py-5">
                            <div class="fw-semibold">ไม่มีรายการรอชำระเงิน</div>
                            <div class="small">คำสั่งซื้อที่หมดเวลาจะถูกลบออกอัตโนมัติ</div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <div class="section-title">
                                <div class="left"><i class="bi bi-hourglass-split"></i> รอชำระเงิน</div>
                                <div class="muted small">แสดงล่าสุด <?= (int)$MAX_PENDING_CARDS ?> รายการ</div>
                            </div>

                            <?php foreach ($intents as $it): ?>
                                <?php
                                $intentItems = normalizeItems(safeJsonArray($it['items_json'] ?? '[]'));
                                $timeRemaining = (int)($it['_time_remaining'] ?? 0);
                                if ($timeRemaining <= 0) continue;

                                $first = $intentItems[0] ?? [];
                                $title = $first['name'] ?? ($it['product_name'] ?? 'รายการสินค้า');
                                $variantLabel = $first['variant_name'] ?? '';
                                $collapseId = 'collapse_intent_' . (int)$it['id'];
                                ?>
                                <div class="card order-card mb-3">
                                    <div class="card-header bg-white border-0 pt-3 pb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="small text-muted">
                                                รหัสคำสั่งซื้อ #<?php echo (int)$it['id']; ?>
                                                · <?php echo htmlspecialchars(($it['mode'] ?? '') === 'cart' ? 'ตะกร้าสินค้า' : 'ซื้อเดี่ยว'); ?>
                                            </div>
                                            <span class="pill <?php echo intentBadgeClass($it['status']); ?>">
                                                <?php echo intentStatusText($it['status']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="card-body pt-2 pb-2">
                                        <div class="d-flex align-items-start gap-2">
                                            <div class="rounded-3 bg-light overflow-hidden flex-shrink-0" style="width:64px;height:64px;">
                                                <?php $img = getItemImageUrl($conn, (int)($first['product_id'] ?? 0), (int)($first['variant_id'] ?? 0)); ?>
                                                <img class="order-item-img"
                                                    src="<?php echo htmlspecialchars($img); ?>"
                                                    alt="<?php echo htmlspecialchars($title); ?>"
                                                    loading="lazy"
                                                    onerror="this.src='https://via.placeholder.com/64?text=No+Image';">
                                            </div>

                                            <div class="flex-grow-1">
                                                <div class="small fw-semibold text-truncate">
                                                    <?php echo htmlspecialchars($title); ?>
                                                    <?php if (!empty($variantLabel)): ?>
                                                        <span class="text-muted">(<?php echo htmlspecialchars($variantLabel); ?>)</span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="small text-muted">
                                                    <?php echo (count($intentItems) > 1) ? ('รวม ' . count($intentItems) . ' รายการ') : '1 รายการ'; ?>
                                                </div>

                                                <?php if (count($intentItems) > 1): ?>
                                                    <div class="mt-2">
                                                        <button class="btn btn-sm btn-light toggle-items-btn"
                                                            type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#<?php echo $collapseId; ?>"
                                                            aria-expanded="false"
                                                            aria-controls="<?php echo $collapseId; ?>">
                                                            ดูรายการอื่น <?php echo count($intentItems) - 1; ?> รายการ
                                                            <i class="bi bi-chevron-down chev"></i>
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if (count($intentItems) > 1): ?>
                                            <div class="collapse mt-2" id="<?php echo $collapseId; ?>">
                                                <div class="items-panel">
                                                    <?php foreach (array_slice($intentItems, 1) as $row): ?>
                                                        <?php $rowImg = getItemImageUrl($conn, (int)$row['product_id'], (int)$row['variant_id']); ?>
                                                        <div class="item-row">
                                                            <img class="item-thumb"
                                                                src="<?php echo htmlspecialchars($rowImg); ?>"
                                                                alt="<?php echo htmlspecialchars($row['name']); ?>"
                                                                loading="lazy"
                                                                onerror="this.src='https://via.placeholder.com/48?text=No+Image';">

                                                            <div class="flex-grow-1">
                                                                <div class="small fw-semibold">
                                                                    <?php echo htmlspecialchars($row['name']); ?>
                                                                    <?php if (!empty($row['variant_name'])): ?>
                                                                        <span class="text-muted">(<?php echo htmlspecialchars($row['variant_name']); ?>)</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="small text-muted">
                                                                    จำนวน: <?php echo (int)$row['qty']; ?>
                                                                    <?php if ((float)$row['price'] > 0): ?>
                                                                        · ราคา/ชิ้น: <?php echo moneyTHB($row['price']); ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <div class="text-end small">
                                                                <?php if ((float)$row['subtotal'] > 0): ?>
                                                                    <div class="fw-semibold"><?php echo moneyTHB($row['subtotal']); ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-footer bg-white border-0 pt-2 pb-3 intent-footer">
                                        <div class="d-flex justify-content-between align-items-end mb-3">
                                            <div>
                                                <div class="small text-muted">ยอดชำระทั้งหมด</div>
                                                <div class="h5 fw-bold text-danger mb-0"><?php echo moneyTHB((float)$it['amount']); ?></div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">สร้างเมื่อ</small>
                                                <div class="small"><?php echo htmlspecialchars((string)$it['created_at']); ?></div>
                                            </div>
                                        </div>

                                        <div class="mt-3 d-flex justify-content-between align-items-center gap-2">
                                            <small class="text-danger">
                                                <i class="bi bi-clock"></i>
                                                เวลาเหลือ <span class="intentCountdown" data-expires-ts="<?php echo (int)strtotime((string)$it['expires_at']); ?>">--:--</span>
                                            </small>

                                            <div class="d-flex gap-2">
                                                <a class="btn btn-sm btn-primary"
                                                    href="payment.php?intent_id=<?php echo (int)$it['id']; ?>">
                                                    <i class="bi bi-arrow-right"></i> ชำระเงิน
                                                </a>

                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="cancelIntent(<?php echo (int)$it['id']; ?>)">
                                                    <i class="bi bi-x"></i> ยกเลิก
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($waitingCount >= $MAX_PENDING_CARDS): ?>
                                <div class="text-center text-muted small mt-2">
                                    แสดงเฉพาะรายการล่าสุด <?= (int)$MAX_PENDING_CARDS ?> รายการ
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ================== ส่งสลิปแล้ว / รอตรวจสอบ (payments) ================== -->
                <div class="tab-pane fade <?= ($defaultTab === 'slip') ? 'show active' : '' ?>" id="tabSlip" role="tabpanel" aria-labelledby="tabSlipBtn">
                    <?php if (empty($allOrders)): ?>
                        <div class="text-center text-muted py-5">
                            <div class="fw-semibold">ยังไม่มีรายการส่งสลิป</div>
                            <div class="small">เมื่อคุณส่งสลิปชำระเงิน รายการจะแสดงที่นี่</div>
                        </div>
                    <?php else: ?>
                        <!-- Filter sub-tabs สำหรับแต่ละ status -->
                        <ul class="nav nav-pills mb-3" id="slipStatusTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="statusApprovedBtn" data-bs-toggle="tab" 
                                    data-bs-target="#statusApproved" type="button" role="tab">
                                    ✅ อนุมัติแล้ว
                                    <?php if (count($ordersApproved) > 0): ?>
                                        <span class="badge bg-success-subtle text-success ms-1"><?= count($ordersApproved) ?></span>
                                    <?php endif; ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="statusPendingBtn" data-bs-toggle="tab" 
                                    data-bs-target="#statusPending" type="button" role="tab">
                                    ⏳ รอตรวจสอบ
                                    <?php if (count($ordersPending) > 0): ?>
                                        <span class="badge bg-warning-subtle text-warning ms-1"><?= count($ordersPending) ?></span>
                                    <?php endif; ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="statusRejectedBtn" data-bs-toggle="tab" 
                                    data-bs-target="#statusRejected" type="button" role="tab">
                                    ❌ ปฏิเสธ
                                    <?php if (count($ordersRejected) > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger ms-1"><?= count($ordersRejected) ?></span>
                                    <?php endif; ?>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Status: Approved -->
                            <div class="tab-pane fade show active" id="statusApproved" role="tabpanel">
                                <?php if (empty($ordersApproved)): ?>
                                    <div class="text-center text-muted py-3">
                                        <div class="small">ไม่มีรายการที่อนุมัติแล้ว</div>
                                    </div>
                                <?php else: ?>
                                    <div class="section-title">
                                        <div class="left"><i class="bi bi-receipt"></i> อนุมัติแล้ว</div>
                                        <div class="muted small">แสดงล่าสุด <?= (int)$MAX_SLIP_CARDS ?> รายการ</div>
                                    </div>
                                    <?php foreach ($ordersApproved as $order): ?>
                                        <?php
                                        $orderItems = buildItemsFromPaymentRow($order);
                                        $first = $orderItems[0] ?? [];
                                        $title = (string)($first['name'] ?? 'สินค้า');
                                        $variantLabel = (string)($first['variant_name'] ?? '');
                                        $collapseId = 'collapse_payment_' . (int)$order['id'];
                                        ?>
                                        <div class="card order-card mb-3">
                                            <div class="card-header bg-white border-0 pt-3 pb-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="small text-muted">
                                                        รหัสคำสั่งซื้อ #<?php echo (int)$order['id']; ?>
                                                    </div>
                                                    <span class="badge pill <?php echo statusBadgeClass($order['status']); ?>">
                                                        <?php echo statusText($order['status']); ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="card-body pt-2 pb-2">
                                                <div class="d-flex align-items-start gap-2">
                                                    <div class="rounded-3 bg-light overflow-hidden flex-shrink-0" style="width:64px;height:64px;">
                                                        <?php $img = getItemImageUrl($conn, (int)($first['product_id'] ?? 0), (int)($first['variant_id'] ?? 0)); ?>
                                                        <img class="order-item-img"
                                                            src="<?php echo htmlspecialchars($img); ?>"
                                                            alt="<?php echo htmlspecialchars($title); ?>"
                                                            loading="lazy"
                                                            onerror="this.src='https://via.placeholder.com/64?text=No+Image';">
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="small fw-semibold text-truncate">
                                                            <?php echo htmlspecialchars($title); ?>
                                                            <?php if (!empty($variantLabel)): ?>
                                                                <span class="text-muted">(<?php echo htmlspecialchars($variantLabel); ?>)</span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="small text-muted">
                                                            <?php echo (count($orderItems) > 1) ? ('รวม ' . count($orderItems) . ' รายการ') : '1 รายการ'; ?>
                                                        </div>

                                                        <?php if (count($orderItems) > 1): ?>
                                                            <div class="mt-2">
                                                                <button class="btn btn-sm btn-light toggle-items-btn"
                                                                    type="button"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#<?php echo $collapseId; ?>"
                                                                    aria-expanded="false"
                                                                    aria-controls="<?php echo $collapseId; ?>">
                                                                    ดูรายการอื่น <?php echo count($orderItems) - 1; ?> รายการ
                                                                    <i class="bi bi-chevron-down chev"></i>
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if (count($orderItems) > 1): ?>
                                                    <div class="collapse mt-2" id="<?php echo $collapseId; ?>">
                                                        <div class="items-panel">
                                                            <?php foreach (array_slice($orderItems, 1) as $row): ?>
                                                                <?php $rowImg = getItemImageUrl($conn, (int)$row['product_id'], (int)$row['variant_id']); ?>
                                                                <div class="item-row">
                                                                    <img class="item-thumb"
                                                                        src="<?php echo htmlspecialchars($rowImg); ?>"
                                                                        alt="<?php echo htmlspecialchars($row['name']); ?>"
                                                                        loading="lazy"
                                                                        onerror="this.src='https://via.placeholder.com/48?text=No+Image';">

                                                                    <div class="flex-grow-1">
                                                                        <div class="small fw-semibold">
                                                                            <?php echo htmlspecialchars($row['name']); ?>
                                                                            <?php if (!empty($row['variant_name'])): ?>
                                                                                <span class="text-muted">(<?php echo htmlspecialchars($row['variant_name']); ?>)</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="small text-muted">
                                                                            จำนวน: <?php echo (int)$row['qty']; ?>
                                                                            <?php if ((float)$row['price'] > 0): ?>
                                                                                · ราคา/ชิ้น: <?php echo moneyTHB($row['price']); ?>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>

                                                                    <div class="text-end small">
                                                                        <?php if ((float)$row['subtotal'] > 0): ?>
                                                                            <div class="fw-semibold"><?php echo moneyTHB($row['subtotal']); ?></div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="card-footer bg-white border-0 pt-2 pb-3">
                                                <div class="d-flex justify-content-between align-items-end">
                                                    <div>
                                                        <div class="small text-muted">ยอดชำระทั้งหมด</div>
                                                        <div class="h5 fw-bold text-danger mb-0"><?php echo moneyTHB((float)$order['amount']); ?></div>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted">อนุมัติเมื่อ</small>
                                                        <div class="small"><?php echo htmlspecialchars((string)$order['created_at']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Status: Pending -->
                            <div class="tab-pane fade" id="statusPending" role="tabpanel">
                                <?php if (empty($ordersPending)): ?>
                                    <div class="text-center text-muted py-3">
                                        <div class="small">ไม่มีรายการรอตรวจสอบ</div>
                                    </div>
                                <?php else: ?>
                                    <div class="section-title">
                                        <div class="left"><i class="bi bi-receipt"></i> รอตรวจสอบ</div>
                                        <div class="muted small">แสดงล่าสุด <?= (int)$MAX_SLIP_CARDS ?> รายการ</div>
                                    </div>
                                    <?php foreach ($ordersPending as $order): ?>
                                        <?php
                                        $orderItems = buildItemsFromPaymentRow($order);
                                        $first = $orderItems[0] ?? [];
                                        $title = (string)($first['name'] ?? 'สินค้า');
                                        $variantLabel = (string)($first['variant_name'] ?? '');
                                        $collapseId = 'collapse_payment_' . (int)$order['id'];
                                        ?>
                                        <div class="card order-card mb-3">
                                            <div class="card-header bg-white border-0 pt-3 pb-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="small text-muted">
                                                        รหัสคำสั่งซื้อ #<?php echo (int)$order['id']; ?>
                                                    </div>
                                                    <span class="badge pill <?php echo statusBadgeClass($order['status']); ?>">
                                                        <?php echo statusText($order['status']); ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="card-body pt-2 pb-2">
                                                <div class="d-flex align-items-start gap-2">
                                                    <div class="rounded-3 bg-light overflow-hidden flex-shrink-0" style="width:64px;height:64px;">
                                                        <?php $img = getItemImageUrl($conn, (int)($first['product_id'] ?? 0), (int)($first['variant_id'] ?? 0)); ?>
                                                        <img class="order-item-img"
                                                            src="<?php echo htmlspecialchars($img); ?>"
                                                            alt="<?php echo htmlspecialchars($title); ?>"
                                                            loading="lazy"
                                                            onerror="this.src='https://via.placeholder.com/64?text=No+Image';">
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="small fw-semibold text-truncate">
                                                            <?php echo htmlspecialchars($title); ?>
                                                            <?php if (!empty($variantLabel)): ?>
                                                                <span class="text-muted">(<?php echo htmlspecialchars($variantLabel); ?>)</span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="small text-muted">
                                                            <?php echo (count($orderItems) > 1) ? ('รวม ' . count($orderItems) . ' รายการ') : '1 รายการ'; ?>
                                                        </div>

                                                        <?php if (count($orderItems) > 1): ?>
                                                            <div class="mt-2">
                                                                <button class="btn btn-sm btn-light toggle-items-btn"
                                                                    type="button"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#<?php echo $collapseId; ?>"
                                                                    aria-expanded="false"
                                                                    aria-controls="<?php echo $collapseId; ?>">
                                                                    ดูรายการอื่น <?php echo count($orderItems) - 1; ?> รายการ
                                                                    <i class="bi bi-chevron-down chev"></i>
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if (count($orderItems) > 1): ?>
                                                    <div class="collapse mt-2" id="<?php echo $collapseId; ?>">
                                                        <div class="items-panel">
                                                            <?php foreach (array_slice($orderItems, 1) as $row): ?>
                                                                <?php $rowImg = getItemImageUrl($conn, (int)$row['product_id'], (int)$row['variant_id']); ?>
                                                                <div class="item-row">
                                                                    <img class="item-thumb"
                                                                        src="<?php echo htmlspecialchars($rowImg); ?>"
                                                                        alt="<?php echo htmlspecialchars($row['name']); ?>"
                                                                        loading="lazy"
                                                                        onerror="this.src='https://via.placeholder.com/48?text=No+Image';">

                                                                    <div class="flex-grow-1">
                                                                        <div class="small fw-semibold">
                                                                            <?php echo htmlspecialchars($row['name']); ?>
                                                                            <?php if (!empty($row['variant_name'])): ?>
                                                                                <span class="text-muted">(<?php echo htmlspecialchars($row['variant_name']); ?>)</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="small text-muted">
                                                                            จำนวน: <?php echo (int)$row['qty']; ?>
                                                                            <?php if ((float)$row['price'] > 0): ?>
                                                                                · ราคา/ชิ้น: <?php echo moneyTHB($row['price']); ?>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>

                                                                    <div class="text-end small">
                                                                        <?php if ((float)$row['subtotal'] > 0): ?>
                                                                            <div class="fw-semibold"><?php echo moneyTHB($row['subtotal']); ?></div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="card-footer bg-white border-0 pt-2 pb-3">
                                                <div class="d-flex justify-content-between align-items-end">
                                                    <div>
                                                        <div class="small text-muted">ยอดชำระทั้งหมด</div>
                                                        <div class="h5 fw-bold text-danger mb-0"><?php echo moneyTHB((float)$order['amount']); ?></div>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted">ส่งสลิปเมื่อ</small>
                                                        <div class="small"><?php echo htmlspecialchars((string)$order['created_at']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Status: Rejected -->
                            <div class="tab-pane fade" id="statusRejected" role="tabpanel">
                                <?php if (empty($ordersRejected)): ?>
                                    <div class="text-center text-muted py-3">
                                        <div class="small">ไม่มีรายการที่ปฏิเสธ</div>
                                    </div>
                                <?php else: ?>
                                    <div class="section-title">
                                        <div class="left"><i class="bi bi-receipt"></i> ปฏิเสธ / ไม่ผ่าน</div>
                                        <div class="muted small">แสดงล่าสุด <?= (int)$MAX_SLIP_CARDS ?> รายการ</div>
                                    </div>
                                    <?php foreach ($ordersRejected as $order): ?>
                                        <?php
                                        $orderItems = buildItemsFromPaymentRow($order);
                                        $first = $orderItems[0] ?? [];
                                        $title = (string)($first['name'] ?? 'สินค้า');
                                        $variantLabel = (string)($first['variant_name'] ?? '');
                                        $collapseId = 'collapse_payment_' . (int)$order['id'];
                                        ?>
                                        <div class="card order-card mb-3">
                                            <div class="card-header bg-white border-0 pt-3 pb-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="small text-muted">
                                                        รหัสคำสั่งซื้อ #<?php echo (int)$order['id']; ?>
                                                    </div>
                                                    <span class="badge pill <?php echo statusBadgeClass($order['status']); ?>">
                                                        <?php echo statusText($order['status']); ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="card-body pt-2 pb-2">
                                                <div class="d-flex align-items-start gap-2">
                                                    <div class="rounded-3 bg-light overflow-hidden flex-shrink-0" style="width:64px;height:64px;">
                                                        <?php $img = getItemImageUrl($conn, (int)($first['product_id'] ?? 0), (int)($first['variant_id'] ?? 0)); ?>
                                                        <img class="order-item-img"
                                                            src="<?php echo htmlspecialchars($img); ?>"
                                                            alt="<?php echo htmlspecialchars($title); ?>"
                                                            loading="lazy"
                                                            onerror="this.src='https://via.placeholder.com/64?text=No+Image';">
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="small fw-semibold text-truncate">
                                                            <?php echo htmlspecialchars($title); ?>
                                                            <?php if (!empty($variantLabel)): ?>
                                                                <span class="text-muted">(<?php echo htmlspecialchars($variantLabel); ?>)</span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="small text-muted">
                                                            <?php echo (count($orderItems) > 1) ? ('รวม ' . count($orderItems) . ' รายการ') : '1 รายการ'; ?>
                                                        </div>

                                                        <?php if (count($orderItems) > 1): ?>
                                                            <div class="mt-2">
                                                                <button class="btn btn-sm btn-light toggle-items-btn"
                                                                    type="button"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#<?php echo $collapseId; ?>"
                                                                    aria-expanded="false"
                                                                    aria-controls="<?php echo $collapseId; ?>">
                                                                    ดูรายการอื่น <?php echo count($orderItems) - 1; ?> รายการ
                                                                    <i class="bi bi-chevron-down chev"></i>
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if (count($orderItems) > 1): ?>
                                                    <div class="collapse mt-2" id="<?php echo $collapseId; ?>">
                                                        <div class="items-panel">
                                                            <?php foreach (array_slice($orderItems, 1) as $row): ?>
                                                                <?php $rowImg = getItemImageUrl($conn, (int)$row['product_id'], (int)$row['variant_id']); ?>
                                                                <div class="item-row">
                                                                    <img class="item-thumb"
                                                                        src="<?php echo htmlspecialchars($rowImg); ?>"
                                                                        alt="<?php echo htmlspecialchars($row['name']); ?>"
                                                                        loading="lazy"
                                                                        onerror="this.src='https://via.placeholder.com/48?text=No+Image';">

                                                                    <div class="flex-grow-1">
                                                                        <div class="small fw-semibold">
                                                                            <?php echo htmlspecialchars($row['name']); ?>
                                                                            <?php if (!empty($row['variant_name'])): ?>
                                                                                <span class="text-muted">(<?php echo htmlspecialchars($row['variant_name']); ?>)</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="small text-muted">
                                                                            จำนวน: <?php echo (int)$row['qty']; ?>
                                                                            <?php if ((float)$row['price'] > 0): ?>
                                                                                · ราคา/ชิ้น: <?php echo moneyTHB($row['price']); ?>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>

                                                                    <div class="text-end small">
                                                                        <?php if ((float)$row['subtotal'] > 0): ?>
                                                                            <div class="fw-semibold"><?php echo moneyTHB($row['subtotal']); ?></div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="card-footer bg-white border-0 pt-2 pb-3">
                                                <div class="d-flex justify-content-between align-items-end">
                                                    <div>
                                                        <div class="small text-muted">ยอดชำระทั้งหมด</div>
                                                        <div class="h5 fw-bold text-danger mb-0"><?php echo moneyTHB((float)$order['amount']); ?></div>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted">ปฏิเสธเมื่อ</small>
                                                        <div class="small"><?php echo htmlspecialchars((string)$order['created_at']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <!-- Bottom Navigation Bar -->
    <?php include FRONTEND_PATH . '/partials/buyer_bottom_nav.php'; ?>

    <script>
        // Countdown สำหรับ intent (รอชำระ)
        function pad2(n) {
            return String(n).padStart(2, '0');
        }

        function updateIntentCountdowns() {
            document.querySelectorAll('.intentCountdown').forEach(el => {
                const expiresTs = parseInt(el.dataset.expiresTs || '0', 10);
                if (!expiresTs) return;

                const now = Math.floor(Date.now() / 1000);
                let diff = expiresTs - now;

                if (diff <= 0) {
                    el.textContent = '00:00';
                    // ถ้าหมดเวลา ให้ลบ card ออกจาก UI ทันที (กัน edge case)
                    const card = el.closest('.order-card');
                    if (card) card.remove();
                    return;
                }

                const m = Math.floor(diff / 60);
                const s = diff % 60;
                el.textContent = `${m}:${pad2(s)}`;
            });
        }

        setInterval(updateIntentCountdowns, 1000);
        updateIntentCountdowns();

        // Cancel intent (เรียก endpoint ของคุณตามที่มีอยู่)
        function cancelIntent(intentId) {
            if (!intentId) return;
            if (!confirm('ยืนยันยกเลิกการจองนี้?')) return;

            fetch('<?= FRONTEND_URL ?>/actions/buyer/expire_intent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'intent_id=' + encodeURIComponent(intentId)
                })
                .then(r => r.json())
                .then(data => {
                    if (data && data.ok) {
                        location.reload();
                        return;
                    }
                    alert(data && data.message ? data.message : 'ยกเลิกไม่สำเร็จ');
                })
                .catch(() => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
        }
    </script>

</body>

</html>