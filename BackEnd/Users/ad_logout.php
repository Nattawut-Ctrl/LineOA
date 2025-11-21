<?php
session_start();

// ลบตัวแปร session ทั้งหมด
session_unset();

// ทำลาย session
session_destroy();

// ส่งกลับไปหน้า login
header("Location: ad_login.php");
exit;
