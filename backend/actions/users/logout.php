<?php
session_start();

// ลบตัวแปร session ทั้งหมด
session_unset();

// ทำลาย session
session_destroy();

// ส่งกลับไปหน้า login
header("Location: " . BACKEND_URL . "/pages/users/login.php");
exit;