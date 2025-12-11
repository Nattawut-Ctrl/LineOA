<?php
$pendingSlipCount = 0;
$pendingSlips     = [];

if (isset($conn) && $conn instanceof mysqli) {
  require_once SERVICES_PATH . '/slipService.php';

  $pendingSlipCount = getPendingSlipCount($conn);
  // ดึงรายการแจ้งเตือนล่าสุด 10 อัน
  $pendingSlips     = getPendingSlipNotifications($conn, 10);
}
?>

<nav class="app-header navbar navbar-expand bg-dark sticky-top" data-bs-theme="dark">
  <div class="container-fluid">

    <ul class="navbar-nav">
      <li class="nav-item">
        <button class="nav-link btn text-white" data-lte-toggle="sidebar" role="button">
          <i class="bi bi-list"></i>
        </button>
      </li>
      <li class="nav-item d-none d-md-inline-block">
        <a href="<?= BACKEND_URL ?>/dashboard.php" class="nav-link fw-semibold">
          Line-Shop Admin
        </a>
      </li>
    </ul>

    <ul class="navbar-nav ms-auto align-items-center">
      <!-- แจ้งเตือน -->
      <li class="nav-item dropdown position-relative">
        <a class="nav-link position-relative" data-bs-toggle="dropdown" href="#">
          <i class="bi bi-bell fs-5"></i>

          <?php if ($pendingSlipCount > 0): ?>
            <!-- badge วงกลมแดง -->
            <span class="badge rounded-pill text-bg-danger navbar-badge"
              style="font-size: 0.65rem; position:absolute; top:4px; right:0;">
              <?= ($pendingSlipCount > 99) ? '99+' : $pendingSlipCount ?>
            </span>
          <?php endif; ?>
        </a>

        <!-- Dropdown แจ้งเตือนแบบ YouTube -->
        <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg"
             style="width: 360px; max-height: 420px; overflow-y: auto;">

          <!-- header -->
          <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-semibold small">
              การแจ้งเตือน
            </span>
            <?php if ($pendingSlipCount > 0): ?>
              <span class="badge text-bg-danger small">
                <?= $pendingSlipCount ?> รายการค้างตรวจ
              </span>
            <?php endif; ?>
          </div>

          <?php if (!empty($pendingSlips)): ?>

            <?php foreach ($pendingSlips as $slip): ?>
              <?php
                $slipId   = (int)$slip['payment_id'];
                $orderId  = $slip['order_code'] ?? ('ORD-' . str_pad($slip['payment_id'], 5, '0', STR_PAD_LEFT));
                $first = $slip['first_name'] ?? '';
                $last  = $slip['last_name'] ?? '';
                $custName = trim($first . ' ' . $last) ?: 'ลูกค้าไม่ระบุชื่อ';
                $amount   = (float)($slip['amount'] ?? 0);
                $created  = $slip['created_at'] ?? null;

                // แปลงเวลาเป็นฟอร์แมตสั้น ๆ เช่น 08/12 13:45
                $timeText = $created
                  ? (new DateTime($created))->format('d/m H:i')
                  : '';
              ?>

              <a href="<?= BACKEND_URL ?>/payments/view.php?id=<?= $slipId ?>"
                 class="dropdown-item py-2 px-3 small d-flex gap-2 align-items-start">

                <!-- icon กลม ๆ ด้านซ้าย -->
                <div class="flex-shrink-0">
                  <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                       style="width: 32px; height: 32px;">
                    <i class="bi bi-receipt-cutoff"></i>
                  </div>
                </div>

                <!-- รายละเอียด -->
                <div class="flex-grow-1">
                  <div class="fw-semibold text-truncate">
                    สลิปใหม่จาก <?= htmlspecialchars($custName) ?>
                  </div>
                  <div class="text-muted">
                    ออเดอร์: <span class="fw-semibold"><?= htmlspecialchars($orderId) ?></span>
                  </div>
                  <div class="text-danger fw-semibold">
                    ฿<?= number_format($amount, 2) ?>
                  </div>
                  <div class="text-muted small">
                    ส่งเมื่อ <?= htmlspecialchars($timeText) ?>
                  </div>
                </div>
              </a>

              <div class="dropdown-divider my-0"></div>
            <?php endforeach; ?>

            <!-- footer: ไปหน้ารายการทั้งหมด -->
            <div class="px-3 py-2 text-center">
              <a href="<?= BACKEND_URL ?>/payments/list.php" class="small text-decoration-none">
                ดูสลิปทั้งหมด
              </a>
            </div>

          <?php else: ?>
            <div class="px-3 py-3 small text-muted text-center">
              ยังไม่มีการแจ้งเตือนใหม่
            </div>
          <?php endif; ?>

        </div>
      </li>

      <!-- ชื่อผู้ใช้ + Logout -->
      <li class="nav-item me-2 small text-muted">
        <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
      </li>
      <li class="nav-item">
        <a class="btn btn-outline-danger btn-sm" href="<?= BACKEND_URL ?>/Users/ad_logout.php">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </li>
    </ul>

  </div>
</nav>