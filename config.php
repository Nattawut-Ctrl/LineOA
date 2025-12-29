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

// define('FRONTEND_ASSETS_PATH', FRONTEND_PATH . '/assets');
// define('FRONTEND_ASSETS_URL',  FRONTEND_URL  . '/assets');

define('SHARED_PATH', BASE_PATH . '/shared');
define('SHARED_PARTIALS_PATH', SHARED_PATH . '/partials');
define('SHARED_ASSETS_URL', BASE_URL . '/shared/assets');
define('SHARED_ASSETS_PATH', SHARED_PATH . '/assets');

// config.php
define('ADMIN_NOTIFY_EMAIL', 'admin@example.com');

if (!defined('USE_CLOUDINARY')) {
    define('USE_CLOUDINARY', true);
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
define('DB_NAME', 'line_shop');

function connectDB()
{
    $host   = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
    $user   = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'root');
    $pass   = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : '');
    $dbname = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'line_shop');

    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new Exception("DB connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
