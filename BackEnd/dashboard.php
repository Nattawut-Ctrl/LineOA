<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once UTILS_PATH . '/db_with_log.php';

if (!isset($_SESSION['admin_id'])) {
  header('Location: Users/ad_login.php');
  exit;
}

$pageTitle  = "Dashboard | Line-Shop Admin";
$activeMenu = "dashboard";

$conn = connectDBWithLog();

// KPI
$pending   = db_query($conn, "SELECT COUNT(*) c FROM payments WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$approved  = db_query($conn, "SELECT COUNT(*) c FROM payments WHERE status='approved'")->fetch_assoc()['c'] ?? 0;
$rejected  = db_query($conn, "SELECT COUNT(*) c FROM payments WHERE status='rejected'")->fetch_assoc()['c'] ?? 0;
$products  = db_query($conn, "SELECT COUNT(*) c FROM products")->fetch_assoc()['c'] ?? 0;
$users     = db_query($conn, "SELECT COUNT(*) c FROM users")->fetch_assoc()['c'] ?? 0;

// Pending ล่าสุด 5 รายการ
$recentPending = db_query($conn, "
  SELECT p.id, p.amount, p.created_at, u.display_name
  FROM payments p
  JOIN users u ON p.user_id=u.id
  WHERE p.status='pending'
  ORDER BY p.id DESC
  LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <?php include __DIR__ . '/partials/admin_head.php'; ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <?php include __DIR__ . '/partials/admin_navbar.php'; ?>
  <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>

  <div class="content-wrapper">

    <section class="content-header">
      <div class="container-fluid">
        <h1 class="fw-bold mb-0">Dashboard</h1>
        <p class="text-muted">ภาพรวมระบบ Line-Shop</p>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">

        <!-- KPI Cards -->
        <div class="row g-3">

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="small-box text-bg-warning">
              <div class="inner">
                <h3><?= $pending ?></h3>
                <p>สลิปรออนุมัติ</p>
              </div>
              <div class="icon"><i class="bi bi-hourglass-split"></i></div>
              <a href="payments/list.php" class="small-box-footer">
                ไปหน้าตรวจสลิป <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="small-box text-bg-success">
              <div class="inner">
                <h3><?= $approved ?></h3>
                <p>อนุมัติแล้ว</p>
              </div>
              <div class="icon"><i class="bi bi-check2-circle"></i></div>
              <a href="payments/list.php" class="small-box-footer">
                ดูรายการ <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="small-box text-bg-danger">
              <div class="inner">
                <h3><?= $rejected ?></h3>
                <p>ปฏิเสธแล้ว</p>
              </div>
              <div class="icon"><i class="bi bi-x-circle"></i></div>
              <a href="payments/list.php" class="small-box-footer">
                ดูรายการ <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="small-box text-bg-primary">
              <div class="inner">
                <h3><?= $products ?></h3>
                <p>จำนวนสินค้า</p>
              </div>
              <div class="icon"><i class="bi bi-box-seam"></i></div>
              <a href="Stock/addStock.php" class="small-box-footer">
                จัดการสินค้า <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

        </div>

        <!-- Content Row -->
        <div class="row mt-3 g-3">

          <!-- Recent pending slips -->
          <div class="col-12 col-lg-7">
            <div class="card shadow-sm">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">สลิปรออนุมัติล่าสุด</h5>
                <a href="payments/list.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
              </div>

              <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>ผู้ใช้</th>
                      <th>ยอดเงิน</th>
                      <th>เวลา</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if ($recentPending && $recentPending->num_rows): ?>
                    <?php while($r = $recentPending->fetch_assoc()): ?>
                      <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['display_name']) ?></td>
                        <td><?= number_format($r['amount'],2) ?> ฿</td>
                        <td><?= $r['created_at'] ?></td>
                        <td>
                          <a class="btn btn-sm btn-primary"
                             href="payments/view.php?id=<?= $r['id'] ?>">
                            เปิดดู
                          </a>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">ยังไม่มีสลิปรออนุมัติ</td>
                    </tr>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- quick stats / links -->
          <div class="col-12 col-lg-5">
            <div class="card shadow-sm">
              <div class="card-header">
                <h5 class="mb-0">สรุประบบ</h5>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                  <span>ผู้ใช้ทั้งหมด</span>
                  <strong><?= $users ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>สินค้าในระบบ</span>
                  <strong><?= $products ?></strong>
                </div>
                <hr>
                <p class="text-muted small mb-2">
                  อัปเดตล่าสุด: <?= date("d/m/Y H:i") ?>
                </p>

                <div class="d-grid gap-2">
                  <a href="Stock/addStock.php" class="btn btn-outline-dark">
                    <i class="bi bi-box-seam me-1"></i> ไปจัดการสินค้า
                  </a>
                  <a href="payments/list.php" class="btn btn-outline-primary">
                    <i class="bi bi-receipt me-1"></i> ไปตรวจสลิป
                  </a>
                </div>

              </div>
            </div>
          </div>

        </div>

      </div>
    </section>

  </div>

  <?php include __DIR__ . '/partials/admin_footer.php'; ?>
</div>

<?php include __DIR__ . '/partials/admin_script.php'; ?>
</body>
</html>
