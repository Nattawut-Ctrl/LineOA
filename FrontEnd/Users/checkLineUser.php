<?php
session_start();
require_once UTILS_PATH . '/db_with_log.php';

$conn = connectDBWithLog();

$line_uid      = $_POST['line_uid']      ?? '';
$display_name  = $_POST['display_name']  ?? '';
$picture_url   = $_POST['picture_url']   ?? '';

if ($line_uid === '') {
    die("ไม่พบ LINE UID");
}

// ตรวจว่ามีผู้ใช้อยู่แล้วหรือยัง
$sqlCheck  = "SELECT id FROM users WHERE line_uid = ?";
$res = db_query($conn, $sqlCheck, [$line_uid], "s");

if ($res && $res->num_rows > 0) {
    // มีอยู่แล้ว
    $row = $res->fetch_assoc();
    $_SESSION['user_id'] = $row['id'];
    header("Location: ../Buyer/Buyer.php");
    exit;
} else {
    $params = http_build_query([
        'line_uid'     => $line_uid,
        'display_name' => $display_name,
        'picture_url'  => $picture_url,
    ]);
    header("Location: ../Users/register.php?" . $params);
    exit;
}