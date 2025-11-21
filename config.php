<?php
require_once __DIR__ . '/utils/log.php';

// config_path.php
define('BASE_PATH', __DIR__);
define('UTILS_PATH', BASE_PATH . '/utils');
define('FRONTEND_PATH', BASE_PATH . '/FrontEnd');
define('BACKEND_PATH', BASE_PATH . '/BackEnd');
define('SERVICES_PATH', BASE_PATH . '/services');

function connectDB() {
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
?>