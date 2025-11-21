<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';

$conn = connectDBWithLog();

function redirectWithError($code)
{
    header('Location: ad_login.php?error=' . $code);
    exit;
}

// รับค่าจากฟอร์ม
$emailOrUsername = trim($_POST['email'] ?? '');
$password        = trim($_POST['password'] ?? '');

// เช็กกรอกครบไหม
if ($emailOrUsername === '' || $password === '') {
    redirectWithError('required');
}

try {

    // ดึง admin ตาม username/email
    $sql = "
        SELECT id, username, email, password, status
        FROM admins
        WHERE email = ? OR username = ?
        LIMIT 1
    ";
    $res = db_query($conn, $sql, [$emailOrUsername, $emailOrUsername], "ss");

    if (!$res || $res->num_rows === 0) {
        redirectWithError('invalid');
    }

    $admin = $res->fetch_assoc();

    // สถานะไม่ active
    if ($admin['status'] !== 'active') {
        redirectWithError('inactive');
    }

    // verify password hash
    if (!password_verify($password, $admin['password'])) {
        redirectWithError('invalid');
    }

    // login สำเร็จ
    $_SESSION['admin_id']    = $admin['id'];
    $_SESSION['admin_name']  = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];

    header('Location: ../Stock/addStock.php');
    exit;

} catch (Exception $e) {
    redirectWithError('unknown');
}
