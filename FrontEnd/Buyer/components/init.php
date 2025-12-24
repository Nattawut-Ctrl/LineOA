<?php
// frontend/Buyer/components/init.php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once SERVICES_PATH . '/userService.php';

// 1) DB + auth
$conn    = connectDBWithLog();
$user_id = require_user_id();

// 2) Load user (ใช้ใน navbar/profile)
$user = getUserById($conn, (int)$user_id);
if (!$user) {
    // กันกรณี user_id ค้าง / ข้อมูลหาย
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/Users/line-entry.php?from=register");
    exit;
}

// 3) Helper เล็ก ๆ (เลือกใช้)
function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// 4) URLs ที่ใช้บ่อย (ป้องกัน hardcode path)
$BUYER_BASE_URL = FRONTEND_URL . '/Buyer';
$BUYER_PAGES_URL = $BUYER_BASE_URL . '/pages';
$BUYER_ACTIONS_URL = $BUYER_BASE_URL . '/actions';
$BUYER_API_URL = $BUYER_BASE_URL . '/api';