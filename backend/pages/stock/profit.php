<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

// ===== PROFIT SUMMARY =====
$profitSummary = null;
$resSum = db_query($conn, "
    SELECT
        COALESCE(SUM(a.qty),0) AS total_qty_sold,
        COALESCE(SUM(a.qty * a.unit_sell_price),0) AS total_revenue,
        COALESCE(SUM(a.qty * a.unit_cost_price),0) AS total_cogs,
        COALESCE(SUM(a.qty * (a.unit_sell_price - a.unit_cost_price)),0) AS total_gross_profit,
        COALESCE(COUNT(DISTINCT a.lot_id),0) AS total_lots,
        COALESCE(COUNT(DISTINCT p.id),0) AS total_products
    FROM lot_allocations a
    LEFT JOIN stock_lots l ON l.id = a.lot_id
    LEFT JOIN products p ON p.id = l.product_id
");
if ($resSum && $resSum->num_rows > 0) {
    $profitSummary = $resSum->fetch_assoc();
}

// ===== PROFIT BY PRODUCT =====
$profitByProduct = [];
$resProd = db_query($conn, "
    SELECT
        p.id,
        p.name AS product_name,
        COALESCE(SUM(a.qty),0) AS qty_sold,
        COALESCE(SUM(a.qty * a.unit_sell_price),0) AS revenue,
        COALESCE(SUM(a.qty * a.unit_cost_price),0) AS cogs,
        COALESCE(SUM(a.qty * (a.unit_sell_price - a.unit_cost_price)),0) AS gross_profit,
        COALESCE(COUNT(DISTINCT a.lot_id),0) AS lot_count
    FROM lot_allocations a
    JOIN stock_lots l ON l.id = a.lot_id
    JOIN products p ON p.id = l.product_id
    GROUP BY p.id, p.name
    ORDER BY gross_profit DESC
");
if ($resProd) {
    while ($r = $resProd->fetch_assoc()) {
        $profitByProduct[] = $r;
    }
}

// ===== PROFIT BY LOT =====
$profitByLot = [];
$resLot = db_query($conn, "
    SELECT
        l.id,
        l.lot_code,
        l.fiscal_year,
        p.name AS product_name,
        v.variant_name,
        l.cost_price,
        l.qty_received,
        l.qty_available,
        COALESCE(SUM(a.qty),0) AS qty_sold,
        COALESCE(SUM(a.qty * a.unit_sell_price),0) AS revenue,
        COALESCE(SUM(a.qty * a.unit_cost_price),0) AS cogs,
        COALESCE(SUM(a.qty * (a.unit_sell_price - a.unit_cost_price)),0) AS gross_profit
    FROM stock_lots l
    JOIN products p ON p.id = l.product_id
    LEFT JOIN product_variants v ON v.id = l.variant_id
    LEFT JOIN lot_allocations a ON a.lot_id = l.id
    GROUP BY l.id
    ORDER BY gross_profit DESC
");
if ($resLot) {
    while ($r = $resLot->fetch_assoc()) {
        $profitByLot[] = $r;
    }
}

$pageTitle  = "รายงานกำไร";
$activeMenu = "profit";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายงานกำไร | Line Shop</title>
    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
    <style>
        .card { border-radius: .85rem; }
        .num { text-align: right; }
        .kpi { font-size: 1.05rem; font-weight: 600; }
        .muted { color: #6c757d; }
        .profit-positive { color: #198754; font-weight: 600; }
        .profit-negative { color: #dc3545; font-weight: 600; }
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
                            <h4 class="mb-0">รายงานกำไร</h4>
                            <div class="text-muted small">ดูสรุปกำไร, กำไรตามสินค้า และกำไรตามล็อต</div>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-secondary" href="<?= BACKEND_URL ?>/pages/stock/lots.php">ดูล็อตทั้งหมด</a>
                        </div>
                    </div>

                    <!-- ===== PROFIT SUMMARY ===== -->
                    <h5 class="mb-3 mt-4">สรุปกำไร (Profit Summary)</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">จำนวนสินค้าขายไป (ชิ้น)</div>
                                    <div class="kpi num"><?= number_format((int)($profitSummary['total_qty_sold'] ?? 0)) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">รายได้รวม (Revenue)</div>
                                    <div class="kpi num"><?= number_format((float)($profitSummary['total_revenue'] ?? 0), 2) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">ต้นทุนขายรวม (COGS)</div>
                                    <div class="kpi num"><?= number_format((float)($profitSummary['total_cogs'] ?? 0), 2) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">กำไรขั้นต้นรวม (Gross Profit)</div>
                                    <div class="kpi num <?= ((float)($profitSummary['total_gross_profit'] ?? 0) >= 0) ? 'profit-positive' : 'profit-negative' ?>">
                                        <?= number_format((float)($profitSummary['total_gross_profit'] ?? 0), 2) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">จำนวนล็อตที่มีการขาย</div>
                                    <div class="kpi num"><?= number_format((int)($profitSummary['total_lots'] ?? 0)) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">จำนวนสินค้าที่มีการขาย</div>
                                    <div class="kpi num"><?= number_format((int)($profitSummary['total_products'] ?? 0)) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== PROFIT BY PRODUCT ===== -->
                    <h5 class="mb-3 mt-4">กำไรแยกตามสินค้า (Profit by Product)</h5>
                    <div class="card mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>สินค้า</th>
                                            <th class="num">จำนวนขาย (ชิ้น)</th>
                                            <th class="num">รายได้</th>
                                            <th class="num">ต้นทุน</th>
                                            <th class="num">กำไร</th>
                                            <th class="num">อัตราส่วน</th>
                                            <th class="num">ล็อต</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($profitByProduct)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">ยังไม่มีการขาย</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($profitByProduct as $row): ?>
                                                <?php 
                                                    $profit = (float)$row['gross_profit'];
                                                    $revenue = (float)$row['revenue'];
                                                    $ratio = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                                                    <td class="num"><?= number_format((int)$row['qty_sold']) ?></td>
                                                    <td class="num"><?= number_format((float)$row['revenue'], 2) ?></td>
                                                    <td class="num"><?= number_format((float)$row['cogs'], 2) ?></td>
                                                    <td class="num <?= ($profit >= 0) ? 'profit-positive' : 'profit-negative' ?>">
                                                        <?= number_format($profit, 2) ?>
                                                    </td>
                                                    <td class="num <?= ($ratio >= 0) ? 'profit-positive' : 'profit-negative' ?>">
                                                        <?= $ratio ?>%
                                                    </td>
                                                    <td class="num"><?= number_format((int)$row['lot_count']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ===== PROFIT BY LOT ===== -->
                    <h5 class="mb-3 mt-4">กำไรแยกตามล็อต (Profit by Lot)</h5>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Lot Code</th>
                                            <th>สินค้า</th>
                                            <th class="num">ต้นทุน/ชิ้น</th>
                                            <th class="num">รับเข้า</th>
                                            <th class="num">ขายไป</th>
                                            <th class="num">คงเหลือ</th>
                                            <th class="num">รายได้</th>
                                            <th class="num">ต้นทุน</th>
                                            <th class="num">กำไร</th>
                                            <th class="num">อัตราส่วน</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($profitByLot)): ?>
                                            <tr>
                                                <td colspan="11" class="text-center text-muted py-4">ยังไม่มีล็อต</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($profitByLot as $row): ?>
                                                <?php 
                                                    $profit = (float)$row['gross_profit'];
                                                    $revenue = (float)$row['revenue'];
                                                    $ratio = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold"><?= htmlspecialchars($row['lot_code']) ?></div>
                                                        <div class="text-muted small">FY <?= (int)$row['fiscal_year'] ?></div>
                                                    </td>
                                                    <td>
                                                        <div><?= htmlspecialchars($row['product_name']) ?></div>
                                                        <?php if (!empty($row['variant_name'])): ?>
                                                            <div class="text-muted small"><?= htmlspecialchars($row['variant_name']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="num"><?= number_format((float)$row['cost_price'], 2) ?></td>
                                                    <td class="num"><?= number_format((int)$row['qty_received']) ?></td>
                                                    <td class="num"><?= number_format((int)$row['qty_sold']) ?></td>
                                                    <td class="num"><?= number_format((int)$row['qty_available']) ?></td>
                                                    <td class="num"><?= number_format((float)$row['revenue'], 2) ?></td>
                                                    <td class="num"><?= number_format((float)$row['cogs'], 2) ?></td>
                                                    <td class="num <?= ($profit >= 0) ? 'profit-positive' : 'profit-negative' ?>">
                                                        <?= number_format($profit, 2) ?>
                                                    </td>
                                                    <td class="num <?= ($ratio >= 0) ? 'profit-positive' : 'profit-negative' ?>">
                                                        <?= $ratio ?>%
                                                    </td>
                                                    <td class="text-end">
                                                        <a class="btn btn-sm btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/lot_report.php?lot_id=<?= (int)$row['id'] ?>">ดูรายละเอียด</a>
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
