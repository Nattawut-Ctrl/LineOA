<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/slipService.php';

$conn = connectDBWithLog();
$payments = getAllPayments($conn);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจสลิปทั้งหมด</title>
    <?php include BASE_PATH . '/partials/bootstrap.php'; ?>
</head>

<body>
<div class="container py-4">

    <h3 class="mb-4">รายการสลิปทั้งหมด</h3>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>Status</th>
                <th>ดูรายละเอียด</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= $p['display_name'] ?></td>
                <td><?= number_format($p['amount'], 2) ?></td>
                <td><?= $p['status'] ?></td>
                <td>
                    <a href="view.php?id=<?= $p['id'] ?>" class="btn btn-info btn-sm">เปิด</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>

    </table>

</div>
</body>
</html>
