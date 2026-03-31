<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

$lotId = isset($_GET['lot_id']) ? (int)$_GET['lot_id'] : 0;
if ($lotId <= 0) {
    header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/stock/lots.php?error=invalid_lot');
    exit;
}

// Summary
$summary = null;
$resSum = db_query($conn, "
    SELECT
        l.id,
        l.lot_code,
        l.fiscal_year,
        l.cost_price,
        l.qty_received,
        l.qty_available,
        p.name AS product_name,
        v.variant_name,
        COALESCE(SUM(a.qty),0) AS qty_sold,
        COALESCE(SUM(a.qty * a.unit_sell_price),0) AS revenue,
        COALESCE(SUM(a.qty * a.unit_cost_price),0) AS cogs,
        COALESCE(SUM(a.qty * (a.unit_sell_price - a.unit_cost_price)),0) AS gross_profit
    FROM stock_lots l
    JOIN products p ON p.id = l.product_id
    LEFT JOIN product_variants v ON v.id = l.variant_id
    LEFT JOIN lot_allocations a ON a.lot_id = l.id
    WHERE l.id = ?
    GROUP BY l.id
", [$lotId], "i");
if ($resSum && $resSum->num_rows > 0) {
    $summary = $resSum->fetch_assoc();
}
if (!$summary) {
    header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/stock/lots.php?error=lot_not_found');
    exit;
}

// Ledger
$ledger = [];
$resLed = db_query($conn, "
    SELECT
        a.created_at,
        a.qty,
        a.unit_sell_price,
        a.unit_cost_price,
        (a.qty * a.unit_sell_price) AS line_revenue,
        (a.qty * a.unit_cost_price) AS line_cogs,
        (a.qty * (a.unit_sell_price - a.unit_cost_price)) AS line_profit,
        pi.payment_id,
        p.amount,
        p.status,
        p.created_at AS payment_created_at,
        u.display_name,
        CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS user_name
    FROM lot_allocations a
    JOIN payment_items pi ON pi.id = a.payment_item_id
    JOIN payments p ON p.id = pi.payment_id
    LEFT JOIN users u ON u.id = p.user_id
    WHERE a.lot_id = ?
    ORDER BY a.created_at DESC, a.id DESC
", [$lotId], "i");
if ($resLed) {
    while ($r = $resLed->fetch_assoc()) {
        $ledger[] = $r;
    }
}

$pageTitle  = "รายงานล็อต";
$activeMenu = "stock";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายงานล็อต | Line Shop</title>
    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
    <style>
        .card { border-radius: .85rem; }
        .num { text-align: right; }
        .kpi { font-size: 1.05rem; font-weight: 600; }
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
                            <h4 class="mb-0">รายงานล็อต: <?= htmlspecialchars($summary['lot_code']) ?></h4>
                            <div class="text-muted small">
                                <?= htmlspecialchars($summary['product_name']) ?>
                                <?php if (!empty($summary['variant_name'])): ?> · <?= htmlspecialchars($summary['variant_name']) ?><?php endif; ?>
                                · ปีงบ <?= htmlspecialchars($summary['fiscal_year']) ?>
                            </div>
                        </div>
                        <div>
                            <a class="btn btn-outline-secondary" href="<?= BACKEND_URL ?>/pages/stock/lots.php">กลับไปล็อตทั้งหมด</a>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">รับเข้า (ชิ้น)</div>
                                    <div class="kpi num"><?= number_format((int)$summary['qty_received']) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">คงเหลือ (ชิ้น)</div>
                                    <div class="kpi num"><?= number_format((int)$summary['qty_available']) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">ขายไปแล้ว (ชิ้น)</div>
                                    <div class="kpi num"><?= number_format((int)$summary['qty_sold']) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="muted small">ต้นทุน/ชิ้น (ล็อต)</div>
                                    <div class="kpi num"><?= number_format((float)$summary['cost_price'], 2) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header fw-semibold">สรุปกำไร</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="muted small">รายได้ (Revenue)</div>
                                    <div class="kpi num"><?= number_format((float)$summary['revenue'], 2) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="muted small">ต้นทุนขาย (COGS)</div>
                                    <div class="kpi num"><?= number_format((float)$summary['cogs'], 2) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="muted small">กำไรขั้นต้น (Gross Profit)</div>
                                    <div class="kpi num"><?= number_format((float)$summary['gross_profit'], 2) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header fw-semibold">รายการขายที่ตัดจากล็อตนี้ (Lot Ledger)</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>วันที่ตัดล็อต</th>
                                            <th>ผู้ซื้อ</th>
                                            <th class="num">จำนวน</th>
                                            <th class="num">ราคาขาย/ชิ้น</th>
                                            <th class="num">ต้นทุน/ชิ้น</th>
                                            <th class="num">รายได้</th>
                                            <th class="num">กำไร</th>
                                            <th class="num">Payment ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($ledger)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">ยังไม่มีการตัดล็อตจากการขาย</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($ledger as $r): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                                                    <td><?= htmlspecialchars($r['display_name'] ?: $r['user_name']) ?></td>
                                                    <td class="num"><?= number_format((int)$r['qty']) ?></td>
                                                    <td class="num"><?= number_format((float)$r['unit_sell_price'], 2) ?></td>
                                                    <td class="num"><?= number_format((float)$r['unit_cost_price'], 2) ?></td>
                                                    <td class="num"><?= number_format((float)$r['line_revenue'], 2) ?></td>
                                                    <td class="num"><?= number_format((float)$r['line_profit'], 2) ?></td>
                                                    <td class="num">#<?= (int)$r['payment_id'] ?></td>
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
