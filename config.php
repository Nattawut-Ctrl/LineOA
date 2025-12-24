<?php
require_once __DIR__ . '/shared/utils/log.php';

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

$subdir = '';

define('BASE_URL', $scheme . '://' . $host . $subdir);
define('BACKEND_URL', BASE_URL . '/backend');
define('FRONTEND_URL', BASE_URL . '/frontend');

define('SHARED_PATH', BASE_PATH . '/shared');
define('SHARED_PARTIALS_PATH', SHARED_PATH . '/partials');
define('SHARED_ASSETS_URL', BASE_URL . '/shared/assets');

// config.php
define('ADMIN_NOTIFY_EMAIL', 'admin@example.com');

if (!defined('USE_CLOUDINARY')) {
    define('USE_CLOUDINARY', true);
}

if (!defined('UPLOAD_BASE_DIR')) {
    define('UPLOAD_BASE_DIR', BASE_PATH . '/uploads');
}
if (!defined('UPLOAD_BASE_URL_PATH')) {
    define('UPLOAD_BASE_URL_PATH', 'uploads');
}

function connectDB()
{
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "line_shop";

    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
