<?php
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
