<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/SlipService.php';

if (!isset($_SESSION['admin_id'])) {
  header('Location: ../Users/ad_login.php');
  exit;
}

$pageTitle  = "ตรวจสลิปการโอน";
$activeMenu = "slip";

$conn = connectDBWithLog();
$payments = getAllPayments($conn);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <?php include BACKEND_PATH . '/partials/admin_navbar.php'; ?>
  <?php include BACKEND_PATH . '/partials/admin_sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">

        <div class="card">
          <div class="card-header"><h5 class="mb-0">รายการสลิปทั้งหมด</h5></div>
          <div class="card-body">
            <table class="table table-bordered table-hover align-middle">
              <thead>
                <tr>
                  <th>ID</th><th>User</th><th>Amount</th><th>Status</th><th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($payments as $p): ?>
                  <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['display_name']) ?></td>
                    <td><?= number_format($p['amount'],2) ?></td>
                    <td>
                      <span class="badge bg-<?= $p['status']==='pending'?'warning':($p['status']==='approved'?'success':'danger') ?>">
                        <?= $p['status'] ?>
                      </span>
                    </td>
                    <td>
                      <a class="btn btn-sm btn-info" href="view.php?id=<?= $p['id'] ?>">ดูสลิป</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </section>
  </div>

  <?php include BACKEND_PATH . '/partials/admin_footer.php'; ?>
</div>
<?php include BACKEND_PATH . '/partials/admin_script.php'; ?>
</body>
</html>
