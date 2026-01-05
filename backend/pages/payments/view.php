<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/csrf.php';
require_once SERVICES_PATH . '/slipService.php';
require_once UTILS_PATH . '/admin_guard.php';
require_admin();

if (!isset($_SESSION['admin_id'])) {
  header('Location: ' . BACKEND_URL . '/pages/users/login.php');
  exit;
}

$csrf_token = csrf_token();

function statusButton($id, $status, $label, $class)
{
  global $csrf_token;
  $token  = htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8');
  $id     = (int)$id;
  $status = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
  $label  = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
  $class  = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

  $action = rtrim(BACKEND_URL, '/') . '/actions/payments/update_status.php';
  $action = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');

  return "
    <form action='{$action}' method='post'>
        <input type='hidden' name='csrf_token' value='{$token}'>
        <input type='hidden' name='id' value='{$id}'>
        <input type='hidden' name='status' value='{$status}'>
        <button type='submit' class='btn {$class}'>{$label}</button>
    </form>
  ";
}

$pageTitle  = "ดูสลิป";
$activeMenu = "slip";

$conn = connectDBWithLog();
$id = (int)($_GET['id'] ?? 0);
$payment = getPaymentById($conn, $id);
if (!$payment) die("Payment not found");

// ---- เตรียม format วันที่/เวลาให้ดูง่าย ----
$transferDateRaw = $payment['transfer_date'] ?? null;
$transferTimeRaw = $payment['transfer_time'] ?? null;
$createdAtRaw    = $payment['created_at']    ?? null;

$transferDate = $transferDateRaw ? date('d/m/Y', strtotime($transferDateRaw)) : '-';
$transferTime = $transferTimeRaw ? substr($transferTimeRaw, 0, 5) . ' น.' : '-';
$createdAt    = $createdAtRaw    ? date('d/m/Y H:i', strtotime($createdAtRaw)) . ' น.' : '-';

$modeLabel = ($payment['mode'] ?? 'single') === 'cart' ? 'ทั้งตะกร้า' : 'ซื้อเดี่ยว';
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
      <div class="app-content">
        <div class="container-fluid">

          <section class="content pt-3">
            <div class="container-fluid">

              <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">สลิป #<?= (int)$payment['id'] ?></h5>
                  <span class="text-muted small">
                    สร้างเมื่อ: <?= htmlspecialchars($createdAt) ?>
                  </span>
                </div>

                <div class="card-body">

                  <!-- ==== แถวหลัก: ซ้าย = ข้อมูล, ขวา = รูปสลิป ==== -->
                  <div class="row">
                    <!-- ซ้าย: ข้อมูลรายละเอียดการชำระ -->
                    <div class="col-lg-7 mb-3">
                      <div class="mb-3">
                        <h5 class="fw-semibold mb-3">ข้อมูลการชำระ</h5>
                        <div class="table-responsive">
                          <table class="table table-sm table-borderless mb-0">
                            <tr>
                              <th style="width:150px;">ผู้ใช้</th>
                              <td><?= htmlspecialchars(trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name']) ?? '')) ?: '-' ?></td>
                            </tr>
                            <tr>
                              <th>ยอดเงิน</th>
                              <td><span class="fw-semibold text-danger">
                                  <?= number_format((float)$payment['amount'], 2) ?> บาท
                                </span></td>
                            </tr>
                            <tr>
                              <th>โหมดการชำระ</th>
                              <td><?= htmlspecialchars($modeLabel) ?></td>
                            </tr>
                            <tr>
                              <th>วันที่โอน</th>
                              <td><?= htmlspecialchars($transferDate) ?></td>
                            </tr>
                            <tr>
                              <th>เวลาโอน</th>
                              <td><?= htmlspecialchars($transferTime) ?></td>
                            </tr>
                            <tr>
                              <th>สถานะ</th>
                              <td>
                                <?php
                                $st = $payment['status'] ?? 'pending';
                                $badge = match ($st) {
                                  'approved' => 'success',
                                  'rejected' => 'danger',
                                  default => 'warning'
                                };
                                ?>
                                <span class="badge text-bg-<?= $badge ?> px-3 py-2">
                                  <?= htmlspecialchars($st) ?>
                                </span>
                              </td>
                            </tr>
                          </table>
                        </div>
                      </div>

                      <?php
                      // แสดงรายละเอียดรายการสินค้า (ถ้ามี)
                      $items = [];
                      if (!empty($payment['items_json'])) {
                        $decoded = json_decode($payment['items_json'], true);
                        if (is_array($decoded)) {
                          $items = $decoded;
                        }
                      }

                      if (!empty($items)): ?>
                        <h5 class="mt-4 mb-3">รายการสินค้า</h5>
                        <div class="table-responsive">
                          <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                              <tr>
                                <th>สินค้า</th>
                                <th style="width:140px;">ตัวเลือก</th>
                                <th style="width:80px;" class="text-center">จำนวน</th>
                                <th style="width:140px;" class="text-end">ราคา/ชิ้น</th>
                                <th style="width:140px;" class="text-end">รวม</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($items as $it):
                                $name   = $it['name'] ?? '-';
                                $vname  = $it['variant_name'] ?? '';
                                $qty    = (int)($it['quantity'] ?? 1);
                                if ($qty < 1) $qty = 1;
                                $price  = (float)($it['price'] ?? 0);
                                $total  = $qty * $price;
                              ?>
                                <tr>
                                  <td><?= htmlspecialchars($name) ?></td>
                                  <td><?= htmlspecialchars($vname) ?></td>
                                  <td class="text-center"><?= $qty ?></td>
                                  <td class="text-end"><?= number_format($price, 2) ?></td>
                                  <td class="text-end"><?= number_format($total, 2) ?></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- ขวา: รูปสลิป (ใหญ่หน่อยบนจอใหญ่) -->
                    <div class="col-lg-5 mb-3">
                      <h5 class="fw-semibold mb-3">ภาพสลิป</h5>
                      <div class="border rounded p-2 bg-light text-center">
                        <img src="/<?= htmlspecialchars($payment['slip_path']) ?>"
                          alt="สลิปการโอน #<?= (int)$payment['id'] ?>"
                          class="img-fluid"
                          style="max-height: 520px; object-fit: contain;">
                      </div>
                    </div>
                  </div>

                  <!-- ปุ่มจัดการ -->
                  <div class="mt-4 d-flex align-items-center">
                    <?= statusButton($id, 'approved', 'อนุมัติ', 'btn-success me-2') ?>
                    <?= statusButton($id, 'rejected', 'ปฏิเสธ', 'btn-danger me-2') ?>
                    <a href="list.php" class="btn btn-secondary">กลับ</a>
                  </div>

                </div>
              </div>

            </div>
          </section>

        </div>
      </div>
    </main>

    <!-- <?php include BACKEND_PATH . '/partials/admin_footer.php'; ?> -->
  </div>
  <?php include BACKEND_PATH . '/partials/admin_script.php'; ?>
</body>

</html>