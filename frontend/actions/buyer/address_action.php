<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once SERVICES_PATH . '/userService.php';
require_once FRONTEND_PATH . '/services/AddressService.php';

$conn    = connectDBWithLog();
$user_id = require_user_id();

$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/pages/users/line-entry.php?from=register");
    exit;
}

$action = $_POST['action'] ?? '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header("Location: " . FRONTEND_URL . "/pages/buyer/buyer_address.php?error=invalid_id");
    exit;
}

try {
    if ($action === 'set_default') {
        setDefaultAddress($conn, $user_id, $id);
        header("Location: " . FRONTEND_URL . "/pages/buyer/buyer_address.php?success=set_default");
        exit;
    }

    if ($action === 'delete') {
        softDeleteAddress($conn, $user_id, $id);
        header("Location: " . FRONTEND_URL . "/pages/buyer/buyer_address.php?success=deleted");
        exit;
    }

    header("Location: " . FRONTEND_URL . "/pages/buyer/buyer_address.php?error=invalid_action");
    exit;

} catch (Throwable $e) {
    // ไม่โชว์รายละเอียด error ต่อ user
    header("Location: " . FRONTEND_URL . "/pages/buyer/buyer_address.php?error=action_failed");
    exit;
}
