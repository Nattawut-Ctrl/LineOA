<?php
session_start(); // ต้องอยู่บรรทัดแรกสุดเสมอ
require_once __DIR__ . '/../../config.php';

// ถ้าไม่ได้ล็อกอิน (ไม่มี session admin_id) ให้เด้งไปหน้า login
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../Users/ad_login.php');
    exit;
}


require_once UTILS_PATH . '/db_with_log.php';

$conn = connectDBWithLog();

// โหลดรายการสินค้าเดิมไว้สำหรับ dropdown
$products = [];
$res = db_query($conn, "SELECT id, name FROM products ORDER BY id DESC");

while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เพิ่มสินค้า / เพิ่มสต็อก</title>

    <?php $pageTitle = 'เพิ่มสินค้า / เพิ่มสต็อก'; ?>
    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
    <style>
        .variant-row {
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .table td img {
            border-radius: 8px;
            object-fit: cover;
        }

        .page-title {
            font-weight: 600;
        }

        .card {
            border-radius: 0.75rem;
        }
    </style>
</head>

<body>
    <!-- Top bar / Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="#">
                <i class="bi bi-box-seam me-1"></i> ระบบจัดการสินค้า
            </a>

            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="text-light small me-3">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
                </span>
                <a href="../Users/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <div class="container pb-4">

        <?php
        // --------------------------
        // ตั้งค่าการแบ่งหน้า + ค้นหา/กรอง
        // --------------------------
        $perPage = 5; // จำนวนสินค้าต่อหน้า ปรับได้
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;

        // เตรียมเงื่อนไขค้นหา/กรอง
        $where = " WHERE 1=1 ";

        // คำค้นหา (ชื่อสินค้า / หมวดหมู่)
        if (!empty($_GET['q'])) {
            $q = '%' . $conn->real_escape_string($_GET['q']) . '%';
            $where .= " AND (p.name LIKE '$q' OR p.category LIKE '$q')";
        }

        // กรองสต็อก
        if (!empty($_GET['filter_stock'])) {
            $fs = $_GET['filter_stock'];
            if ($fs === 'low') {
                $where .= " AND p.stock > 0 AND p.stock <= 5"; // กำหนดเองได้ว่า "เหลือน้อย" คือเท่าไหร่
            } elseif ($fs === 'out') {
                $where .= " AND p.stock <= 0";
            }
        }

        // นับจำนวนสินค้าทั้งหมด (ตามเงื่อนไขค้นหา)
        $countRes = $conn->query("SELECT COUNT(*) AS total FROM products p $where");
        $totalRows = ($countRes && $countRes->num_rows > 0)
            ? (int)$countRes->fetch_assoc()['total']
            : 0;

        $totalPages = max(1, ceil($totalRows / $perPage));

        // ถ้าเลข page เกินหน้าสุดท้าย ให้ดึงหน้าสุดท้ายแทน
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $perPage;

        // ดึงเฉพาะสินค้าของหน้านี้
        $productsList = $conn->query("
            SELECT p.*,
                (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) AS variant_count
            FROM products p
            $where
            ORDER BY p.id DESC
            LIMIT $perPage OFFSET $offset
        ");
        ?>

        <!-- Header Page -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 page-title mb-1">จัดการสินค้า</h1>
                <p class="text-muted mb-0">
                    เพิ่ม / แก้ไข / จัดการสต็อกสินค้าให้พร้อมสำหรับการใช้งานจริง
                </p>
            </div>
            <!-- ถ้ามีหน้า dashboard หลักสามารถใส่ลิงก์กลับได้ -->
            <!-- <a href="ad_dashboard.php" class="btn btn-outline-light text-dark btn-sm">กลับแดชบอร์ด</a> -->
        </div>

        <!-- แสดงผลสำเร็จ / error -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                if ($_GET['success'] === 'new_product_created') {
                    echo "🎉 เพิ่มสินค้าใหม่เรียบร้อยแล้ว!";
                } elseif ($_GET['success'] === 'variant_stock_added') {
                    echo "📦 เพิ่มสต็อกให้ตัวเลือกสินค้าเรียบร้อย!";
                } elseif ($_GET['success'] === 'product_stock_added') {
                    echo "📦 เพิ่มสต็อกสินค้าเรียบร้อย!";
                } elseif ($_GET['success'] === 'updated') {
                    echo "🔄 อัปเดตข้อมูลเรียบร้อย!";
                } else {
                    echo "ดำเนินการสำเร็จ!";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ❗ เกิดข้อผิดพลาด:
                <?php
                if ($_GET['error'] === 'invalid_product_input') {
                    echo "ข้อมูลสินค้าไม่ครบหรือไม่ถูกต้อง";
                } elseif ($_GET['error'] === 'invalid_input') {
                    echo "ข้อมูลไม่ครบถ้วนในการเพิ่มสต็อก";
                } else {
                    echo "ไม่สามารถดำเนินการได้";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb เล็กๆ -->
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../Dashboard/ad_dashboard.php">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active" aria-current="page">จัดการสินค้า</li>
            </ol>
        </nav>

        <!-- Header Page -->
        <!-- <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 page-title mb-1">จัดการสินค้า</h1>
                <p class="text-muted mb-0">
                    เพิ่ม / แก้ไข / จัดการสต็อกสินค้าให้พร้อมสำหรับการใช้งานจริง
                </p>
            </div>
        </div> -->

        <!-- ฟอร์มค้นหา / กรอง -->
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-5">
                <input
                    type="text"
                    class="form-control"
                    name="q"
                    placeholder="ค้นหาชื่อสินค้า / หมวดหมู่"
                    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="filter_stock" class="form-select">
                    <option value="">-- สต็อกทั้งหมด --</option>
                    <option value="low" <?= ($_GET['filter_stock'] ?? '') === 'low' ? 'selected' : '' ?>>
                        สต็อกเหลือน้อย (1–5 ชิ้น)
                    </option>
                    <option value="out" <?= ($_GET['filter_stock'] ?? '') === 'out' ? 'selected' : '' ?>>
                        สต็อกหมด
                    </option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-search"></i> ค้นหา
                </button>
            </div>
        </form>


        <!-- รายการสินค้า -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-semibold">📦 รายการสินค้า</span>
                </div>
                <span class="badge bg-secondary">
                    ทั้งหมด <?= number_format($totalRows) ?> รายการ
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">รูป</th>
                                <th scope="col">ชื่อสินค้า</th>
                                <th scope="col">ราคา</th>
                                <th scope="col">สต็อก</th>
                                <th scope="col">ตัวเลือก</th>
                                <th scope="col" width="220" class="text-end">จัดการ</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php while ($p = $productsList->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-muted">#<?= $p['id'] ?></td>
                                    <td>
                                        <?php if (!empty($p['image'])): ?>
                                            <img
                                                src="<?= $p['image'] ?>"
                                                width="60"
                                                height="60"
                                                class="img-thumbnail"
                                                style="object-fit: cover;"
                                                alt="product-image">
                                        <?php else: ?>
                                            <span class="text-muted small">ไม่มีรูป</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                                        <?php if (!empty($p['category'])): ?>
                                            <div class="small text-muted">หมวดหมู่: <?= htmlspecialchars($p['category']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($p['price'], 2) ?></td>
                                    <td>
                                        <?php if ($p['stock'] <= 0): ?>
                                            <span class="badge bg-danger-subtle border border-danger text-danger">
                                                สต็อกหมด
                                            </span>
                                        <?php elseif ($p['stock'] <= 5): ?>
                                            <span class="badge bg-warning-subtle border border-warning text-warning">
                                                เหลือน้อย (<?= number_format($p['stock']) ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle border border-success text-success">
                                                <?= number_format($p['stock']) ?> ชิ้น
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-info-subtle border border-info text-info">
                                            <?= $p['variant_count'] ?> ตัวเลือก
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <!-- ปุ่มแก้ไข เปิด Modal -->
                                            <button
                                                class="btn btn-outline-warning editProductBtn"
                                                data-id="<?= $p['id'] ?>">
                                                <i class="bi bi-pencil-square me-1"></i> แก้ไข
                                            </button>

                                            <!-- ปุ่มลบ -->
                                            <button
                                                class="btn btn-outline-danger deleteProductBtn"
                                                data-id="<?= $p['id'] ?>">
                                                <i class="bi bi-trash me-1"></i> ลบ
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                            <?php if ($totalRows === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        ยังไม่มีสินค้าในระบบ
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="card-footer border-0">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">

                            <!-- ปุ่มก่อนหน้า -->
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">ก่อนหน้า</a>
                            </li>

                            <!-- เลขหน้า -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- ปุ่มถัดไป -->
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">ถัดไป</a>
                            </li>

                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>

        <!-- จัดการสินค้า: ฟอร์ม 2 ฝั่ง -->
        <div class="row g-4 mt-1">

            <!-- 1) ฟอร์มเพิ่มสต็อกจากสินค้าเดิม -->
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="mb-0 fw-semibold">
                            ➕ เพิ่มสต็อกจากสินค้าเดิม
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="save_new_stock.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">เลือกสินค้า</label>
                                <select name="product_id" id="productSelect" class="form-select" required>
                                    <option value="">-- เลือกสินค้า --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    เลือกสินค้าที่ต้องการเพิ่มสต็อก และกำหนดจำนวนในตัวเลือกด้านล่าง
                                </div>
                            </div>

                            <div id="variantArea"></div>

                            <div class="d-grid">
                                <button class="btn btn-primary mt-2">
                                    <i class="bi bi-box-seam me-1"></i> เพิ่มสต็อก
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 2) ฟอร์มเพิ่มสินค้าใหม่ + variants -->
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">🆕 เพิ่มสินค้าใหม่</h5>
                        <span class="small text-muted">กรอกข้อมูลสินค้าให้ครบถ้วน</span>
                    </div>
                    <div class="card-body">
                        <form action="save_new_product.php" method="POST" enctype="multipart/form-data">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">ชื่อสินค้า</label>
                                <input type="text" name="name" class="form-control" required placeholder="เช่น เสื้อยืด Oversize รุ่น A">
                            </div>

                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">หมวดหมู่</label>
                                    <input type="text" name="category" class="form-control" required placeholder="เช่น เสื้อผ้า, รองเท้า">
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">ราคาเริ่มต้น</label>
                                    <div class="input-group">
                                        <span class="input-group-text">฿</span>
                                        <input type="number" name="price" step="0.01" class="form-control" required>
                                    </div>
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">สต็อกเริ่มต้น</label>
                                    <input type="number" name="stock" class="form-control" value="0">
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">รหัสสินค้า (SKU)</label>
                                    <input type="text" name="sku" class="form-control" placeholder="เช่น SHIRT-A01">
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">หน่วย</label>
                                    <input type="text" name="unit" class="form-control" placeholder="เช่น ชิ้น, กล่อง">
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">สถานะสินค้า</label>
                                    <select name="status" class="form-select">
                                        <option value="active">แสดงบนระบบ</option>
                                        <option value="inactive">ซ่อนชั่วคราว</option>
                                    </select>
                                </div>
                            </div>


                            <div class="mb-3">
                                <label class="form-label fw-semibold">คำอธิบาย</label>
                                <textarea name="description" rows="3" class="form-control" placeholder="รายละเอียดสินค้า / เงื่อนไขเพิ่มเติม"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">รูปภาพสินค้า</label>
                                <input type="file" name="image" id="mainImageInput" class="form-control">
                                <div class="form-text">รองรับไฟล์ .jpg, .png ขนาดไม่เกิน 5 MB</div>
                                <img id="mainImagePreview" src="#" alt="" class="mt-2 d-none" width="120">
                            </div>


                            <hr class="my-3">

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">ตัวเลือกสินค้า (Variants)</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addVariantBtn">
                                    <i class="bi bi-plus-circle me-1"></i> เพิ่มตัวเลือก
                                </button>
                            </div>
                            <p class="small text-muted mb-2">
                                เช่น สี / ไซส์ / แพ็กเกจ ฯลฯ ถ้าไม่มีตัวเลือก สามารถเว้นว่างส่วนนี้ได้
                            </p>

                            <div id="variantsContainer"></div>

                            <div class="d-grid mt-3">
                                <button class="btn btn-success">
                                    <i class="bi bi-save2 me-1"></i> บันทึกสินค้าใหม่
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div><!-- row -->

    </div> <!-- container -->

    <!-- EDIT PRODUCT MODAL -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขสินค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="editProductContent">
                    <!-- โหลดข้อมูลมาใส่ด้วย AJAX -->
                </div>

            </div>
        </div>
    </div>

    <script>
        // ---------------------------
        // โหลดตัวเลือก variant ของสินค้าเดิมเพื่อเพิ่มสต็อก
        // ---------------------------
        const productSelectEl = document.getElementById('productSelect');
        if (productSelectEl) {
            productSelectEl.addEventListener('change', function() {
                const productId = this.value;
                const variantArea = document.getElementById('variantArea');

                if (!productId) {
                    variantArea.innerHTML = "";
                    return;
                }

                fetch("load_variants.php?product_id=" + productId)
                    .then(res => res.text())
                    .then(html => {
                        variantArea.innerHTML = html;
                    });
            });
        }

        // ---------------------------
        // เพิ่มตัวเลือกสินค้าใหม่
        // ---------------------------
        document.getElementById('addVariantBtn').addEventListener('click', () => {
            const container = document.getElementById('variantsContainer');

            const div = document.createElement('div');
            div.className = 'variant-row';

            div.innerHTML = `
                <div class="d-flex justify-content-between mb-2">
                    <strong>ตัวเลือกสินค้า</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger removeVariant">
                        <i class="bi bi-x-circle"></i> ลบ
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ชื่อ (เช่น สีแดง / ไซส์ M)</label>
                        <input type="text" name="variant_name[]" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">ราคา</label>
                        <input type="number" step="0.01" name="variant_price[]" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">สต็อก</label>
                        <input type="number" name="variant_stock[]" class="form-control">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">รูป</label>
                        <input type="file" name="variant_image[]" class="form-control">
                    </div>
                </div>
            `;

            container.appendChild(div);

            // ปุ่มลบตัวเลือก
            div.querySelector('.removeVariant').onclick = () => div.remove();
        });

        // auto close alert
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                const alertList = document.querySelectorAll('.alert');
                alertList.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 3000);
        });
    </script>

    <script>
        // เปิด modal สำหรับแก้ไขสินค้า
        document.querySelectorAll('.editProductBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;

                fetch("ajax_load_product.php?id=" + id)
                    .then(res => res.text())
                    .then(html => {
                        const contentEl = document.getElementById("editProductContent");
                        contentEl.innerHTML = html;

                        // เปิด modal
                        const myModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                        myModal.show();

                        // ---------- จับปุ่มลบ variant ----------
                        const variantDeleteButtons = contentEl.querySelectorAll('.deleteVariantBtn');

                        variantDeleteButtons.forEach(vbtn => {
                            vbtn.addEventListener('click', () => {
                                const vid = vbtn.dataset.id;
                                if (!confirm("ต้องการลบตัวเลือกสินค้านี้ใช่หรือไม่?")) return;

                                const fd = new FormData();
                                fd.append('id', vid);

                                fetch("ajax_delete_variant.php", {
                                        method: "POST",
                                        body: fd
                                    })
                                    .then(r => r.text())
                                    .then(txt => {
                                        if (txt.trim() === "success") {
                                            // ลบแถวออกจาก modal หรือจะ reload หน้าเลยก็ได้
                                            const row = vbtn.closest('.variant-row, tr, .variant-item');
                                            if (row) row.remove();
                                        } else {
                                            console.error("Delete variant failed:", txt);
                                            alert("ลบตัวเลือกสินค้าไม่สำเร็จ");
                                        }
                                    })
                                    .catch(err => {
                                        console.error("Fetch error:", err);
                                        alert("เกิดข้อผิดพลาดในการลบตัวเลือกสินค้า");
                                    });
                            });
                        });
                        // ---------- จบส่วนลบ variant ----------

                        // ---------- form update ----------
                        const updateForm = document.getElementById("updateProductForm");
                        if (updateForm) {
                            updateForm.addEventListener('submit', (ev) => {
                                ev.preventDefault();

                                fetch("ajax_update_product.php", {
                                        method: "POST",
                                        body: new FormData(updateForm)
                                    })
                                    .then(res => res.text())
                                    .then(result => {
                                        if (result.trim() === "success") {
                                            location.reload();
                                        } else {
                                            console.error("Update failed:", result);
                                            alert("บันทึกการแก้ไขไม่สำเร็จ");
                                        }
                                    })
                                    .catch(err => {
                                        console.error("Fetch error:", err);
                                        alert("เกิดข้อผิดพลาดในการบันทึกการแก้ไข");
                                    });
                            });
                        }
                        // ---------- จบ form update ----------
                    });
            });
        });

        // ลบสินค้า (ทั้งชิ้น)
        document.querySelectorAll('.deleteProductBtn').forEach(btn => {
            btn.onclick = () => {
                if (!confirm("ลบสินค้านี้?")) return;

                const fd = new FormData();
                fd.append('id', btn.dataset.id);

                fetch("ajax_delete_product.php", {
                        method: "POST",
                        body: fd
                    })
                    .then(r => r.text())
                    .then(txt => {
                        if (txt.trim() === "success") {
                            location.reload();
                        } else {
                            console.error("Delete failed:", txt);
                            alert("ลบสินค้าไม่สำเร็จ");
                        }
                    })
                    .catch(err => console.error("Fetch error:", err));
            };
        });
    </script>

    <script>
        // Preview รูปภาพหลักก่อนบันทึก
        document.addEventListener("DOMContentLoaded", function() {
            const input = document.getElementById('mainImageInput');
            const preview = document.getElementById('mainImagePreview');

            if (input && preview) {
                input.addEventListener('change', function(e) {
                    const [file] = this.files;
                    if (file) {
                        preview.src = URL.createObjectURL(file);
                        preview.classList.remove('d-none');
                    } else {
                        preview.src = '#';
                        preview.classList.add('d-none');
                    }
                });
            }
        });
    </script>

</body>

</html>