<?php
require_once __DIR__ . '/utils/log.php';

// config_path.php
define('BASE_PATH', __DIR__);
define('UTILS_PATH', BASE_PATH . '/utils');
define('FRONTEND_PATH', BASE_PATH . '/FrontEnd');
define('BACKEND_PATH', BASE_PATH . '/BackEnd');
define('SERVICES_PATH', BASE_PATH . '/services');
// define('BASE_URL', 'https://joline-garreted-fabiola.ngrok-free.dev');

// ===== Auto BASE_URL ตาม host ปัจจุบัน (รองรับ ngrok ด้วย) =====
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

$subdir = '';

define('BASE_URL', $scheme . '://' . $host . $subdir);
define('BACKEND_URL', BASE_URL . '/BackEnd');
define('FRONTEND_URL', BASE_URL . '/FrontEnd');

// ===== Upload & Cloudinary config =====

// จะเปิด/ปิด Cloudinary จากตรงนี้ก็ได้ (หรือใช้แบบ auto จากไฟล์ config ก็ได้)
if (!defined('USE_CLOUDINARY')) {
    define('USE_CLOUDINARY', true);
}

// root เก็บไฟล์ local (โฟลเดอร์ /uploads ที่มีอยู่แล้ว)
if (!defined('UPLOAD_BASE_DIR')) {
    define('UPLOAD_BASE_DIR', BASE_PATH . '/uploads');
}
if (!defined('UPLOAD_BASE_URL_PATH')) {
    // path ที่จะเก็บลง DB เช่น uploads/products/xxx.jpg
    define('UPLOAD_BASE_URL_PATH', 'uploads');
}

function connectDB()
{
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "line_shop";   // ชื่อเดียวกับที่ CREATE DATABASE

    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
