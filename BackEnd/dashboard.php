<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once UTILS_PATH . '/db_with_log.php';

if (!isset($_SESSION['admin_id'])) {
  header("Location: " . BACKEND_URL . "/Users/ad_login.php");
  exit;
}

$conn = connectDBWithLog();

// Simple stats
$productsCount = db_query($conn, "SELECT COUNT(*) c FROM products")->fetch_assoc()['c'] ?? 0;
$variantsCount = 0;
// รวมสต็อกจาก products + product_variants
$stockRow = db_query($conn, "SELECT 
    (SELECT IFNULL(SUM(stock),0) FROM product_variants) AS c")->fetch_assoc();
if ($stockRow && isset($stockRow['c'])) {
  $variantsCount = (int)$stockRow['c'];
}
$pendingSlips  = db_query($conn, "SELECT COUNT(*) c FROM payments WHERE status='pending'")
  ->fetch_assoc()['c'] ?? 0;

$pageTitle  = "Dashboard";
$activeMenu = "dashboard";
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <?php include BACKEND_PATH . "/partials/admin_head.php"; ?>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini">
  <div class="app-wrapper">

    <?php include BACKEND_PATH . "/partials/admin_navbar.php"; ?>
    <?php include BACKEND_PATH . "/partials/admin_sidebar.php"; ?>

    <main class="app-main">

      <div class="app-content-header">
        <div class="container-fluid">
          <h3 class="mb-0 fw-semibold">Dashboard</h3>
        </div>
      </div>

      <div class="app-content">
        <div class="container-fluid">

          <div class="row g-3 mb-3">

            <div class="col-lg-4 col-12">
              <div class="small-box text-bg-primary">
                <div class="inner">
                  <h3><?= (int)$productsCount ?></h3>
                  <p>Products</p>
                </div>
                <div class="small-box-icon">
                  <i class="bi bi-bag"></i>
                </div>
                <a href="<?= BACKEND_URL ?>/Stock/addStock.php" class="small-box-footer">
                  จัดการสินค้า <i class="fas fa-arrow-circle-right"></i>
                </a>
              </div>
            </div>

            <div class="col-lg-4 col-12">
              <div class="small-box text-bg-success">
                <div class="inner">
                  <h3><?= (int)$variantsCount ?></h3>
                  <p>Variants / Stock Units</p>
                </div>
                <div class="small-box-icon">
                  <i class="bi bi-box-seam"></i>
                </div>
                <a href="<?= BACKEND_URL ?>/Stock/addStock.php" class="small-box-footer">
                  เพิ่มสต็อก <i class="fas fa-arrow-circle-right"></i>
                </a>
              </div>
            </div>

            <div class="col-lg-4 col-12">
              <div class="small-box text-bg-warning">
                <div class="inner">
                  <h3><?= (int)$pendingSlips ?></h3>
                  <p>Pending Payment Slips</p>
                </div>
                <div class="small-box-icon">
                  <i class="bi bi-receipt"></i>
                </div>
                <a href="<?= BACKEND_URL ?>/payments/list.php" class="small-box-footer">
                  ตรวจสลิป <i class="fas fa-arrow-circle-right"></i>
                </a>
              </div>
            </div>

          </div>

          <div class="card shadow-sm">
            <div class="card-header fw-semibold">Quick Actions</div>
            <div class="card-body d-flex gap-2 flex-wrap">
              <a class="btn btn-primary" href="<?= BACKEND_URL ?>/Stock/addStock.php#tabAddStock">
                <i class="bi bi-plus-circle"></i> เพิ่มสินค้า/สต็อก
              </a>
              <a class="btn btn-warning" href="<?= BACKEND_URL ?>/payments/list.php">
                <i class="bi bi-receipt"></i> ตรวจสลิปค้าง
              </a>
            </div>
          </div>

        </div>
      </div>

    </main>
    <?php include BACKEND_PATH . '/partials/admin_footer.php'; ?>
  </div>

  <?php include BACKEND_PATH . "/partials/admin_script.php"; ?>
</body>

</html>