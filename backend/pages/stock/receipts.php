<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

$rows = [];
$res = db_query(
    $conn,
    "SELECT id, receipt_no, fiscal_year, supplier_name, reference_no, receipt_date, status, created_at
     FROM stock_receipts
     ORDER BY id DESC
     LIMIT 200"
);
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
}

$pageTitle = 'รายการใบรับของ';
$activeMenu = 'receipt';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ใบรับของ</title>
    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini">
    <div class="app-wrapper">
        <?php include BACKEND_PATH . '/partials/admin_navbar.php'; ?>
        <?php include BACKEND_PATH . '/partials/admin_sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content">
                <div class="container-fluid pt-3 pb-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">รายการใบรับของ</h4>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/receipt_create.php">+ สร้างใบรับของ</a>
                            <a class="btn btn-outline-secondary" href="<?= BACKEND_URL ?>/pages/stock/lots.php">ดูล็อตทั้งหมด</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>เลขที่ใบเสร็จ</th>
                                            <th>ปีงบ</th>
                                            <th>วันที่รับของ</th>
                                            <th>ชื่อร้านค้า</th>
                                            <th>เลขอ้างอิง</th>
                                            <th>สถานะ</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($rows)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">ยังไม่มีข้อมูล</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($rows as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r['receipt_no'] ?? '') ?></td>
                                                    <td><?= (int)($r['fiscal_year'] ?? 0) ?></td>
                                                    <td><?= htmlspecialchars($r['receipt_date'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($r['supplier_name'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($r['reference_no'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($r['status'] ?? '-') ?></td>
                                                    <td class="text-end">
                                                        <a class="btn btn-sm btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/lots.php?receipt_id=<?= (int)$r['id'] ?>">ดูล็อต</a>
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
            </div>
        </main>
    </div>
</body>

</html>