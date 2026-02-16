<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

// Profit by Lot
$rows = [];
$res = db_query($conn, "
    SELECT
        l.id,
        l.lot_code,
        l.fiscal_year,
        p.name AS product_name,
        v.variant_name,
        COALESCE(SUM(a.qty), 0) AS qty_sold,
        COALESCE(SUM(a.qty * a.unit_sell_price), 0) AS revenue,
        COALESCE(SUM(a.qty * a.unit_cost_price), 0) AS cogs,
        COALESCE(SUM(a.qty * (a.unit_sell_price - a.unit_cost_price)), 0) AS gross_profit
    FROM stock_lots l
    JOIN products p ON p.id = l.product_id
    LEFT JOIN product_variants v ON v.id = l.variant_id
    LEFT JOIN lot_allocations a ON a.lot_id = l.id
    GROUP BY l.id
    ORDER BY gross_profit DESC, l.created_at DESC
");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
}

$pageTitle  = "กําไรตามล็อต";
$activeMenu = "profit_by_lot";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>กําไรตามล็อต</title>
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
                            <h4 class="mb-0">กําไรตามล็อต</h4>
                            <div class="text-muted small">กําไรของแต่ละล็อตสินค้า</div>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/profit_summary.php">สรุปกําไร</a>
                            <a class="btn btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/profit_by_product.php">กําไรตามสินค้า</a>
                            <a class="btn btn-outline-secondary" href="<?= BACKEND_URL ?>/pages/stock/lots.php">ดูล็อตทั้งหมด</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>รหัสล็อต</th>
                                            <th>สินค้า</th>
                                            <th>ตัวเลือก</th>
                                            <th class="num">ขายไปแล้ว (ชิ้น)</th>
                                            <th class="num">รายได้</th>
                                            <th class="num">ต้นทุนขาย</th>
                                            <th class="num">กําไรขั้นต้น</th>
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
                                                <td class="num"><?= number_format((int)$r['qty_sold']) ?></td>
                                                <td class="num"><?= number_format((float)$r['revenue'], 2) ?></td>
                                                <td class="num"><?= number_format((float)$r['cogs'], 2) ?></td>
                                                <td class="num <?= (float)$r['gross_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format((float)$r['gross_profit'], 2) ?>
                                                </td>
                                                <td class="text-end">
                                                    <a class="btn btn-sm btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/lot_report.php?lot_id=<?= (int)$r['id'] ?>">ดูรายงาน</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($rows)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">ยังไม่มีข้อมูลกําไร</td>
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