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

function formatThaiDateFull($date)
{
  if (!$date) return '-';

  $months = [
    1 => ['full' => 'มกราคม'], // 'short' => 'ม.ค.'
    2 => ['full' => 'กุมภาพันธ์'], // 'short' => 'ก.พ.'
    3 => ['full' => 'มีนาคม'], // 'short' => 'มี.ค.'
    4 => ['full' => 'เมษายน'], // 'short' => 'เม.ย.'
    5 => ['full' => 'พฤษภาคม'], // 'short' => 'พ.ค.'
    6 => ['full' => 'มิถุนายน'], // 'short' => 'มิ.ย.'
    7 => ['full' => 'กรกฎาคม'], // ...
    8 => ['full' => 'สิงหาคม'],
    9 => ['full' => 'กันยายน'],
    10 => ['full' => 'ตุลาคม'],
    11 => ['full' => 'พฤศจิกายน'],
    12 => ['full' => 'ธันวาคม'], // 'short' => 'ธ.ค.'
  ];

  $ts = strtotime($date);
  $day   = (int)date('j', $ts);          // 2
  $month = (int)date('n', $ts);          // 1
  $year  = (int)date('Y', $ts) + 543;    // พ.ศ.

  // return "{$day} {$months[$month]['full']} ({$months[$month]['short']}) {$year}";
  return "{$day} {$months[$month]['full']} {$year}";
}

$transferDate = $transferDateRaw ? formatThaiDateFull($transferDateRaw) : '-';
$transferTime = $transferTimeRaw ? substr($transferTimeRaw, 0, 5) . ' น.' : '-';
$createdAt    = $createdAtRaw    ? date('d/m/Y H:i', strtotime($createdAtRaw)) . ' น.' : '-';

// $modeLabel = ($payment['mode'] ?? 'single') === 'cart' ? 'ทั้งตะกร้า' : 'ซื้อเดี่ยว';
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

              <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient text-dark d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                  <h4 class="mb-0 text-black fw-bold">💳 สลิป #<?= (int)$payment['id'] ?></h4>
                  <span class="text-white small opacity-75">
                    สร้างเมื่อ: <?= htmlspecialchars($createdAt) ?>
                  </span>
                </div>

                <div class="card-body">

                  <!-- ==== แถวหลัก: ซ้าย = ข้อมูล, ขวา = รูปสลิป ==== -->
                  <div class="row">
                    <!-- ซ้าย: ข้อมูลรายละเอียดการชำระ -->
                    <div class="col-lg-7 mb-3">
                      <!-- ข้อมูลการชำระ -->
                      <div class="mb-4">
                        <h5 class="fw-bold mb-3 pb-2 border-bottom border-primary">📋 ข้อมูลการชำระ</h5>
                        <div class="table-responsive">
                          <?php
                          $amountToShow = isset($payment['grand_total'])
                            ? (float)$payment['grand_total']
                            : (float)($payment['amount'] ?? 0);
                          ?>
                          <table class="table table-sm table-borderless mb-0">
                            <tr>
                              <th style="width:150px;">ผู้ใช้</th>
                              <td><?php
                                  $fullName = trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''));
                                  echo htmlspecialchars($fullName ?: '-');
                                  ?>
                              </td>
                            </tr>
                            <tr>
                              <th>ยอดเงินรวมค่าส่ง</th>
                              <td><span class="fw-semibold text-danger">
                                  <?= number_format($amountToShow, 2) ?> บาท
                                </span></td>
                            </tr>

                            <!-- <tr>
                              <th>โหมดการชำระ</th>
                              <td><?= htmlspecialchars($modeLabel) ?></td>
                            </tr> -->
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

                      <!-- ข้อมูลที่อยู่จัดส่ง -->
                      <?php
                      $addressData = null;
                      if (!empty($payment['address_json'])) {
                        $addressData = json_decode($payment['address_json'], true);
                      }
                      ?>
                      <?php if (!empty($addressData)): ?>
                        <div class="card bg-light border-0 mb-4 shadow-sm">
                          <div class="card-body">
                            <h5 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-geo-alt-fill text-danger me-2"></i>ที่อยู่จัดส่ง</h5>
                            <div class="border-start border-danger border-3 ps-3">
                              <div class="fw-semibold mb-2">
                                <?= htmlspecialchars($addressData['full_name'] ?? '-') ?>
                                <span class="text-muted fw-normal ms-2 small">
                                  <?php
                                  $phone = $addressData['phone'] ?? '';
                                  $digits = preg_replace('/\D/', '', $phone);
                                  if (preg_match('/^(\d{3})(\d{3})(\d{4})$/', $digits, $m)) {
                                    echo htmlspecialchars("{$m[1]}-{$m[2]}-{$m[3]}");
                                  } else {
                                    echo htmlspecialchars($phone);
                                  }
                                  ?>
                                </span>
                              </div>
                              <div class="text-muted small lh-lg">
                                เลขที่ <?= htmlspecialchars($addressData['address_line'] ?? '-') ?>,
                                ตำบล<?= htmlspecialchars($addressData['subdistrict'] ?? '-') ?>,
                                อำเภอ<?= htmlspecialchars($addressData['district'] ?? '-') ?>,
                                จังหวัด<?= htmlspecialchars($addressData['province'] ?? '-') ?>
                                <?= htmlspecialchars($addressData['postal_code'] ?? '-') ?>
                              </div>
                              <?php if (!empty($addressData['label'])): ?>
                                <div class="mt-2">
                                  <span class="badge text-bg-secondary"><?= htmlspecialchars($addressData['label']) ?></span>
                                </div>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      <?php endif; ?>

                      <?php if (($payment['status'] ?? 'pending') === 'approved'): ?>
                        <div class="mt-4 p-3 bg-light rounded-3 border border-info border-opacity-25">
                          <h5 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-box2-heart text-info me-2"></i>เลขติดตามพัสดุ</h5>
                          <form id="trackingForm" method="post" action="<?= rtrim(BACKEND_URL, '/') ?>/api/payments/update_tracking.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <select name="carrier" required>
                              <option value="">-- เลือกขนส่ง --</option>
                              <option value="thpost">ไปรษณีย์ไทย</option>
                              <option value="flash">Flash Express</option>
                              <option value="kerry">Kerry Express</option>
                              <option value="jnt">J&T Express</option>
                            </select>
                            <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
                            <div class="input-group">
                              <input type="text" id="trackingInput" class="form-control" name="tracking_no" value="<?= htmlspecialchars($payment['tracking_no'] ?? '') ?>" placeholder="เช่น 1234567890">
                              <button type="submit" class="btn btn-info text-white"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                            </div>
                          </form>
                        </div>
                      <?php endif; ?>

                      <!-- รายการสินค้า -->
                      <?php
                      // แสดงรายละเอียดรายการสินค้า (ถ้ามี)
                      $items = [];
                      if (!empty($payment['items_json'])) {
                        $decoded = json_decode($payment['items_json'], true);
                        if (is_array($decoded)) {
                          $items = $decoded;
                        }
                      }
                      $totalItems  = 0;
                      // fallback เฉพาะกรณี DB ยังไม่มี subtotal/grand_total
                      $subTotal    = isset($payment['subtotal']) ? (float)$payment['subtotal'] : 0.0;
                      $shippingFee = isset($payment['shipping_fee']) ? (float)$payment['shipping_fee'] : 0.0;
                      $grandTotal  = isset($payment['grand_total']) ? (float)$payment['grand_total'] : ($subTotal + $shippingFee);

                      if (!empty($items)): ?>
                        <h5 class="mt-4 mb-3 fw-bold pb-2 border-bottom"><i class="bi bi-box-seam me-2 text-success"></i>รายการสินค้า</h5>
                        <div class="table-responsive">
                          <table class="table table-bordered text-center align-middle small">
                            <tr>
                              <th>สินค้า</th>
                              <th>จำนวน</th>
                              <th>ราคาต่อชิ้น</th>
                              <th>รวมย่อย</th>
                            </tr>

                            <?php foreach ($items as $it):
                              $name   = $it['name'] ?? $it['product_name'] ?? '-';
                              $qty    = (int)($it['quantity'] ?? 1);
                              if ($qty < 1) $qty = 1;

                              $unitPrice = (float)($it['unit_price'] ?? $it['price'] ?? 0);
                              $lineTotal = $qty * $unitPrice;

                              $totalItems += $qty;
                            ?>
                              <tr>
                                <td><?= htmlspecialchars($name) ?></td>
                                <td><?= $qty ?></td>
                                <td><?= number_format($unitPrice, 2) ?></td>
                                <td><?= number_format($lineTotal, 2) ?></td>
                              </tr>
                            <?php endforeach; ?>

                            <tr class="table-warning">
                              <th colspan="3">ราคาสินค้า</th>
                              <th><strong><?= number_format($subTotal, 2) ?> บาท</strong></th>
                            </tr>
                            <tr>
                              <th colspan="3">
                                <i class="bi bi-truck text-info me-2"></i>ค่าส่ง
                                <small class="text-muted">(<span id="totalItemsDisplay"><?= (int)$totalItems ?></span> ชิ้น)</small>
                              </th>
                              <th><strong id="shippingDisplay"><?= number_format($shippingFee, 2) ?> บาท</strong></th>
                            </tr>
                            <tr class="table-success">
                              <th colspan="3">รวมทั้งหมด</th>
                              <th><strong id="grandTotalDisplay"><?= number_format($grandTotal, 2) ?> บาท</strong></th>
                            </tr>
                          </table>
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- ขวา: รูปสลิป (ใหญ่หน่อยบนจอใหญ่) -->
                    <div class="col-lg-5 mb-3">
                      <h5 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-receipt me-2 text-warning"></i>ภาพสลิปโอนเงิน</h5>
                      <div class="border-2 border-warning rounded-3 p-4 bg-white text-center shadow-sm" style="min-height: 450px; display: flex; align-items: center; justify-content: center;">
                        <img src="/<?= htmlspecialchars($payment['slip_path']) ?>"
                          alt="สลิปการโอน #<?= (int)$payment['id'] ?>"
                          class="img-fluid rounded-2"
                          style="max-height: 500px; object-fit: contain; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">

                      </div>
                    </div>
                  </div>

                  <!-- ปุ่มจัดการ -->
                  <div class="mt-5 pt-3 border-top d-flex gap-2 align-items-center flex-wrap">
                    <?php
                    // ✅ แสดงปุ่ม approve/reject เฉพาะเมื่อ:
                    // 1. Status = 'pending'
                    // 2. มี slip_path (ผู้ซื้อได้อัปโหลด slip แล้ว)
                    if (($payment['status'] ?? 'pending') === 'pending' && !empty($payment['slip_path'])): ?>
                      <div class="d-flex gap-2">
                        <?= statusButton($id, 'approved', '✅ อนุมัติ', 'btn-success btn-lg') ?>
                        <?= statusButton($id, 'rejected', '❌ ปฏิเสธ', 'btn-danger btn-lg') ?>
                      </div>
                    <?php else: ?>
                      <div class="alert alert-info mb-0 flex-grow-1">
                        <?php if (($payment['status'] ?? 'pending') !== 'pending'): ?>
                          <strong>ℹ️ รายการนี้มีสถานะเป็น:</strong>
                          <?php
                          $statusText = match ($payment['status']) {
                            'approved' => '✅ อนุมัติแล้ว',
                            'rejected' => '❌ ปฏิเสธแล้ว',
                            'expired' => '⏱️ หมดเวลา',
                            default => 'ไม่ทราบ'
                          };
                          echo htmlspecialchars($statusText);
                          ?>
                        <?php else: ?>
                          <strong>⏳ รอผู้ซื้ออัปโหลดสลิป...</strong>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                    <a href="list.php" class="btn btn-outline-secondary btn-lg ms-auto"><i class="bi bi-arrow-left me-1"></i>กลับ</a>
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