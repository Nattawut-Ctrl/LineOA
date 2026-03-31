<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

// Overall Profit Summary
$summary = null;
$res = db_query($conn, "
    SELECT
        COALESCE(SUM(a.qty * a.unit_sell_price), 0) AS total_revenue,
        COALESCE(SUM(a.qty * a.unit_cost_price), 0) AS total_cogs,
        COALESCE(SUM(a.qty * (a.unit_sell_price - a.unit_cost_price)), 0) AS total_gross_profit
    FROM lot_allocations a
");
if ($res && $res->num_rows > 0) {
    $summary = $res->fetch_assoc();
}

$pageTitle  = "สรุปกําไร";
$activeMenu = "profit_summary";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>สรุปกําไร</title>
    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
    <style>
        .card { border-radius: 0.85rem; }
        .num { text-align: right; }
        .kpi { font-size: 1.5rem; font-weight: 600; }
        .muted { color: #6c757d; }
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
                            <h4 class="mb-0">สรุปกําไรทั้งหมด</h4>
                            <div class="text-muted small">ภาพรวมกําไรของร้าน</div>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/profit_by_lot.php">กําไรตามล็อต</a>
                            <a class="btn btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/profit_by_product.php">กําไรตามสินค้า</a>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="muted small">รายได้รวม (Total Revenue)</div>
                                    <div class="kpi num text-success"><?= number_format((float)($summary['total_revenue'] ?? 0), 2) ?> บาท</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="muted small">ต้นทุนขายรวม (Total COGS)</div>
                                    <div class="kpi num text-danger"><?= number_format((float)($summary['total_cogs'] ?? 0), 2) ?> บาท</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="muted small">กําไรขั้นต้นรวม (Total Gross Profit)</div>
                                    <div class="kpi num text-primary"><?= number_format((float)($summary['total_gross_profit'] ?? 0), 2) ?> บาท</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</body>

</html>