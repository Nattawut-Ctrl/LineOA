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

$addressId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $addressId > 0;

$address = [
    'full_name' => '',
    'phone' => '',
    'address_line' => '',
    'subdistrict' => '',
    'district' => '',
    'province' => '',
    'postal_code' => '',
    'note' => '',
    'label' => '',
    'is_default' => 0
];

if ($editing) {
    $found = getUserAddressById($conn, $user_id, $addressId);
    if (!$found) {
        die("ไม่พบที่อยู่");
    }
    $address = $found;
} else {
    $userPhone = trim($user['phone'] ?? '');
    if ($address['phone'] === '' && $userPhone !== '') {
        $address['phone'] = $userPhone;
    }
}

function updateUserPhone(mysqli $conn, int $user_id, string $phone): bool
{
    $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ? LIMIT 1");
    $stmt->bind_param("si", $phone, $user_id);
    $stmt->execute();
    $ok = ($stmt->affected_rows >= 0);
    $stmt->close();
    return $ok;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = [
        'full_name'    => trim($_POST['full_name'] ?? ''),
        'phone'        => trim($_POST['phone'] ?? ''),
        'address_line' => trim($_POST['address_line'] ?? ''),
        'subdistrict'  => trim($_POST['subdistrict'] ?? ''),
        'district'     => trim($_POST['district'] ?? ''),
        'province'     => trim($_POST['province'] ?? ''),
        'postal_code'  => trim($_POST['postal_code'] ?? ''),
        'note'         => trim($_POST['note'] ?? ''),
        'label'        => trim($_POST['label'] ?? ''),
        'is_default'   => isset($_POST['is_default']) ? 1 : 0,
    ];

    if ($data['phone'] === '') {
        die("กรุณากรอกเบอร์โทร");
    }

    if ($editing) {
        updateAddress($conn, $user_id, $addressId, $data);
    } else {
        createAddress($conn, $user_id, $data);
    }

    if (!empty($_POST['update_user_phone'])) {
        updateUserPhone($conn, $user_id, $data['phone']);
    }

    header("Location: buyer_address.php?success=" . ($editing ? "แก้ไขที่อยู่แล้ว" : "เพิ่มที่อยู่แล้ว"));
    exit;
}
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title><?= $editing ? 'แก้ไขที่อยู่' : 'เพิ่มที่อยู่ใหม่' ?></title>
    <?php require_once SHARED_PARTIALS_PATH . '/bootstrap.php'; ?>
    <style>
        .topbar {
            background: linear-gradient(90deg,
                    rgba(238, 77, 45, 0.97),
                    rgba(255, 143, 90, 0.97));
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar topbar navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white" onclick="window.location.href='buyer_address.php'">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="navbar-brand mx-auto"><?= $editing ? 'แก้ไขที่อยู่' : 'เพิ่มที่อยู่ใหม่' ?></span>
            <span class="me-3 text-white-50 small d-none d-sm-inline">
                <?php echo htmlspecialchars($user['first_name']); ?>
            </span>
        </div>
    </nav>

    <div class="container py-3" style="max-width:720px;">

        <form method="post" class="card">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">ชื่อผู้รับ</label>
                        <input name="full_name" class="form-control" required value="<?= htmlspecialchars($address['full_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">เบอร์โทร</label>
                        <input name="phone" class="form-control" maxlength="10" required value="<?= htmlspecialchars($address['phone']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">ที่อยู่ (บ้านเลขที่/ถนน/หมู่)</label>
                        <input name="address_line" class="form-control" required value="<?= htmlspecialchars($address['address_line']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ตำบล/แขวง</label>
                        <input name="subdistrict" class="form-control" required value="<?= htmlspecialchars($address['subdistrict']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">อำเภอ/เขต</label>
                        <input name="district" class="form-control" required value="<?= htmlspecialchars($address['district']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">จังหวัด</label>
                        <input name="province" class="form-control" required value="<?= htmlspecialchars($address['province']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">รหัสไปรษณีย์</label>
                        <input name="postal_code" class="form-control" maxlength="5" required value="<?= htmlspecialchars($address['postal_code']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ป้ายกำกับ</label>
                        <select name="label" class="form-select">
                            <?php
                            $labels = ['', 'บ้าน', 'ที่ทำงาน', 'อื่น ๆ'];
                            foreach ($labels as $lb):
                                $sel = ($address['label'] === $lb) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($lb) ?>" <?= $sel ?>><?= $lb === '' ? 'ไม่ระบุ' : $lb ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">โน้ตถึงคนส่งของ</label>
                        <input name="note" class="form-control" value="<?= htmlspecialchars($address['note']) ?>">
                    </div>

                    <div class="col-12">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_default" id="is_default"
                                <?= ((int)$address['is_default'] === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_default">
                                ตั้งเป็นที่อยู่เริ่มต้น
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button class="btn btn-dark">
                        <?= $editing ? 'บันทึกการแก้ไข' : 'บันทึกที่อยู่' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

</body>

</html>