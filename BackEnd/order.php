<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/slipService.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BACKEND_URL . "/Users/ad_login.php");
    exit;
}

$conn = connectDBWithLog();

$pageTitle  = "รายการออร์เดอร์";
$activeMenu = "order";

// Filters & pagination สำหรับรายการออร์เดอร์ (ดึงจาก payments)
$search    = trim($_GET['q'] ?? '');
$status    = $_GET['status'] ?? '';
$orderDate = $_GET['order_date'] ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[] = "(CONCAT('ORD', LPAD(p.id, 4, '0')) LIKE ? 
                 OR u.first_name LIKE ? 
                 OR u.last_name LIKE ? 
                 OR u.display_name LIKE ?)";
    $kw = '%' . $search . '%';
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $types   .= 'ssss';
}

if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $where[]  = "p.status = ?";
    $params[] = $status;
    $types   .= 's';
}

if ($orderDate !== '') {
    $where[]  = "DATE(p.created_at) = ?";
    $params[] = $orderDate;
    $types   .= 's';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sqlBase  = " FROM payments p JOIN users u ON p.user_id = u.id ";

// นับจำนวนทั้งหมด
$sqlCount  = "SELECT COUNT(*) c" . $sqlBase . " " . $whereSql;
$resCount  = db_query($conn, $sqlCount, $params, $types);
$rowCount  = $resCount ? $resCount->fetch_assoc() : ['c' => 0];
$totalRows = (int)($rowCount['c'] ?? 0);

$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// ดึงข้อมูลหน้า current
$sqlData = "SELECT p.*, 
                   u.first_name, 
                   u.last_name, 
                   u.display_name,
                   CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS user_name"
    . $sqlBase . " " . $whereSql
    . " ORDER BY p.id DESC LIMIT ? OFFSET ?";

$params2 = $params;
$params2[] = $perPage;
$params2[] = $offset;
$types2  = $types . "ii";

$resData = db_query($conn, $sqlData, $params2, $types2);
$orders  = [];
if ($resData && $resData->num_rows > 0) {
    while ($r = $resData->fetch_assoc()) {
        $orders[] = $r;
    }
}

$fromRow = $totalRows > 0 ? $offset + 1 : 0;
$toRow   = min($offset + $perPage, $totalRows);

function build_order_query(array $extra = []): string
{
    $params = $_GET;
    foreach ($extra as $k => $v) {
        $params[$k] = $v;
    }
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <?php
    $extraHead = '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/admin_orders.css?v=' . filemtime(BASE_PATH . '/assets/css/admin_orders.css') . '">';
    include BACKEND_PATH . "/partials/admin_head.php";
    ?>
</head>


<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini">

    <div class="app-wrapper">

        <?php include BACKEND_PATH . "/partials/admin_navbar.php"; ?>
        <?php include BACKEND_PATH . "/partials/admin_sidebar.php"; ?>


        <main class="app-main">

            <!-- ส่วนหัวหน้าเพจ -->
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-0 fw-bold">รายการออร์เดอร์</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- เนื้อหา -->
            <div class="app-content">
                <div class="container-fluid">

                    <!-- แถวฟิลเตอร์ค้นหา -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="get" class="row g-2 align-items-end order-filter-card">
                                <div class="col-md-4">
                                    <label class="form-label">ค้นหา (ชื่อลูกค้า / เลขออร์เดอร์)</label>
                                    <input type="text" name="q" class="form-control" placeholder="เช่น ORD0001, สมชาย"
                                        value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">สถานะออร์เดอร์</label>
                                    <select name="status" class="form-select">
                                        <option value="">— ทั้งหมด —</option>
                                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>pending</option>
                                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>approved</option>
                                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">ช่วงวันที่สั่งซื้อ</label>
                                    <input type="date" name="order_date" class="form-control" value="<?= htmlspecialchars($orderDate) ?>">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> ค้นหา
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- การ์ดตารางออร์เดอร์ -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">รายการออร์เดอร์ทั้งหมด</h5>
                            <span class="text-muted small">แสดง 10 รายการล่าสุด</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">เลขที่</th>
                                            <th>วันที่สั่งซื้อ</th>
                                            <th>ชื่อลูกค้า</th>
                                            <th class="text-center">จำนวนสินค้า</th>
                                            <th class="text-end">ยอดรวม (บาท)</th>
                                            <th>สถานะออร์เดอร์</th>
                                            <th>สถานะชำระเงิน</th>
                                            <th class="text-center" style="width: 140px;">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($orders)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">ยังไม่มีออร์เดอร์</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($orders as $o):
                                                $id = (int)($o['id'] ?? 0);
                                                $orderCode = 'ORD' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
                                                $createdAt = $o['created_at'] ?? '';
                                                $userName = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                                                if ($userName === '') {
                                                    $userName = $o['display_name'] ?? '-';
                                                }

                                                // นับจำนวนสินค้า
                                                $itemsCount = 1;
                                                if (!empty($o['items_json'])) {
                                                    $decoded = json_decode($o['items_json'], true);
                                                    if (is_array($decoded)) {
                                                        $sum = 0;
                                                        foreach ($decoded as $it) {
                                                            $qty = isset($it['quantity']) ? (int)$it['quantity'] : 1;
                                                            if ($qty < 1) $qty = 1;
                                                            $sum += $qty;
                                                        }
                                                        if ($sum > 0) {
                                                            $itemsCount = $sum;
                                                        }
                                                    }
                                                }

                                                $amount = (float)($o['amount'] ?? 0);
                                                $paymentStatus = $o['status'] ?? 'pending';

                                                // แสดงสถานะออร์เดอร์ (สรุปจากสถานะการชำระเงิน)
                                                $orderStatusText = match ($paymentStatus) {
                                                    'approved' => 'ชำระเงินแล้ว',
                                                    'rejected' => 'ยกเลิก',
                                                    default    => 'รอชำระเงิน',
                                                };

                                                // สถานะการชำระเงินแบบเดียวกับหน้า Payment Slips
                                                $paymentBadge = match ($paymentStatus) {
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    default    => 'warning',
                                                };
                                            ?>
                                                <tr>
                                                    <td><span class="fw-semibold"><?= htmlspecialchars($orderCode) ?></span></td>
                                                    <td><?= htmlspecialchars($createdAt) ?></td>
                                                    <td><?= htmlspecialchars($userName) ?></td>
                                                    <td class="text-center"><?= $itemsCount ?></td>
                                                    <td class="text-end"><?= number_format($amount, 2) ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary order-status-badge">
                                                            <?= htmlspecialchars($orderStatusText) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge text-bg-<?= $paymentBadge ?> payment-status-badge">
                                                            <?= htmlspecialchars($paymentStatus) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="<?= BACKEND_URL ?>/payments/view.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fa-solid fa-eye me-1"></i> ดู
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ส่วน pagination -->
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <?php if ($totalRows > 0): ?>
                                    แสดง <?= $fromRow ?>–<?= $toRow ?> จาก <?= $totalRows ?> รายการ
                                <?php else: ?>
                                    ไม่มีรายการออร์เดอร์
                                <?php endif; ?>
                            </span>
                            <nav>
                                <?php if ($totalPages > 1): ?>
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page <= 1 ? '#' : build_order_query(['page' => $page - 1]) ?>">«</a>
                                        </li>
                                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= build_order_query(['page' => $p]) ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : build_order_query(['page' => $page + 1]) ?>">»</a>
                                        </li>
                                    </ul>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div><!-- /.container-fluid -->
                </div><!-- /.app-content -->
        </main>
    </div><!-- /.app-wrapper -->

    <?php include BACKEND_PATH . '/partials/admin_footer.php'; ?>
    <?php include BACKEND_PATH . '/partials/admin_script.php'; ?>

</body>

</html>