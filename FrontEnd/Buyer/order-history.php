<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once UTILS_PATH . '/image_helper.php';
require_once UTILS_PATH . '/product_image_helper.php';
require_once SERVICES_PATH . '/userService.php';

$conn    = connectDBWithLog();
$user_id = require_user_id();

$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/Users/line-entry.php?from=register");
    exit;
}

// ดึงออเดอร์จากตาราง payments
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
        ORDER BY p.created_at DESC";

$res = db_query($conn, $sql, [$user_id], "i");
$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}

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

function getOrderItemImageUrl(mysqli $conn, array $order): string
{
    if (!empty($order['variant_image'])) {
        return buildImageUrl($order['variant_image']);
    }

    if (!empty($order['product_id'])) {
        return getProductMainImageUrl($conn, (int)$order['product_id']);
    }

    return buildImageUrl('');
}

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออเดอร์ของฉัน | Line-Shop</title>
    <?php include BASE_PATH . '/partials/bootstrap.php'; ?>

    <style>
        body {
            background: #f5f5f7;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            padding-bottom: 72px;
        }

        .topbar {
            background: linear-gradient(90deg,
                    rgba(238, 77, 45, 0.97),
                    rgba(255, 143, 90, 0.97));
        }

        .order-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .order-item-img {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #ffffff;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 2000;
        }

        .bottom-nav .nav-item {
            flex: 1;
            text-align: center;
            font-size: 0.75rem;
            color: #777;
            text-decoration: none;
            padding-top: 3px;
        }

        .bottom-nav .nav-item i {
            font-size: 1.3rem;
            display: block;
            margin-bottom: 2px;
        }

        .bottom-nav .nav-item.active {
            color: #ee4d2d;
            font-weight: 600;
        }

        .bottom-nav .nav-item.active i {
            color: #ee4d2d;
        }
    </style>
</head>

<body>

    <!-- Topbar -->
    <nav class="navbar topbar navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white" onclick="window.location.href='Buyer.php'">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="navbar-brand mx-auto">ออเดอร์ของฉัน</span>
            <span class="me-3 text-white-50 small d-none d-sm-inline">
                <?php echo htmlspecialchars($user['first_name']); ?>
            </span>
        </div>
    </nav>

    <main class="container py-3">

        <?php if (empty($orders)): ?>
            <div class="text-center text-muted mt-5">
                <i class="bi bi-box-seam fs-1 mb-2 d-block"></i>
                <p>ยังไม่มีประวัติการสั่งซื้อ</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                $items = [];
                if ($order['mode'] === 'cart' && !empty($order['items_json'])) {
                    $items = json_decode($order['items_json'], true) ?: [];
                }
                ?>
                <div class="card order-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white border-0 pt-3 pb-2">
                        <div class="small text-muted">
                            ออเดอร์ #<?php echo (int)$order['id']; ?>
                            · <?php echo htmlspecialchars($order['mode'] === 'cart' ? 'ตะกร้าสินค้า' : 'ซื้อเดี่ยว'); ?>
                        </div>
                        <span class="badge <?php echo statusBadgeClass($order['status']); ?>">
                            <?php echo statusText($order['status']); ?>
                        </span>
                    </div>
                    <div class="card-body pt-2 pb-2">

                        <?php if ($order['mode'] === 'single'): ?>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-light overflow-hidden flex-shrink-0"
                                    style="width:64px;height:64px;">
                                    <?php $img = getOrderItemImageUrl($conn, $order); ?>

                                    <img class="order-item-img"
                                        src="<?php echo htmlspecialchars($img); ?>"
                                        alt="<?php echo htmlspecialchars($order['product_name'] ?? 'สินค้า'); ?>"
                                        loading="lazy"
                                        onerror="this.src='https://via.placeholder.com/64?text=No+Image';">

                                </div>
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold text-truncate">
                                        <?php echo htmlspecialchars($order['product_name'] ?? 'สินค้าเดี่ยว'); ?>
                                        <?php if (!empty($order['variant_id'])): ?>
                                            (<?php echo htmlspecialchars($order['variant_name'] ?? ''); ?>)
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted">
                                        จำนวน: 1 ชิ้น
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($items)): ?>
                                <?php
                                $previewCount = 1;
                                $totalItems   = count($items);
                                $hasMore      = $totalItems > $previewCount;
                                $collapseId   = 'order-item-' . (int)$order['id'];

                                $visibleItems = array_slice($items, 0, $previewCount);
                                $hiddenItems  = array_slice($items, $previewCount);
                                ?>

                                <div class="vstack gap-2">

                                    <!-- 1) โชว์รายการ -->
                                    <?php foreach ($visibleItems as $it): ?>
                                        <?php
                                        $pId = (int)($it['product_id'] ?? $it['productId'] ?? 0);
                                        $vId = (int)($it['variant_id'] ?? $it['variantId'] ?? 0);

                                        $name  = (string)($it['name'] ?? 'สินค้า');
                                        $qty   = (int)($it['quantity'] ?? 0);
                                        $price = (float)($it['price'] ?? 0);

                                        $img = '';
                                        if (!empty($it['image'])) {
                                            $img = buildImageUrl($it['image']);
                                        } else {
                                            if ($vId > 0) {
                                                $r = db_query($conn, "SELECT image FROM product_variants WHERE id = ? LIMIT 1", [$vId], "i");
                                                $vrow = $r ? $r->fetch_assoc() : null;
                                                if (!empty($vrow['image'])) $img = buildImageUrl($vrow['image']);
                                            }
                                            if ($img === '' && $pId > 0) {
                                                $img = getProductMainImageUrl($conn, $pId);
                                            }
                                        }

                                        $variantLabel = (string)($it['variant_name'] ?? '');
                                        ?>

                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-3 bg-light overflow-hidden flex-shrink-0" style="width:64px;height:64px;">
                                                <img class="order-item-img"
                                                    src="<?php echo htmlspecialchars($img); ?>"
                                                    alt="<?php echo htmlspecialchars($name); ?>"
                                                    loading="lazy"
                                                    onerror="this.src='https://via.placeholder.com/64?text=No+Image';">
                                            </div>

                                            <div class="flex-grow-1">
                                                <div class="small fw-semibold text-truncate">
                                                    <?php echo htmlspecialchars($name); ?>
                                                    <?php if ($variantLabel !== ''): ?>
                                                        <span class="text-muted">(<?php echo htmlspecialchars($variantLabel); ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="small text-muted">จำนวน: <?php echo $qty; ?> ชิ้น</div>
                                            </div>

                                            <?php if ($price > 0): ?>
                                                <div class="text-end">
                                                    <div class="small text-muted">฿<?php echo number_format($price, 2); ?></div>
                                                    <div class="small fw-semibold">฿<?php echo number_format($price * max($qty, 1), 2); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <hr class="my-1">
                                    <?php endforeach; ?>

                                    <!-- 2) collapse -->
                                    <?php if ($hasMore): ?>
                                        <div class="collapse mt-2" id="<?php echo htmlspecialchars($collapseId); ?>">
                                            <?php foreach ($hiddenItems as $it): ?>
                                                <?php
                                                $pId = (int)($it['product_id'] ?? $it['productId'] ?? 0);
                                                $vId = (int)($it['variant_id'] ?? $it['variantId'] ?? 0);

                                                $name  = (string)($it['name'] ?? 'สินค้า');
                                                $qty   = (int)($it['quantity'] ?? 0);
                                                $price = (float)($it['price'] ?? 0);

                                                $img = '';
                                                if (!empty($it['image'])) {
                                                    $img = buildImageUrl($it['image']);
                                                } else {
                                                    if ($vId > 0) {
                                                        $r = db_query($conn, "SELECT image FROM product_variants WHERE id = ? LIMIT 1", [$vId], "i");
                                                        $vrow = $r ? $r->fetch_assoc() : null;
                                                        if (!empty($vrow['image'])) $img = buildImageUrl($vrow['image']);
                                                    }
                                                    if ($img === '' && $pId > 0) {
                                                        $img = getProductMainImageUrl($conn, $pId);
                                                    }
                                                }

                                                $variantLabel = (string)($it['variant_name'] ?? '');
                                                ?>

                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-3 bg-light overflow-hidden flex-shrink-0" style="width:64px;height:64px;">
                                                        <img class="order-item-img"
                                                            src="<?php echo htmlspecialchars($img); ?>"
                                                            alt="<?php echo htmlspecialchars($name); ?>"
                                                            loading="lazy"
                                                            onerror="this.src='https://via.placeholder.com/64?text=No+Image';">
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="small fw-semibold text-truncate">
                                                            <?php echo htmlspecialchars($name); ?>
                                                            <?php if ($variantLabel !== ''): ?>
                                                                <span class="text-muted">(<?php echo htmlspecialchars($variantLabel); ?>)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="small text-muted">จำนวน: <?php echo $qty; ?> ชิ้น</div>
                                                    </div>

                                                    <?php if ($price > 0): ?>
                                                        <div class="text-end">
                                                            <div class="small text-muted">฿<?php echo number_format($price, 2); ?></div>
                                                            <div class="small fw-semibold">฿<?php echo number_format($price * max($qty, 1), 2); ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <hr class="my-1">
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- 3) toggle -->
                                        <button class="btn btn-sm btn-outline-secondary w-100 mt-2"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#<?php echo htmlspecialchars($collapseId); ?>"
                                            aria-expanded="false"
                                            aria-controls="<?php echo htmlspecialchars($collapseId); ?>">
                                            ดูทั้งหมด (<?php echo $totalItems; ?> รายการ)
                                        </button>
                                    <?php endif; ?>

                                </div>

                            <?php else: ?>
                                <div class="small text-muted">ข้อมูลสินค้าไม่พร้อมใช้งาน</div>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                    <div class="card-footer bg-white border-0 pt-0 pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                วันที่สั่งซื้อ: <?php echo htmlspecialchars($order['created_at']); ?>
                            </div>
                            <div class="text-end">
                                <div class="small">ยอดชำระทั้งหมด</div>
                                <div class="fw-bold text-danger">
                                    ฿<?php echo number_format((float)$order['amount'], 2); ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($order['slip_path'])): ?>
                            <?php
                            $slipUrl = '../../' . ltrim($order['slip_path'], '/');
                            ?>
                            <div class="mt-2 text-end">
                                <a href="<?php echo htmlspecialchars($slipUrl); ?>" target="_blank"
                                    class="btn btn-sm btn-outline-secondary">
                                    ดูสลิป
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>

    <!-- Footer Bar -->
    <nav class="bottom-nav">
        <a href="Buyer.php" class="nav-item" id="nav-home">
            <i class="bi bi-house-door"></i>
            <span>หน้าแรก</span>
        </a>

        <a href="order-history.php" class="nav-item active" id="nav-orders">
            <i class="bi bi-receipt"></i>
            <span>ออเดอร์ของฉัน</span>
        </a>

        <a href="notifications.php" class="nav-item" id="nav-noti">
            <i class="bi bi-bell"></i>
            <span>แจ้งเตือน / สลิป</span>
        </a>

        <a href="profile.php" class="nav-item" id="nav-me">
            <i class="bi bi-person"></i>
            <span>ฉัน</span>
        </a>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('button[data-bs-toggle="collapse"]').forEach(btn => {
                const sel = btn.getAttribute('data-bs-target');
                const el = document.querySelector(sel);
                if (!el) return;

                const original = btn.textContent;

                el.addEventListener('shown.bs.collapse', () => btn.textContent = 'ซ่อนรายการ');
                el.addEventListener('hidden.bs.collapse', () => btn.textContent = original);
            });
        });
    </script>

</body>

</html>