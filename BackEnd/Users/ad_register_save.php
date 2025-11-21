<?php
session_start();
require_once UTILS_PATH . '/db_with_log.php';

$conn = connectDBWithLog();

// รับเฉพาะ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$full_name        = trim($_POST['full_name']        ?? '');
$username         = trim($_POST['username']         ?? '');
$email            = trim($_POST['email']            ?? '');
$password         = $_POST['password']              ?? '';
$password_confirm = $_POST['password_confirm']      ?? '';

// 1) ตรวจว่ากรอกครบหรือไม่
if ($full_name === '' || $username === '' || $email === '' || $password === '' || $password_confirm === '') {

    writeLog(
        $conn,
        "INSERT admins (invalid input: required)",
        ['username' => $username, 'email' => $email],
        '',
        'error',
        'ad_register_save: required fields missing'
    );

    header('Location: ad_register.php?error=required');
    exit;
}

// 2) รหัสผ่านตรงกันไหม
if ($password !== $password_confirm) {

    writeLog(
        $conn,
        "INSERT admins (password mismatch)",
        ['username' => $username, 'email' => $email],
        '',
        'error',
        'ad_register_save: password mismatch'
    );

    header('Location: ad_register.php?error=password_mismatch');
    exit;
}

// 3) เช็ค username ซ้ำไหม
$resUser = db_query(
    $conn,
    "SELECT id FROM admins WHERE username = ? LIMIT 1",
    [$username],
    "s"
);

if ($resUser && $resUser->num_rows > 0) {

    writeLog(
        $conn,
        "INSERT admins (exists username)",
        ['username' => $username],
        '',
        'error',
        'ad_register_save: username already exists'
    );

    header('Location: ad_register.php?error=exists_username');
    exit;
}

// 4) เช็ค email ซ้ำไหม
$resEmail = db_query(
    $conn,
    "SELECT id FROM admins WHERE email = ? LIMIT 1",
    [$email],
    "s"
);

if ($resEmail && $resEmail->num_rows > 0) {

    writeLog(
        $conn,
        "INSERT admins (exists email)",
        ['email' => $email],
        '',
        'error',
        'ad_register_save: email already exists'
    );

    header('Location: ad_register.php?error=exists_email');
    exit;
}

// 5) สร้าง hash รหัสผ่าน
$hash = password_hash($password, PASSWORD_DEFAULT);

// 6) บันทึกลงตาราง admins
$result = db_exec(
    $conn,
    "INSERT INTO admins (username, email, password, full_name, role, status)
     VALUES (?, ?, ?, ?, ?, 'active')",
    [$username, $email, $hash, $full_name, 'admin'],
    "sssss"
);

$adminId = $conn->insert_id;

// ถ้า insert ไม่สำเร็จ
if (!$result['ok'] || !$adminId) {

    writeLog(
        $conn,
        "INSERT admins (failed)",
        ['username' => $username, 'email' => $email],
        '',
        'error',
        'ad_register_save: insert admin failed'
    );

    header('Location: ad_register.php?error=unknown');
    exit;
}

// log สำเร็จ
writeLog(
    $conn,
    "INSERT admins (success)",
    ['admin_id' => $adminId, 'username' => $username, 'email' => $email],
    '',
    'success',
    null,
    $adminId
);

// จะให้ login ให้อัตโนมัติไหม? ถ้าอยาก login เลยก็ uncomment 3 บรรทัดล่างนี้
// $_SESSION['admin_id'] = (int)$adminId;
// header('Location: ../Stock/addStock.php');
// exit;

// ตอนนี้เลือกให้กลับไปหน้า login พร้อมข้อความว่าสมัครสำเร็จ
header('Location: ad_login.php?success=registered');
exit;
