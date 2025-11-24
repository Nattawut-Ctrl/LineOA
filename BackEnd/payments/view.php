<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/slipService.php';

$conn = connectDBWithLog();

$id = (int)($_GET['id'] ?? 0);
$payment = getPaymentById($conn, $id);

if (!$payment) die("Payment not found");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจสลิป #<?= $id ?></title>
    <?php include BASE_PATH . '/partials/bootstrap.php'; ?>
</head>

<body>
<div class="container py-4">

    <h3>สลิป #<?= $payment['id'] ?></h3>
    <p><strong>ผู้ใช้:</strong> <?= $payment['display_name'] ?></p>
    <p><strong>ยอดเงิน:</strong> <?= number_format($payment['amount'],2) ?></p>
    <p><strong>Status:</strong> <?= $payment['status'] ?></p>

    <img src="/<?= $payment['slip_path'] ?>" class="img-fluid my-3" style="max-width:400px">

    <div class="mt-3">
        <a href="update_status.php?id=<?= $id ?>&status=approved"
           class="btn btn-success me-2">อนุมัติ</a>

        <a href="update_status.php?id=<?= $id ?>&status=rejected"
           class="btn btn-danger">ปฏิเสธ</a>

        <a href="list.php" class="btn btn-secondary ms-3">กลับ</a>
    </div>

</div>
</body>
</html>
