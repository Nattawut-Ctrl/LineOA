<?php
// utils/user_guard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/config.php';
/**
 * บังคับให้ user login ก่อนเข้า page ฝั่ง FrontEnd (Buyer)
 * คืนค่า user_id (int)
 */
function require_user_id(): int
{
    $user_id = (int)($_SESSION['user_id'] ?? 0);

    if ($user_id <= 0) {
        // redirect ไปหน้า line-entry ฝั่ง FrontEnd
        header("Location: " . FRONTEND_URL . "/Users/line-entry.php?from=shop");
        exit;
    }

    return $user_id;
}
