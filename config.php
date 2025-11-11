<?php
// Database configuration - แก้ค่าให้ตรงกับสภาพแวดล้อมของคุณ
define('DB_HOST', 'localhost');
define('DB_NAME', 'test');
define('DB_USER', 'root');
define('DB_PASS', ''); // ถ้าใช้รหัสผ่านให้กรอกที่นี่

// ฟังก์ชันเชื่อมต่อฐานข้อมูล
function connectDB() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if (!$conn) {
        // หากเชื่อมต่อไม่สำเร็จให้แสดงข้อผิดพลาด
        die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . mysqli_connect_error());
    }
    return $conn;
}
?>
