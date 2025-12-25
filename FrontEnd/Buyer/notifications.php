<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once SERVICES_PATH . '/userService.php';
require_once UTILS_PATH . '/image_helper.php';

$conn    = connectDBWithLog();
$user_id = require_user_id();

$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/Users/line-entry.php?from=register");
    exit;
}

$sql = "SELECT * FROM payments
        WHERE user_id = ?
        ORDER BY created_at DESC";
$res = db_query($conn, $sql, [$user_id], "i");
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}

function buildMessage($row)
{
    $base = "คุณสั่งซื้อสินค้ายอด " . number_format((float)$row['amount'], 2) . " บาท";

    if ($row['status'] === 'pending') {
        return $base . " ระบบกำลังตรวจสอบสลิปของคุณอยู่";
    } elseif ($row['status'] === 'approved') {
        return $base . " และสลิปของคุณได้รับการอนุมัติแล้ว 🎉";
    } elseif ($row['status'] === 'rejected') {
        return $base . " แต่สลิปถูกปฏิเสธ กรุณาติดต่อผู้ดูแลระบบ";
    }
    return $base;
}

function statusIcon($status)
{
    switch ($status) {
        case 'approved':
            return 'bi-check-circle-fill text-success';
        case 'rejected':
            return 'bi-x-circle-fill text-danger';
        case 'pending':
        default:
            return 'bi-hourglass-split text-warning';
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งเตือน / สลิป | Line-Shop</title>
    <?php require_once SHARED_PARTIALS_PATH . '/bootstrap.php'; ?>

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

        .noti-item {
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
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

    <nav class="navbar topbar navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white" onclick="window.location.href='Buyer.php'">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="navbar-brand mx-auto">แจ้งเตือน / สลิป</span>
            <span class="me-3 text-white-50 small d-none d-sm-inline">
                <?php echo htmlspecialchars($user['first_name']); ?>
            </span>
        </div>
    </nav>

    <main class="container py-3">

        <?php if (empty($rows)): ?>
            <div class="text-center text-muted mt-5">
                <i class="bi bi-bell-slash fs-1 mb-2 d-block"></i>
                <p>ยังไม่มีการแจ้งเตือน</p>
            </div>
        <?php else: ?>
            <div class="list-unstyled">
                <?php foreach ($rows as $row): ?>
                    <div class="noti-item p-3 mb-2 d-flex gap-3">
                        <div class="flex-shrink-0">
                            <div
                                class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                style="width:40px;height:40px;">
                                <i class="bi <?php echo statusIcon($row['status']); ?>"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small fw-semibold mb-1">
                                <?php echo htmlspecialchars(buildMessage($row)); ?>
                            </div>
                            <div class="small text-muted">
                                ออเดอร์ #<?php echo (int)$row['id']; ?> ·
                                วันที่: <?php echo htmlspecialchars($row['created_at']); ?>
                            </div>
                            <?php if (!empty($row['slip_path'])): ?>
                                <?php
                                $slipUrl = buildImageUrl($row['slip_path']);
                                ?>
                                <div class="mt-1">
                                    <a href="<?= htmlspecialchars($slipUrl) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        ดูสลิป
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer Bar -->
    <nav class="bottom-nav">
        <a href="Buyer.php" class="nav-item" id="nav-home">
            <i class="bi bi-house-door"></i>
            <span>หน้าแรก</span>
        </a>

        <a href="order-history.php" class="nav-item" id="nav-orders">
            <i class="bi bi-receipt"></i>
            <span>ออเดอร์ของฉัน</span>
        </a>

        <a href="notifications.php" class="nav-item active" id="nav-noti">
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
            fetch('<?= FRONTEND_URL ?>/utils/mark_buyer_notifications_read.php', {
                    method: 'POST'
                })
                .catch(err => console.error('mark read error', err));
        });
    </script>
</body>

</html>