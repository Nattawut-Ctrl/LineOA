<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/slipService.php';

if (!isset($_SESSION['admin_id'])) {
  header('Location: ' . BACKEND_URL . '/Users/ad_login.php');
  exit;
}

$pageTitle  = "ดูสลิป";
$activeMenu = "slip";

$conn = connectDBWithLog();
$id = (int)($_GET['id'] ?? 0);
$payment = getPaymentById($conn, $id);
if (!$payment) die("Payment not found");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary" side>
<div class="app-wrapper">

  <?php include BACKEND_PATH . '/partials/admin_navbar.php'; ?>
  <?php include BACKEND_PATH . '/partials/admin_sidebar.php'; ?>

  <main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3 class="mb-0"><?= htmlspecialchars($pageTitle ?? "") ?></h3>
      <ol class="breadcrumb float-sm-end">
        <li class="breadcrumb-item"><a href="<?= BACKEND_URL ?>/dashboard.php">Home</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle ?? "") ?></li>
      </ol>
    </div>
  </div>
  <div class="app-content">
    <div class="container-fluid">

    <section class="content pt-3">
      <div class="container-fluid">

        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">สลิป #<?= $payment['id'] ?></h5>
          </div>
          <div class="card-body">
            <p><strong>ผู้ใช้:</strong> <?= htmlspecialchars($payment['display_name']) ?></p>
            <p><strong>ยอดเงิน:</strong> <?= number_format($payment['amount'],2) ?> บาท</p>
            <p><strong>สถานะ:</strong> <?= $payment['status'] ?></p>

            <img src="/<?= $payment['slip_path'] ?>" class="img-fluid my-3" style="max-width:420px">

            <div class="mt-3">
              <a href="update_status.php?id=<?= $id ?>&status=approved"
                 class="btn btn-success me-2">อนุมัติ</a>

              <a href="update_status.php?id=<?= $id ?>&status=rejected"
                 class="btn btn-danger me-2">ปฏิเสธ</a>

              <a href="list.php" class="btn btn-secondary">กลับ</a>
            </div>
          </div>
        </div>

      </div>
    </section>
    </div>
  </div>
</main>

  <?php include BACKEND_PATH . '/partials/admin_footer.php'; ?>
</div>
<?php include BACKEND_PATH . '/partials/admin_script.php'; ?>
</body>
</html>
