<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

// Profit by Product
$rows = [];
$res = db_query($conn, "
    SELECT
        p.id,
        p.name AS product_name,
        COALESCE(SUM(a.qty), 0) AS qty_sold,
        COALESCE(SUM(a.qty * a.unit_sell_price), 0) AS revenue,
        COALESCE(SUM(a.qty * a.unit_cost_price), 0) AS cogs,
        COALESCE(SUM(a.qty * (a.unit_sell_price - a.unit_cost_price)), 0) AS gross_profit
    FROM products p
    LEFT JOIN stock_lots l ON l.product_id = p.id
    LEFT JOIN lot_allocations a ON a.lot_id = l.id
    GROUP BY p.id
    ORDER BY gross_profit DESC, p.name ASC
");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
}

$pageTitle  = "กําไรตามสินค้า";
$activeMenu = "profit_by_product";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>กําไรตามสินค้า</title>
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
                            <h4 class="mb-0">กําไรตามสินค้า</h4>
                            <div class="text-muted small">กําไรของแต่ละสินค้า</div>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/profit_summary.php">สรุปกําไร</a>
                            <a class="btn btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/profit_by_lot.php">กําไรตามล็อต</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>สินค้า</th>
                                            <th class="num">ขายไปแล้ว (ชิ้น)</th>
                                            <th class="num">รายได้</th>
                                            <th class="num">ต้นทุนขาย</th>
                                            <th class="num">กําไรขั้นต้น</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $r): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($r['product_name']) ?></div>
                                                </td>
                                                <td class="num"><?= number_format((int)$r['qty_sold']) ?></td>
                                                <td class="num"><?= number_format((float)$r['revenue'], 2) ?></td>
                                                <td class="num"><?= number_format((float)$r['cogs'], 2) ?></td>
                                                <td class="num <?= (float)$r['gross_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format((float)$r['gross_profit'], 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($rows)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลกําไร</td>
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