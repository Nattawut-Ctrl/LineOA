<?php

// ✅ เซ็ต timezone ให้เป็น Asia/Bangkok
date_default_timezone_set('Asia/Bangkok');

// ✅ ตั้ง error_log เพื่อ debug
$logPath = __DIR__ . '/storage/logs/php-error.log';
if (!is_dir(dirname($logPath))) {
    @mkdir(dirname($logPath), 0777, true);
}
ini_set('error_log', $logPath);

// config_path.php
define('BASE_PATH', __DIR__);
define('UTILS_PATH', BASE_PATH . '/shared/utils');
define('FRONTEND_PATH', BASE_PATH . '/frontend');
define('BACKEND_PATH', BASE_PATH . '/backend');
define('SERVICES_PATH', BASE_PATH . '/shared/services');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

$scheme = $https ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

$subdir = '';
if (preg_match('#^(.*?)/(frontend|backend)(/.*)?$#i', $script, $m)) {
    $subdir = rtrim($m[1], '/');
}
 
define('BASE_URL', $scheme . '://' . $host . $subdir);

define('BACKEND_URL', BASE_URL . '/backend');
define('FRONTEND_URL', BASE_URL . '/frontend');

define('BACKEND_ASSETS_PATH', BACKEND_PATH . '/assets');
define('BACKEND_ASSETS_URL',  BACKEND_URL  . '/assets');

define('SHARED_PATH', BASE_PATH . '/shared');
define('SHARED_PARTIALS_PATH', SHARED_PATH . '/partials');
define('SHARED_ASSETS_URL', BASE_URL . '/shared/assets');
define('SHARED_ASSETS_PATH', SHARED_PATH . '/assets');

define('OCR_PYTHON','python');
define('OCR_SCRIPT_PATH', BASE_PATH . '/ocr/ocr_slip.py');
define('OCR_TEMP_PATH', BASE_PATH . '/storage/uploads/ocr_temp');

// Load mail config ก่อน เพื่อให้ได้ ADMIN_NOTIFY_EMAIL
require_once __DIR__ . '/mail_config.php';

// ✅ ตั้งค่าการอัปโหลดไฟล์
if (!defined('USE_CLOUDINARY')) {
    define('USE_CLOUDINARY', true); // เปลี่ยนเป็น false ถ้าไม่ใช้ Cloudinary
}

if (!defined('UPLOAD_BASE_DIR')) {
    define('UPLOAD_BASE_DIR', BASE_PATH . '/storage/uploads');
}
if (!defined('UPLOAD_BASE_URL_PATH')) {
    define('UPLOAD_BASE_URL_PATH', 'storage/uploads');
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'line_shop_2promax');

define('LINE_ACCESS_TOKEN', 'SVrX1IN+WwovH8JylFGmYhEl6qNDHnHuTySn/ztubkycXbxYSGFRBcTjOdGqr5zBDIuGbjKX03ig9c6AQw31Z3L31BynMZ/dWIY3zn1zNNmCStR772zDHyejcGDAoUCSftLn30LPaOU5brtZK0UwQAdB04t89/1O/w1cDnyilFU=');

// ✅ ฟังก์ชันเชื่อมต่อฐานข้อมูล
function connectDB()
{
    $host   = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
    $user   = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'root');
    $pass   = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : '');
    $dbname = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'line_shop_2promax');

    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new Exception("DB connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    $conn->query("SET SESSION time_zone='+07:00'");
    return $conn;
}
