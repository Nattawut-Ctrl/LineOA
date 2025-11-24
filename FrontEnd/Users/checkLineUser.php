<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/userService.php';

$conn = connectDBWithLog();

$line_uid      = trim($_POST['line_uid'] ?? '');
$display_name  = trim($_POST['display_name'] ?? '');
$picture_url   = trim($_POST['picture_url'] ?? '');

if ($line_uid === '') {
    die("ไม่พบ LINE UID");
}

// ✅ ใช้ service เช็ค user แทน SQL ตรง ๆ
$user = getUserByLineUid($conn, $line_uid);

if ($user) {
    // มีอยู่แล้ว → เซ็ต session เข้า shop
    $_SESSION['user_id'] = $user['id'];
    header("Location: ../Buyer/Buyer.php");
    exit;
}

// ไม่มี user → ไปหน้า Register (ชื่อไฟล์ต้องตรงเคส!)
$params = http_build_query([
    'line_uid'     => $line_uid,
    'display_name' => $display_name,
    'picture_url'  => $picture_url,
]);

header("Location: ../Users/Register.php?" . $params);
exit;
