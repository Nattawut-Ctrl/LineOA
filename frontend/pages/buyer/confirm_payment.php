<?php
session_start();

// ป้องกัน browser cache (กันกด Back แล้วเห็นหน้าเก่า)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once UTILS_PATH . '/image_helper.php';
require_once UTILS_PATH . '/product_image_helper.php';
require_once SERVICES_PATH . '/userService.php';

$conn = connectDBWithLog();
$user_id = require_user_id();

$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/pages/users/line-entry.php?from=register");
    exit;
}

// ดึงออเดอร์ล่าสุด (1 อันแรก)
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
        LIMIT 1";

$res = db_query($conn, $sql, [$user_id], "i");
$latestOrder = $res ? $res->fetch_assoc() : null;

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

<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ยืนยันการชำระเงิน</title>
    <?php require_once SHARED_PARTIALS_PATH . '/bootstrap.php'; ?>

    <style>
        :root {
            --orange-1: #ff6f4f;
            --orange-2: #ff845c;
            --orange-3: #ff9c6a;
            --glass: rgba(255, 255, 255, .14);
            --glass-border: rgba(255, 255, 255, .45);
            --shadow: 0 18px 45px rgba(0, 0, 0, .14);
            --radius: 18px;
        }

        body {
            font-family: "Kanit", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: #f4f5f7;
        }

        .topbar {
            background: linear-gradient(90deg,
                    rgba(238, 77, 45, 0.97),
                    rgba(255, 143, 90, 0.97));
        }

        /* Header โทนส้มแบบในภาพ */
        .hero {
            position: relative;
            min-height: 420px;
            background:
                radial-gradient(1200px 600px at 25% 10%, rgba(255, 255, 255, .18) 0%, rgba(255, 255, 255, 0) 60%),
                radial-gradient(900px 600px at 85% 18%, rgba(255, 255, 255, .12) 0%, rgba(255, 255, 255, 0) 55%),
                linear-gradient(135deg, var(--orange-1), var(--orange-2) 50%, var(--orange-3));
            overflow: hidden;
            padding: 18px 16px 110px;
        }

        .hero .topbar {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            opacity: .95;
            margin-bottom: 18px;
        }

        .hero .back-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, .35);
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(8px);
        }

        .hero .back-btn:hover {
            background: rgba(255, 255, 255, .16);
        }

        .status-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .35);
            box-shadow: 0 10px 26px rgba(0, 0, 0, .12);
            margin: 4px auto 10px;
        }

        .hero-title {
            color: #fff;
            font-weight: 700;
            letter-spacing: .2px;
            margin: 8px 0 10px;
            font-size: clamp(22px, 4.5vw, 30px);
        }

        .hero-desc {
            color: #fff;
            opacity: .95;
            font-weight: 300;
            line-height: 1.45;
            max-width: 42rem;
            margin: 0 auto 12px;
            font-size: 16px;
        }

        .hero-pill {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, .35);
            background: rgba(255, 255, 255, .10);
            color: #fff;
            font-size: 14px;
            margin-bottom: 14px;
            backdrop-filter: blur(8px);
        }

        .hero-actions .btn-glass {
            border: 1px solid var(--glass-border);
            background: var(--glass);
            color: #fff;
            border-radius: 14px;
            padding: 12px 12px;
            font-weight: 600;
            backdrop-filter: blur(8px);
        }

        .hero-actions .btn-glass:hover {
            background: rgba(255, 255, 255, .18);
            color: #fff;
        }

        /* กล่องขาวด้านล่าง (ซ้อนทับ header) */
        .content-wrap {
            margin-top: -70px;
            padding-bottom: 22px;
        }

        .card-soft {
            border: 0;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .mini-banner {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .badge-soft {
            background: #fef3c7;
            color: #92400e;
            font-weight: 700;
        }

        .btn-outline-orange {
            border-color: rgba(255, 111, 79, .55);
            color: #ff6f4f;
            font-weight: 700;
        }

        .btn-outline-orange:hover {
            background: rgba(255, 111, 79, .10);
            border-color: rgba(255, 111, 79, .70);
            color: #ff6f4f;
        }

        /* แบนเนอร์ล่างแบบภาพ (จำลอง) */
        .bottom-banner {
            background: linear-gradient(135deg, #ff5f43, #ff8a5c);
            border-radius: 18px;
            color: #fff;
            padding: 18px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .14);
            overflow: hidden;
            position: relative;
        }

        .bottom-banner::after {
            content: "";
            position: absolute;
            right: -60px;
            top: -60px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .16);
            filter: blur(0px);
        }

        .small-muted {
            color: #6b7280;
            font-size: 13px;
        }
    </style>

    <script>
      // กันกดปุ่ม Back แล้วหลุดจากหน้า confirm_payment
      (function () {
        try {
          history.pushState(null, document.title, location.href);
          window.addEventListener('popstate', function () {
            history.pushState(null, document.title, location.href);
          });

          // กัน BFCache (บาง browser กด back แล้วโหลดจาก cache)
          window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
              location.reload();
            }
          });
        } catch (e) {}
      })();
    </script>

</head>

<body>
    <nav class="navbar topbar navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white" onclick="window.location.href='Buyer.php'">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="navbar-brand mx-auto">ชำระเงินสำเร็จ</span>
            <span class="me-3 text-white-50 small d-none d-sm-inline">
                <?php echo htmlspecialchars($user['first_name']); ?>
            </span>
        </div>
    </nav>
    <!-- HERO -->
    <header class="hero">
        <div class="container px-0 mt-5">
            <div class="text-center ">
                <div class="status-icon ">
                    <i class="bi bi-check-lg fs-3 "></i>
                </div>

                <h1 class="hero-title">ชำระเงินสำเร็จ</h1>

                <p class="hero-desc">
                    การชำระเงินของคุณสำเร็จแล้ว โปรดรอการตรวจสอบจากทางร้านค้า
                </p>

                <div class="row g-2 justify-content-center hero-actions">
                    <div class="col-12 col-sm-6 col-md-4">
                        <a class="btn btn-glass w-100" href="Buyer.php">ดูสินค้าอื่นๆ</a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <a class="btn btn-glass w-100" href="order-history.php">ตรวจสอบออเดอร์</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="content-wrap">
        <div class="container">

            <div class="card card-soft mb-3">
                <div class="card-body p-3 p-md-4">

                    <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                        <div>
                            <div class="fw-bold fs-5">สินค้าของคุณ</div>
                            <div class="small-muted">สินค้าที่คุณชำระไปเมื่อเร็วๆ นี้</div>
                        </div>
                    </div>

                    <div class="mini-banner mt-3">
                        <?php if ($latestOrder): ?>
                            <?php
                            $items = [];
                            if ($latestOrder['mode'] === 'cart' && !empty($latestOrder['items_json'])) {
                                $items = json_decode($latestOrder['items_json'], true) ?: [];
                            }
                            ?>

                            <?php if ($latestOrder['mode'] === 'single'): ?>
                                <!-- ซื้อเดี่ยว -->
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-2 bg-light overflow-hidden flex-shrink-0"
                                        style="width:72px;height:72px;">
                                        <?php $img = getOrderItemImageUrl($conn, $latestOrder); ?>

                                        <img class="order-item-img"
                                            src="<?php echo htmlspecialchars($img); ?>"
                                            alt="<?php echo htmlspecialchars($latestOrder['product_name'] ?? 'สินค้า'); ?>"
                                            loading="lazy"
                                            style="width: 100%; height: 100%; object-fit: cover;"
                                            onerror="this.src='https://via.placeholder.com/64?text=No+Image';">

                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-truncate" style="font-size: 15px;">
                                            <?php echo htmlspecialchars($latestOrder['product_name'] ?? 'สินค้าเดี่ยว'); ?>
                                            <?php if (!empty($latestOrder['variant_id'])): ?>
                                                <span class="text-muted fw-normal">(<?php echo htmlspecialchars($latestOrder['variant_name'] ?? ''); ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            จำนวน: 1 ชิ้น
                                        </div>
                                        <div class="mt-2">
                                            <span class="fw-bold" style="font-size: 16px; color: #ff6f4f;">
                                                ฿<?php echo number_format((float)$latestOrder['amount'], 2); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- ตะกร้าสินค้า -->
                                <div class="vstack gap-2">
                                    <?php
                                    $previewCount = 2;
                                    $totalItems = count($items);
                                    $hasMore = $totalItems > $previewCount;
                                    $collapseId = 'order-item-detail';

                                    $visibleItems = array_slice($items, 0, $previewCount);
                                    $hiddenItems = array_slice($items, $previewCount);
                                    ?>

                                    <!-- แสดงรายการแรก -->
                                    <?php foreach ($visibleItems as $it): ?>
                                        <?php
                                        $pId = (int)($it['product_id'] ?? $it['productId'] ?? 0);
                                        $vId = (int)($it['variant_id'] ?? $it['variantId'] ?? 0);

                                        $name = (string)($it['name'] ?? 'สินค้า');
                                        $qty = (int)($it['quantity'] ?? 0);
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

                                        <div class="d-flex align-items-center gap-3 pb-2">
                                            <div class="rounded-2 bg-light overflow-hidden flex-shrink-0" style="width:68px;height:68px;">
                                                <img class="order-item-img"
                                                    src="<?php echo htmlspecialchars($img); ?>"
                                                    alt="<?php echo htmlspecialchars($name); ?>"
                                                    loading="lazy"
                                                    style="width: 100%; height: 100%; object-fit: cover;"
                                                    onerror="this.src='https://via.placeholder.com/64?text=No+Image';">
                                            </div>

                                            <div class="flex-grow-1">
                                                <div class="fw-semibold text-truncate" style="font-size: 14px;">
                                                    <?php echo htmlspecialchars($name); ?>
                                                    <?php if ($variantLabel !== ''): ?>
                                                        <span class="text-muted fw-normal">(<?php echo htmlspecialchars($variantLabel); ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="small text-muted mt-1">จำนวน: <?php echo $qty; ?> ชิ้น</div>
                                            </div>

                                            <?php if ($price > 0): ?>
                                                <div class="text-end flex-shrink-0">
                                                    <div class="fw-bold" style="font-size: 15px; color: #ff6f4f;">
                                                        ฿<?php echo number_format($price * max($qty, 1), 2); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <hr class="my-2">
                                    <?php endforeach; ?>

                                    <!-- Collapse สำหรับรายการที่เหลือ -->
                                    <?php if ($hasMore): ?>
                                        <div class="collapse" id="<?php echo htmlspecialchars($collapseId); ?>">
                                            <?php foreach ($hiddenItems as $it): ?>
                                                <?php
                                                $pId = (int)($it['product_id'] ?? $it['productId'] ?? 0);
                                                $vId = (int)($it['variant_id'] ?? $it['variantId'] ?? 0);

                                                $name = (string)($it['name'] ?? 'สินค้า');
                                                $qty = (int)($it['quantity'] ?? 0);
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

                                                <div class="d-flex align-items-center gap-3 pb-2">
                                                    <div class="rounded-2 bg-light overflow-hidden flex-shrink-0" style="width:68px;height:68px;">
                                                        <img class="order-item-img"
                                                            src="<?php echo htmlspecialchars($img); ?>"
                                                            alt="<?php echo htmlspecialchars($name); ?>"
                                                            loading="lazy"
                                                            style="width: 100%; height: 100%; object-fit: cover;"
                                                            onerror="this.src='https://via.placeholder.com/64?text=No+Image';">
                                                    </div>

                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold text-truncate" style="font-size: 14px;">
                                                            <?php echo htmlspecialchars($name); ?>
                                                            <?php if ($variantLabel !== ''): ?>
                                                                <span class="text-muted fw-normal">(<?php echo htmlspecialchars($variantLabel); ?>)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="small text-muted mt-1">จำนวน: <?php echo $qty; ?> ชิ้น</div>
                                                    </div>

                                                    <?php if ($price > 0): ?>
                                                        <div class="text-end flex-shrink-0">
                                                            <div class="fw-bold" style="font-size: 15px; color: #ff6f4f;">
                                                                ฿<?php echo number_format($price * max($qty, 1), 2); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <hr class="my-2">
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- Toggle button -->
                                        <button class="btn btn-sm btn-outline-secondary w-100 mt-3"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#<?php echo htmlspecialchars($collapseId); ?>"
                                            aria-expanded="false"
                                            aria-controls="<?php echo htmlspecialchars($collapseId); ?>">
                                            ดูทั้งหมด (<?php echo $totalItems; ?> รายการ)
                                        </button>
                                    <?php endif; ?>

                                    <!-- Summary -->
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold" style="color: #6b7280;">ยอดชำระทั้งหมด</span>
                                            <span class="fw-bold" style="font-size: 18px; color: #ff6f4f;">฿<?php echo number_format((float)$latestOrder['amount'], 2); ?></span>
                                        </div>
                                    </div>

                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <p class="mb-0">ไม่มีข้อมูลออเดอร์</p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>
    </main>

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