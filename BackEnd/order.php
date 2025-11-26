<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once UTILS_PATH . '/db_with_log.php';

if (!isset($_SESSION['admin_id'])) {
  header("Location: " . BACKEND_URL . "/Users/ad_login.php");
  exit;
}

$conn = connectDBWithLog();

$pageTitle  = "รายการออร์เดอร์";
$activeMenu = "order";
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <?php include BACKEND_PATH . "/partials/admin_head.php"; ?>

    <style>
        body {
            font-family: 'Kanit', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .order-status-badge {
            border-radius: 999px;
            padding: 0.25rem 0.65rem;
            font-size: 0.8rem;
        }
    </style>
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
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end mb-0">
                                <li class="breadcrumb-item"><a href="#">หน้าหลัก</a></li>
                                <li class="breadcrumb-item active">รายการออร์เดอร์</li>
                            </ol>
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
                            <form class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">ค้นหา (ชื่อลูกค้า / เลขออร์เดอร์)</label>
                                    <input type="text" class="form-control" placeholder="เช่น ORD2025-0001, สมชาย">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">สถานะออร์เดอร์</label>
                                    <select class="form-select">
                                        <option value="">— ทั้งหมด —</option>
                                        <option value="pending">รอชำระเงิน</option>
                                        <option value="paid">ชำระเงินแล้ว</option>
                                        <option value="shipping">กำลังจัดส่ง</option>
                                        <option value="done">เสร็จสมบูรณ์</option>
                                        <option value="cancel">ยกเลิก</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">ช่วงวันที่สั่งซื้อ</label>
                                    <input type="date" class="form-control">
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
                                        <!-- แถวตัวอย่าง (ภายหลังวนจาก PHP ได้เลย) -->
                                        <tr>
                                            <td><span class="fw-semibold">ORD2025-0001</span></td>
                                            <td>26/11/2025 14:30</td>
                                            <td>สมชาย ใจดี</td>
                                            <td class="text-center">3</td>
                                            <td class="text-end">1,250.00</td>
                                            <td>
                                                <span class="badge bg-info order-status-badge">กำลังจัดส่ง</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success order-status-badge">
                                                    <i class="fa-solid fa-circle-check me-1"></i> ชำระเงินแล้ว
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-outline-primary btn-sm">
                                                    <i class="fa-solid fa-eye me-1"></i> ดู
                                                </button>
                                                <button class="btn btn-outline-secondary btn-sm">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td><span class="fw-semibold">ORD2025-0002</span></td>
                                            <td>26/11/2025 13:05</td>
                                            <td>นรีรัตน์ ศรีแก้วอินทร์</td>
                                            <td class="text-center">1</td>
                                            <td class="text-end">299.00</td>
                                            <td>
                                                <span class="badge bg-warning text-dark order-status-badge">รอชำระเงิน</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark order-status-badge">
                                                    <i class="fa-solid fa-clock me-1"></i> รอชำระ
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-outline-primary btn-sm">
                                                    <i class="fa-solid fa-eye me-1"></i> ดู
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td><span class="fw-semibold">ORD2025-0003</span></td>
                                            <td>25/11/2025 19:45</td>
                                            <td>วีระชัย มีสุข</td>
                                            <td class="text-center">5</td>
                                            <td class="text-end">2,050.00</td>
                                            <td>
                                                <span class="badge bg-success order-status-badge">เสร็จสมบูรณ์</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success order-status-badge">
                                                    <i class="fa-solid fa-circle-check me-1"></i> ชำระเงินแล้ว
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-outline-primary btn-sm">
                                                    <i class="fa-solid fa-eye me-1"></i> ดู
                                                </button>
                                                <button class="btn btn-outline-secondary btn-sm">
                                                    <i class="fa-solid fa-print"></i>
                                                </button>
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ส่วน pagination -->
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <span class="text-muted small">แสดง 1–10 จาก 120 รายการ</span>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#">«</a>
                                    </li>
                                    <li class="page-item active">
                                        <a class="page-link" href="#">1</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">2</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">3</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">»</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>

                </div><!-- /.container-fluid -->
            </div><!-- /.app-content -->
        </main>

        <!-- footer ถ้ามี -->
        <!-- <?php include 'partials/admin_footer.php'; ?> -->
    </div><!-- /.app-wrapper -->

    <!-- JS: Bootstrap + AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/4.0.0-alpha2/js/adminlte.min.js"></script>

</body>

</html>