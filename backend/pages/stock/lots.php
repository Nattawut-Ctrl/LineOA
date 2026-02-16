<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

$receiptId = isset($_GET['receipt_id']) ? (int)$_GET['receipt_id'] : 0;

$where = "";
$params = [];
$types = "";
if ($receiptId > 0) {
    $where = "WHERE l.receipt_id = ?";
    $params[] = $receiptId;
    $types .= "i";
}

$sql = "
    SELECT
        l.id,
        l.lot_code,
        l.fiscal_year,
        l.qty_received,
        l.qty_available,
        l.cost_price,
        l.created_at,
        p.name AS product_name,
        v.variant_name AS variant_name,
        l.receipt_id
    FROM stock_lots l
    JOIN products p ON p.id = l.product_id
    LEFT JOIN product_variants v ON v.id = l.variant_id
    $where
    ORDER BY l.created_at DESC, l.id DESC
    LIMIT 500
";

$rows = [];
$res = !empty($params) ? db_query($conn, $sql, $params, $types) : db_query($conn, $sql);
if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
}

$pageTitle  = "ล็อตสินค้า";
$activeMenu = "lots";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ล็อตสินค้า</title>
    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
    <style>
        .card { border-radius: 0.85rem; }
        .num { text-align: right; }
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini">
    <div class="app-wrapper">
        <?php include BACKEND_PATH . '/partials/admin_navbar.php'; ?>
        <?php include BACKEND_PATH . '/partials/admin_sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content">
                <div class="container-fluid pt-3 pb-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="mb-0">ล็อตสินค้า</h4>
                            <div class="text-muted small">ดูล็อตทั้งหมด</div>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-secondary" href="<?= BACKEND_URL ?>/pages/stock/receipts.php">ดูใบรับของ</a>
                            <a class="btn btn-primary" href="<?= BACKEND_URL ?>/pages/stock/receipt_create.php">+ สร้างใบรับของ</a>
                        </div>
                    </div>

                    <?php if ($receiptId > 0): ?>
                        <div class="alert alert-info">กำลังแสดงล็อตจากใบรับของ ID: <?= (int)$receiptId ?></div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>รหัสล็อต</th>
                                            <th>สินค้า</th>
                                            <th>ตัวเลือก</th>
                                            <th class="num">รับเข้า</th>
                                            <th class="num">คงเหลือ</th>
                                            <th class="num">ต้นทุน/ชิ้น</th>
                                            <th>วันที่</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $r): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($r['lot_code']) ?></div>
                                                    <div class="text-muted small">FY <?= (int)$r['fiscal_year'] ?></div>
                                                </td>
                                                <td><?= htmlspecialchars($r['product_name']) ?></td>
                                                <td><?= htmlspecialchars($r['variant_name'] ?? '-') ?></td>
                                                <td class="num"><?= (int)$r['qty_received'] ?></td>
                                                <td class="num"><?= (int)$r['qty_available'] ?></td>
                                                <td class="num"><?= number_format((float)$r['cost_price'], 2) ?></td>
                                                <td><?= htmlspecialchars($r['created_at']) ?></td>
                                                <td class="text-end">
                                                    <a class="btn btn-sm btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/lot_report.php?lot_id=<?= (int)$r['id'] ?>">ดูรายงาน</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($rows)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">ยังไม่มีล็อต</td>
                                            </tr>
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
