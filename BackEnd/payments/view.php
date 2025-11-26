<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/csrf.php';
require_once SERVICES_PATH . '/slipService.php';

if (!isset($_SESSION['admin_id'])) {
  header('Location: ' . BACKEND_URL . '/Users/ad_login.php');
  exit;
}

$csrf_token = csrf_token();

function statusButton($id, $status, $label, $class)
{
  global $csrf_token;
  $token = htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8');
  $id    = (int)$id;
  $status = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
  $label  = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
  $class  = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

  return "
    <form action='update_status.php' method='post' class='d-inline'>
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
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini" side>
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
                  <p><strong>ยอดเงิน:</strong> <?= number_format($payment['amount'], 2) ?> บาท</p>
                  <p><strong>สถานะ:</strong> <?= $payment['status'] ?></p>

                  <img src="/<?= $payment['slip_path'] ?>" class="img-fluid my-3" style="max-width:420px">

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


                  <div class="mt-3">
                    <?= statusButton($id, 'approved', 'อนุมัติ', 'btn-success me-2', $csrf_token) ?>
                    <?= statusButton($id, 'rejected', 'ปฏิเสธ', 'btn-danger me-2', $csrf_token) ?>
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