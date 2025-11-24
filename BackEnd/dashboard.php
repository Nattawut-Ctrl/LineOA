<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: <?= BACKEND_URL ?>/Users/ad_login.php");
    exit;
}

$pageTitle = "Admin Dashboard";
$activeMenu = "dashboard";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <?php include BACKEND_PATH . "/partials/admin_head.php"; ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">

<div class="wrapper">
    <?php include BACKEND_PATH . "/partials/admin_navbar.php"; ?>
    <?php include BACKEND_PATH . "/partials/admin_sidebar.php"; ?>

    <!-- ใช้ content-wrapper แบบ AdminLTE -->
    <div class="content-wrapper p-4">
        <h2 class="mb-4">Home</h2>

        <div class="row g-4">

            <!-- Users
            <div class="col-md-4">
                <div class="dash-card" onclick="location.href='<?= BACKEND_URL ?>/Users/manage_users.php'">
                    <i class="bi bi-people"></i>
                    <p>Users</p>
                </div>
            </div> -->

            <!-- Items -->
            <div class="col-md-4">
                <div class="dash-card" onclick="location.href='<?= BACKEND_URL ?>/Stock/addStock.php'">
                    <i class="bi bi-archive"></i>
                    <p>Items</p>
                </div>
            </div>

            <!-- Payment Slips -->
            <div class="col-md-4">
                <div class="dash-card" onclick="location.href='<?= BACKEND_URL ?>/payments/list.php'">
                    <i class="bi bi-receipt"></i>
                    <p>Payment Slips</p>
                </div>
            </div>

            <!-- Backup -->
            <!-- <div class="col-md-4">
                <div class="dash-card">
                    <i class="bi bi-arrow-repeat"></i>
                    <p>Backup & Restore</p>
                </div>
            </div> -->

        </div>
    </div>

</div>

</body>
</html>
