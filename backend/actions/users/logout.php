<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';

// ลบตัวแปร session ทั้งหมด
session_unset();

// ทำลาย session
session_destroy();

// ส่งกลับไปหน้า login
header("Location: " . BACKEND_URL . "/pages/users/login.php");
exit;