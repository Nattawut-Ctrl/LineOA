<?php
session_start();
require_once '../../utils/db_with_log.php';

$conn = connectDBWithLog();

$line_uid      = $_POST['line_uid']      ?? '';
$display_name  = $_POST['display_name']  ?? '';
$picture_url   = $_POST['picture_url']   ?? '';

if ($line_uid === '') {
    die("ไม่พบ LINE UID");
}

// ตรวจว่ามีผู้ใช้อยู่แล้วหรือยัง
$sql  = "SELECT id FROM users WHERE line_uid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $line_uid);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // ✅ เคยสมัครแล้ว → ตั้ง session แล้วไปหน้า Buyer
    $_SESSION['user_id'] = $row['id'];
    header("Location: ../Buyer/Buyer.php");
    exit;
} else {
    // ❌ ยังไม่เคยสมัคร → ส่งไป Register พร้อมแนบ line_uid + display_name
    $params = http_build_query([
        'line_uid'     => $line_uid,
        'display_name' => $display_name,
        'picture_url' => $picture_url
    ]);
    header("Location: Register.php?" . $params);
    exit;
}
