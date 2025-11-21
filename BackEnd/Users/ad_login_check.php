<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';

$conn = connectDBWithLog();

function clean($s) { return trim($s ?? ''); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ad_login.php');
    exit;
}

$usernameOrEmail = clean($_POST['username'] ?? '');
$password        = $_POST['password'] ?? '';

if ($usernameOrEmail === '' || $password === '') {
    header('Location: ad_login.php?error=required');
    exit;
}

// ตัวอย่างสมมติ: ตาราง admins มีฟิลด์ username, email, password_hash, is_active
$sql  = "SELECT id, password_hash, is_active FROM admins 
         WHERE username = ? OR email = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if (!$admin) {
    header('Location: ad_login.php?error=invalid');
    exit;
}

if ((int)$admin['is_active'] !== 1) {
    header('Location: ad_login.php?error=inactive');
    exit;
}

// ถ้าใช้ password_hash()
if (!password_verify($password, $admin['password_hash'])) {
    header('Location: ad_login.php?error=invalid');
    exit;
}

// login สำเร็จ
$_SESSION['admin_id'] = $admin['id'];
header('Location: ../Stock/addStock.php');
exit;
