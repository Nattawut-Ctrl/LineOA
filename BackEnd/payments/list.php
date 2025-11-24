<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/slipService.php';

if (!isset($_SESSION['admin_id'])) {
  header('Location: ' . BACKEND_URL . '/Users/ad_login.php');
  exit;
}

$pageTitle  = "ตรวจสลิปการโอน";
$activeMenu = "slip";

$conn = connectDBWithLog();
$payments = function_exists('getAllPayments') ? getAllPayments($conn) : [];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

  <?php include BACKEND_PATH . '/partials/admin_navbar.php'; ?>
  <?php include BACKEND_PATH . '/partials/admin_sidebar.php'; ?>

  <main class="app-main">

    <div class="app-content-header">
      <div class="container-fluid d-flex justify-content-between align-items-center">
        <h3 class="mb-0 fw-semibold"><?= $pageTitle ?></h3>
        <ol class="breadcrumb float-sm-end mb-0">
          <li class="breadcrumb-item"><a href="<?= BACKEND_URL ?>/dashboard.php">Home</a></li>
          <li class="breadcrumb-item active"><?= $pageTitle ?></li>
        </ol>
      </div>
    </div>

    <div class="app-content">
      <div class="container-fluid">

        <div class="card shadow-sm">
          <div class="card-header fw-semibold">รายการสลิปทั้งหมด</div>
          <div class="card-body table-responsive">

            <table class="table table-bordered table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:80px;">ID</th>
                  <th>User</th>
                  <th style="width:140px;">Amount</th>
                  <th style="width:140px;">Status</th>
                  <th style="width:140px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($payments)): ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีสลิป</td></tr>
                <?php else: ?>
                  <?php foreach ($payments as $p): ?>
                    <tr>
                      <td><?= (int)($p['id'] ?? 0) ?></td>
                      <td><?= htmlspecialchars($p['user_name'] ?? $p['display_name'] ?? '-') ?></td>
                      <td><?= number_format((float)($p['amount'] ?? 0), 2) ?></td>
                      <td>
                        <?php
                          $st = $p['status'] ?? 'pending';
                          $badge = match($st){
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'warning'
                          };
                        ?>
                        <span class="badge text-bg-<?= $badge ?> px-3 py-2"><?= htmlspecialchars($st) ?></span>
                      </td>
                      <td>
                        <a class="btn btn-info btn-sm"
                           href="<?= BACKEND_URL ?>/payments/view.php?id=<?= (int)($p['id'] ?? 0) ?>">
                          ดูสลิป
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>

          </div>
        </div>

      </div>
    </div>

  </main>
</div>

<?php include BACKEND_PATH . '/partials/admin_script.php'; ?>
</body>
</html>
