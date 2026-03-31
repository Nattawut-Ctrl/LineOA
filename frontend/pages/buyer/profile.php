<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once SERVICES_PATH . '/userService.php';

$conn    = connectDBWithLog();
$user_id = require_user_id();
$return_to = $_SERVER['REQUEST_URI'] ?? 'profile.php';
$return_to_q = rawurlencode($return_to);
$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/pages/users/line-entry.php?from=register");
    exit;
}

$logoutUrl = "../pages/users/line-entry.php?logout=1";
$activeMenu = 'profile';

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บัญชีของฉัน | Line-Shop</title>
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

        .profile-card {
            border-radius: 18px;
            border: none;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        }

        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
        }

        .menu-card {
            border-radius: 16px;
            border: none;
        }

        .menu-item {
            padding: 10px 0;
        }

        .menu-item i {
            width: 22px;
        }

    </style>
</head>

<body>

    <nav class="navbar topbar navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white" onclick="window.location.href='Buyer.php'">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="navbar-brand mx-auto">บัญชีของฉัน</span>
            <span class="me-3 text-white-50 small d-none d-sm-inline">
                <?php echo htmlspecialchars($user['first_name']); ?>
            </span>
        </div>
    </nav>

    <main class="container py-3">

        <!-- โปรไฟล์ -->
        <div class="card profile-card mb-3">
            <div class="card-body d-flex align-items-center gap-3">
                <img src="<?php echo htmlspecialchars($user['picture_url'] ?? ''); ?>"
                    alt="avatar"
                    class="profile-avatar border">
                <div class="flex-grow-1">
                    <div class="fw-semibold">
                        <?php
                        echo htmlspecialchars(
                            trim(($user['title'] ?? '') . ' ' . ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
                        );
                        ?>
                    </div>
                    <div class="small text-muted">
                        LINE: <?php echo htmlspecialchars($user['display_name'] ?? ''); ?>
                    </div>
                    <div class="small text-muted">
                        โทร: <?php echo htmlspecialchars($user['phone'] ?? '-'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- เมนูหลัก -->
        <div class="card menu-card mb-3">
            <div class="card-body">

                <a href="order-history.php" class="d-flex justify-content-between align-items-center text-decoration-none text-dark menu-item">
                    <div>
                        <i class="bi bi-receipt me-2 text-danger"></i>
                        <span>ออเดอร์ของฉัน</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>

                <hr class="my-1">

                <a href="notifications.php" class="d-flex justify-content-between align-items-center text-decoration-none text-dark menu-item">
                    <div>
                        <i class="bi bi-bell me-2 text-warning"></i>
                        <span>การแจ้งเตือน & สลิป</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>

                <hr class="my-1">
                <a href="buyer_address.php?return_to=<?= $return_to_q ?>" class="d-flex justify-content-between align-items-center text-decoration-none text-dark menu-item">

                    <div>
                        <i class="bi bi-geo-alt me-2"></i>
                        <span>ที่อยู่ของฉัน</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>

                <hr class="my-1">

                <a href="profile_edit.php" class="d-flex justify-content-between align-items-center text-decoration-none text-dark menu-item">
                    <div>
                        <i class="bi bi-person-fill me-2"></i>
                        <span>ข้อมูลส่วนตัว</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>

            </div>
        </div>

    </main>

     <!-- Bottom Navigation Bar -->
    <?php include FRONTEND_PATH . '/partials/buyer_bottom_nav.php'; ?>
    
</body>

</html>