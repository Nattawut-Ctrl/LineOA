<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();

if (!isset($_SESSION['admin_id'])) {
  header("Location: " . BACKEND_URL . "/pages/users/login.php");
  exit;
}

$conn = connectDBWithLog();

$productsCount = db_query($conn, "SELECT COUNT(*) c FROM products")->fetch_assoc()['c'] ?? 0;
$profitSummary = 0;
$profitRow = db_query($conn, "SELECT COALESCE(SUM(a.qty * (a.unit_sell_price - a.unit_cost_price)), 0) AS profit FROM lot_allocations a")->fetch_assoc();
if ($profitRow && isset($profitRow['profit'])) {
  $profitSummary = (float)$profitRow['profit'];
}
$pendingSlips  = db_query($conn, "SELECT COUNT(*) c FROM payments WHERE status='pending'")
  ->fetch_assoc()['c'] ?? 0;

$pageTitle  = "แดชบอร์ด";
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
          <h3 class="mb-0 fw-semibold">แดชบอร์ด</h3>
        </div>
        
      </div>

      <div class="app-content">
        <div class="container-fluid">

          <div class="row g-3 mb-3">

            <div class="col-lg-4 col-12">
              <div class="small-box text-bg-primary">
                <div class="inner">
                  <h3><?= (int)$productsCount ?></h3>
                  <p>รายการสินค้า</p>
                </div>
                <div class="small-box-icon">
                  <i class="bi bi-bag"></i>
                </div>
                <a href="<?= BACKEND_URL ?>/pages/stock/addStock.php" class="small-box-footer">
                  จัดการสินค้า <i class="fas fa-arrow-circle-right"></i>
                </a>
              </div>
            </div>

            <div class="col-lg-4 col-12">
              <div class="small-box text-bg-info">
                <div class="inner">
                  <h3><?= number_format($profitSummary, 2) ?> ฿</h3>
                  <p>สรุปกำไร</p>
                </div>
                <div class="small-box-icon">
                  <i class="bi bi-cash"></i>
                </div>
                <a href="<?= BACKEND_URL ?>/pages/stock/profit_summary.php" class="small-box-footer">
                  ดูรายการชำระเงิน <i class="fas fa-arrow-circle-right"></i>
                </a>
              </div>
            </div>

            <div class="col-lg-4 col-12">
              <div class="small-box text-bg-warning">
                <div class="inner">
                  <h3><?= (int)$pendingSlips ?></h3>
                  <p>สลิปที่ยังไม่ได้ตรวจสอบ</p>
                </div>
                <div class="small-box-icon">
                  <i class="bi bi-receipt"></i>
                </div>
                <a href="<?= BACKEND_URL ?>/pages/payments/list.php" class="small-box-footer">
                  ตรวจสลิป <i class="fas fa-arrow-circle-right"></i>
                </a>
              </div>
            </div>
          </div>

          <div class="card shadow-sm">
            <div class="card-header fw-semibold">การดำเนินการด่วน</div>
            <div class="card-body d-flex gap-2 flex-wrap">
              <a class="btn btn-primary" href="<?= BACKEND_URL ?>/pages/stock/addStock.php#tabAddStock">
                <i class="bi bi-plus-circle"></i> เพิ่มสินค้า/สต็อก
              </a>
              <a class="btn btn-warning" href="<?= BACKEND_URL ?>/pages/payments/list.php">
                <i class="bi bi-receipt"></i> ตรวจสลิปค้าง
              </a>
            </div>
          </div>

        </div>
      </div>

    </main>
  </div>

  <?php include BACKEND_PATH . "/partials/admin_script.php"; ?>
</body>

</html>