<?php
// ===== Database =====
define('DB_HOST', 'localhost');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASSWORD');
define('DB_NAME', 'YOUR_DB_NAME');

// ===== Base Paths =====
// เปลี่ยนเป็นโดเมนจริงเวลารันบนโฮสต์ เช่น https://your-domain.com
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

// ใส่ subdir ถ้าโปรเจกต์อยู่ในโฟลเดอร์ เช่น /LineOA-main
$subdir = '/LineOA-main';

define('BASE_URL', $scheme . '://' . $host . $subdir);
define('BASE_PATH', __DIR__);

define('FRONTEND_PATH', BASE_PATH . '/FrontEnd');
define('BACKEND_PATH',  BASE_PATH . '/BackEnd');
define('UTILS_PATH',    BASE_PATH . '/utils');
define('SERVICES_PATH', BASE_PATH . '/services');
