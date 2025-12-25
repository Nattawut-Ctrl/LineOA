<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once SERVICES_PATH . '/userService.php';
require_once __DIR__ . '/../services/AddressService.php';

$conn    = connectDBWithLog();
$user_id = require_user_id();

$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/Users/line-entry.php?from=register");
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$id     = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($action === 'set_default' && $id > 0) {
    setDefaultAddress($conn, $user_id, $id);
    header("Location: buyer_address.php?success=ตั้งค่าเริ่มต้นแล้ว");
    exit;
}

if ($action === 'delete' && $id > 0) {
    softDeleteAddress($conn, $user_id, $id);
    header("Location: buyer_address.php?success=ลบที่อยู่แล้ว");
    exit;
}

$addresses = getUserAddresses($conn, $user_id);

function formatPhone(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (preg_match('/^(\d{3})(\d{3})(\d{4})$/', $digits, $m)) {
        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }
    return $phone;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ที่อยู่ของฉัน</title>
    <?php require_once SHARED_PARTIALS_PATH . '/bootstrap.php'; ?>

    <style>
        .topbar {
            background: linear-gradient(90deg,
                    rgba(238, 77, 45, 0.97),
                    rgba(255, 143, 90, 0.97));
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

<body class="bg-light">

    <nav class="navbar topbar navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white" onclick="window.location.href='profile.php'">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="navbar-brand mx-auto">ที่อยู่ของฉัน</span>
            <span class="me-3 text-white-50 small d-none d-sm-inline">
                <?php echo htmlspecialchars($user['first_name']); ?>
            </span>
        </div>
    </nav>

    <div class="container py-3" style="max-width:720px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">ที่อยู่จัดส่ง</h5>
            <a class="btn btn-dark btn-sm" href="buyer_address_form.php">
                <i class="bi bi-plus-lg me-1"></i>เพิ่มที่อยู่ใหม่
            </a>
        </div>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success py-2"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>

        <?php if (empty($addresses)): ?>
            <div class="card">
                <div class="card-body text-center text-muted">
                    ยังไม่มีที่อยู่จัดส่ง
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($addresses as $a): ?>
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($a['full_name']) ?>
                                <span class="text-muted fw-normal ms-2">
                                    <?= htmlspecialchars(formatPhone($a['phone'])) ?>
                                </span>
                            </div>

                            <div class="text-muted small">
                                เลขที่ <?= htmlspecialchars($a['address_line']) ?>,
                                ตำบล<?= htmlspecialchars($a['subdistrict']) ?>,
                                อำเภอ<?= htmlspecialchars($a['district']) ?>,
                                จังหวัด<?= htmlspecialchars($a['province']) ?>,
                                <?= htmlspecialchars($a['postal_code']) ?>
                            </div>

                            <div class="mt-2 d-flex gap-2 align-items-center flex-wrap">
                                <?php if (!empty($a['label'])): ?>
                                    <span class="badge text-bg-secondary"><?= htmlspecialchars($a['label']) ?></span>
                                <?php endif; ?>

                                <?php if ((int)$a['is_default'] === 1): ?>
                                    <span class="badge text-bg-danger">ค่าเริ่มต้น</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-end">
                            <a class="btn btn-outline-secondary btn-sm mb-1" href="buyer_address_form.php?id=<?= (int)$a['id'] ?>">
                                แก้ไข
                            </a>

                            <form method="post" action="buyer_address.php" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm mb-1"
                                    onclick="return confirm('ลบที่อยู่นี้ใช่ไหม?')">
                                    ลบ
                                </button>
                            </form>

                            <?php if ((int)$a['is_default'] !== 1): ?>
                                <form method="post" action="buyer_address.php" class="d-inline">
                                    <input type="hidden" name="action" value="set_default">
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button type="submit" class="btn btn-dark btn-sm">
                                        ตั้งเป็นค่าเริ่มต้น
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>

    </div>

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
</body>

</html>