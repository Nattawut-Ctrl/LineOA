<?php
session_start();

require_once '../../utils/db_with_log.php';
$conn = connectDBWithLog();

// รับเฉพาะ POST เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$email    = trim($_POST['email']    ?? '');
$password =       $_POST['password'] ?? '';

// ถ้าขาดข้อมูล → redirect กลับไปพร้อม error=required
if ($email === '' || $password === '') {

    // log เคส input ไม่ครบ
    writeLog(
        $conn,
        "SELECT * FROM admins (login required error)",
        ['identity' => $email],
        '',
        'error',
        'ad_login_check: required fields missing'
    );

    header('Location: ad_login.php?error=required');
    exit;
}

// -----------------------------
// 1) หา admin จาก email / username
//    (ปรับชื่อ table/column ให้ตรงกับของจริง)
// -----------------------------

$sql  = "SELECT id, email, username, password, status 
         FROM admins 
         WHERE email = ? OR username = ?
         LIMIT 1";

$res = db_query($conn, $sql, [$email, $email], "ss");

if (!$res || $res->num_rows === 0) {
    // ไม่เจอผู้ใช้ → invalid

    writeLog(
        $conn,
        "SELECT * FROM admins (login invalid user)",
        ['identity' => $email],
        '',
        'error',
        'ad_login_check: invalid email/username'
    );

    header('Location: ad_login.php?error=invalid');
    exit;
}

$admin = $res->fetch_assoc();

// -----------------------------
// 2) เช็คสถานะบัญชี (ถ้ามีคอลัมน์ status)
//    สมมุติใช้ค่า 'active' = เปิดใช้งาน
// -----------------------------
if (isset($admin['status']) && $admin['status'] !== 'active') {

    writeLog(
        $conn,
        "SELECT * FROM admins (login inactive)",
        ['admin_id' => $admin['id'], 'identity' => $email],
        '',
        'error',
        'ad_login_check: inactive account'
    );

    header('Location: ad_login.php?error=inactive');
    exit;
}

// -----------------------------
// 3) ตรวจรหัสผ่าน
//    สมมติคอลัมน์ password เก็บ password_hash()
// -----------------------------

$hash = $admin['password'];

if (!password_verify($password, $hash)) {

    writeLog(
        $conn,
        "SELECT * FROM admins (login wrong password)",
        ['admin_id' => $admin['id'], 'identity' => $email],
        '',
        'error',
        'ad_login_check: wrong password'
    );

    header('Location: ad_login.php?error=invalid');
    exit;
}

// -----------------------------
// 4) ล็อกอินสำเร็จ → set session
// -----------------------------
$_SESSION['admin_id'] = (int)$admin['id'];

// ถ้าอยากทำ remember me จริง ๆ ให้ไปทำระบบ token + cookie เพิ่มได้
// ตอนนี้ถือว่าเช็คเฉพาะ session พอ

writeLog(
    $conn,
    "SELECT * FROM admins (login success)",
    ['admin_id' => $admin['id'], 'identity' => $email],
    '',
    'success',
    null,
    (int)$admin['id']
);

// เด้งเข้าแดชบอร์ด
header('Location: ../Stock/addStock.php');
exit;
