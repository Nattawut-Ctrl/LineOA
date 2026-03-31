<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once SERVICES_PATH . '/userService.php';
require_once UTILS_PATH . '/image_helper.php';

$conn    = connectDBWithLog();
$user_id = require_user_id();

$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/pages/users/line-entry.php?from=register");
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

$activeMenu = 'notifications';
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

     <!-- Bottom Navigation Bar -->
    <?php include FRONTEND_PATH . '/partials/buyer_bottom_nav.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.BuyerNoti && typeof window.BuyerNoti.markAllRead === 'function') {
                window.BuyerNoti.markAllRead();
            } else {
                fetch('<?= FRONTEND_URL ?>/api/buyer/mark_buyer_notifications_read.php', { method: 'POST', credentials: 'same-origin' })
                    .catch(err => console.error('mark read error', err));
            }
        });
    </script>
</body>

</html>