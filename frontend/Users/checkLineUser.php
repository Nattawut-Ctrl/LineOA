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

$user = getUserByLineUid($conn, $line_uid);

if ($user) {
    $needUpdate = false;
    if ($display_name !== '' && $display_name !== ($user['display_name'] ?? '')) $needUpdate = true;
    if ($picture_url !== '' && $picture_url !== ($user['picture_url'] ?? '')) $needUpdate = true;

    if ($needUpdate) {
        $sql = "UPDATE users
                SET display_name = COALESCE(NULLIF(?, ''), display_name),
                    picture_url = COALESCE(NULLIF(?, ''), picture_url)
                WHERE id = ?
                LIMIT 1";
        db_query($conn, $sql, [$display_name, $picture_url, (int)$user['id']], "ssi");
    }

    $_SESSION['user_id'] = $user['id'];
    header("Location: ../Buyer/Buyer.php");
    exit;
}

$params = http_build_query([
    'line_uid'     => $line_uid,
    'display_name' => $display_name,
    'picture_url'  => $picture_url,
]);

header("Location: ../Users/Register.php?" . $params);
exit;
