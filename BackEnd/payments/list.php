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

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini">
  <div class="app-wrapper">

    <?php include BACKEND_PATH . '/partials/admin_navbar.php'; ?>
    <?php include BACKEND_PATH . '/partials/admin_sidebar.php'; ?>

    <main class="app-main">

      <div class="app-content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fw-semibold"><?= $pageTitle ?></h3>
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
                    <!-- ✅ เพิ่มคอลัมน์ใหม่ -->
                    <th style="width:170px;">ชำระเมื่อ</th>
                    <th style="width:140px;">Status</th>
                    <th style="width:140px;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($payments)): ?>
                    <tr>
                      <!-- ✅ เดิม colspan=5 ต้องเปลี่ยนเป็น 6 -->
                      <td colspan="6" class="text-center text-muted py-4">ยังไม่มีสลิป</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                      <tr>
                        <td><?= (int)($p['id'] ?? 0) ?></td>
                        <td>
                          <?php
                          $displayName = $p['user_name'] ?? trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                          if ($displayName === '') {
                            $displayName = $p['display_name'] ?? '-';
                          }
                          echo htmlspecialchars($displayName);
                          ?>
                        </td>
                        <td><?= number_format((float)($p['amount'] ?? 0), 2) ?></td>

                        <!-- ✅ แสดงวันที่/เวลาโอน -->
                        <td>
                          <?php
                          $tDate = $p['transfer_date'] ?? null;
                          $tTime = $p['transfer_time'] ?? null;

                          if (!empty($tDate)) {
                            echo htmlspecialchars(date('d/m/Y', strtotime($tDate)));
                          } else {
                            echo '<span class="text-muted">-</span>';
                          }

                          if (!empty($tTime)) {
                            // ตัดให้เหลือ HH:MM ถ้าใน DB เป็น HH:MM:SS
                            $timeStr = substr($tTime, 0, 5);
                            echo '<br><small class="text-muted">' . htmlspecialchars($timeStr) . ' น.</small>';
                          }
                          ?>
                        </td>

                        <td>
                          <?php
                          $st = $p['status'] ?? 'pending';
                          $badge = match ($st) {
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
    <?php include BACKEND_PATH . '/partials/admin_footer.php'; ?>
  </div>

  <?php include BACKEND_PATH . '/partials/admin_script.php'; ?>
</body>

</html>