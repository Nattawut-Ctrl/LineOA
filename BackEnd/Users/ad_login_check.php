<?php
session_start();

require_once '../../utils/db_with_log.php';
$conn = connectDB();

function redirectWithError($code) {
    header('Location: ad_login.php?error=' . $code);
    exit;
}

// รับค่าจากฟอร์ม
$emailOrUsername = trim($_POST['email'] ?? '');
$password        = trim($_POST['password'] ?? '');

// เช็กกรอกครบไหม
if ($emailOrUsername === '' || $password === '') {
    redirectWithError('required'); // ไปเข้า case 'required' ใน ad_login.php
}

try {
    // ใช้ email หรือ username ก็ได้
    $sql = "
        SELECT id, username, email, password_hash, status
        FROM admins
        WHERE email = ? OR username = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // ถ้า prepare ไม่ได้ ให้ถือว่า error ทั่วไป
        redirectWithError('unknown');
    }

    $stmt->bind_param('ss', $emailOrUsername, $emailOrUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    // ไม่พบผู้ใช้
    if (!$admin) {
        redirectWithError('invalid');
    }

    // สถานะ inactive
    if ($admin['status'] !== 'active') {
        redirectWithError('inactive');
    }

    // ตรวจรหัสผ่าน (hash)
    if (!password_verify($password, $admin['password_hash'])) {
        redirectWithError('invalid');
    }

    // ถ้าผ่านทุกอย่าง -> login สำเร็จ
    $_SESSION['admin_id']    = $admin['id'];
    $_SESSION['admin_name']  = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];

    // TODO: ถ้าจะทำ remember me แบบจริงจังควรทำตาราง token แยก
    // ตอนนี้ยังไม่ทำก็ได้ ใช้แค่ session ปกติ

    // เด้งไปหน้า addStock (ตรงกับใน ad_login.php)
    header('Location: ../Stock/addStock.php');
    exit;
} catch (Exception $e) {
    // ผิดพลาดอื่น ๆ
    redirectWithError('unknown');
}
