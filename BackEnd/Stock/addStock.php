<?php
session_start(); // ต้องอยู่บรรทัดแรกสุดเสมอ
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/product_image_helper.php';

require_admin();
$conn = connectDBWithLog();

// ถ้าไม่ได้ล็อกอิน (ไม่มี session admin_id) ให้เด้งไปหน้า login
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../Users/ad_login.php');
    exit;
}

// โหลดรายการสินค้าเดิมไว้สำหรับ dropdown
$products = [];
$res = db_query($conn, "SELECT id, name FROM products ORDER BY id DESC");

while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}

$pageTitle  = "เพิ่มสินค้า / เพิ่มสต็อก";
$activeMenu = "stock";
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
            margin-bottom: .75rem;
        }

        .stock-manage-wrapper {
            padding-bottom: 1.5rem;
        }

        @media (max-width: 767.98px) {
            .stock-manage-wrapper {
                padding-inline: .5rem;
            }
        }

        .variant-row-placeholder {
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

        /* ===== เน้นฟอร์มเพิ่มสินค้า + variants ให้เด่นขึ้น ===== */
        .card-main-feature {
            border: 1px solid rgba(25, 135, 84, .25);
            box-shadow: 0 0.55rem 1.5rem rgba(25, 135, 84, .1);
        }

        .card-main-feature .card-header {
            background: linear-gradient(135deg, #198754, #20c997);
            color: #fff;
        }

        .card-main-feature .card-header span.small {
            color: rgba(255, 255, 255, .85);
        }

        .card-main-feature h5 {
            color: #fff;
        }

        .section-label {
            font-size: .85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6c757d;
        }

        .section-box {
            border-radius: .75rem;
            border: 1px dashed #ced4da;
            padding: .75rem 1rem;
            background-color: #f8f9fa;
            margin-bottom: 1rem;
        }

        .section-box-title {
            font-size: .9rem;
            font-weight: 600;
            margin-bottom: .5rem;
        }

        /* กล่อง variant ให้ดูเป็นบล็อกชัด ๆ */
        .variant-row {
            border-radius: .75rem;
            border: 1px solid #e2e3e5;
            background: #fcfcfd;
            padding: .75rem .85rem;
            margin-bottom: .75rem;
        }

        .variant-row-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .5rem;
        }

        .variant-row-header strong {
            font-size: .9rem;
        }

        .variant-row-header small {
            font-size: .75rem;
            color: #6c757d;
        }

        .variant-row .form-label {
            font-size: .8rem;
            margin-bottom: .25rem;
        }
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini">
    <div class="app-wrapper">
        <!-- Top bar / Navbar -->
        <?php include BACKEND_PATH . '/partials/admin_navbar.php'; ?>
        <?php include BACKEND_PATH . '/partials/admin_sidebar.php'; ?>

        <main class="app-main">
            <!-- <div class="app-content-header">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><?= htmlspecialchars($pageTitle ?? "") ?></h3>
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?= BACKEND_URL ?>/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle ?? "") ?></li>
                    </ol>
                </div>
            </div> -->
            <div class="app-content">
                <div class="container-fluid">

                    <section class="content pt-3">
                        <div class="container-fluid">

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
                                $productsListRes = $conn->query("
    SELECT p.*,
        (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) AS variant_count
    FROM products p
    $where
    ORDER BY p.id DESC
    LIMIT $perPage OFFSET $offset
");

                                // แปลงเป็น array เพื่อจะได้วนใช้หลายรอบ + เตรียม list สำหรับ fallback รูป variant
                                $productsList           = [];
                                $productIdsNeedFallback = [];

                                if ($productsListRes) {
                                    while ($row = $productsListRes->fetch_assoc()) {
                                        $productsList[] = $row;

                                        if (empty($row['image'])) {
                                            $productIdsNeedFallback[] = (int)$row['id'];
                                        }
                                    }
                                }

                                // ดึง fallback รูปจาก variant สำหรับสินค้าในหน้านี้ ที่ไม่มีรูปหลัก
                                $fallbackVariantImages = [];
                                if (!empty($productIdsNeedFallback)) {
                                    $fallbackVariantImages = loadVariantFallbackImages($conn, $productIdsNeedFallback);
                                }
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
                                                    <?php foreach ($productsList as $p): ?>
                                                        <tr>
                                                            <td class="text-muted">#<?= $p['id'] ?></td>
                                                            <td>
                                                                <?php
                                                                // ใช้รูปหลักก่อน
                                                                $imgPath = $p['image'] ?? '';

                                                                // ถ้าไม่มีรูปหลัก → ลองหาจาก variant
                                                                $pid = (int)$p['id'];
                                                                if (empty($imgPath) && !empty($fallbackVariantImages[$pid])) {
                                                                    $imgPath = $fallbackVariantImages[$pid];
                                                                }

                                                                // แปลง path เป็น URL เต็ม (ใช้ helper จาก product_image_helper.php)
                                                                $imgUrl = buildImageUrlFromPath($imgPath);
                                                                ?>

                                                                <?php if (!empty($imgUrl)): ?>
                                                                    <img
                                                                        src="<?= htmlspecialchars($imgUrl) ?>"
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
                                                    <?php endforeach; ?>

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

                                    <!-- 1) ฟอร์มเพิ่มสินค้าใหม่ + variants (ฟังก์ชันหลัก) -->
                                    <div class="col-lg-8 col-xl-9">
                                        <div class="card shadow-sm card-main-feature h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-0 fw-semibold">
                                                        🆕 เพิ่มสินค้าใหม่
                                                    </h5>
                                                    <span class="small d-block mt-1">
                                                        กรอกข้อมูลสินค้าให้ครบถ้วน และตั้งค่าตัวเลือก (Variants) ได้ในที่เดียว
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <form action="save_new_product.php" method="POST" enctype="multipart/form-data">

                                                    <!-- กลุ่ม ข้อมูลสินค้าหลัก -->
                                                    <div class="section-box">
                                                        <div class="section-box-title">
                                                            ข้อมูลสินค้าหลัก
                                                        </div>
                                                        <div class="row g-3">
                                                            <div class="col-12">
                                                                <label class="form-label fw-semibold">ชื่อสินค้า</label>
                                                                <input type="text" name="name" class="form-control" required
                                                                    placeholder="เช่น เสื้อยืด Oversize รุ่น A">
                                                            </div>

                                                            <div class="col-md-4">
                                                                <label class="form-label fw-semibold">หมวดหมู่</label>
                                                                <input type="text" name="category" class="form-control" required
                                                                    placeholder="เช่น เสื้อผ้า, รองเท้า">
                                                            </div>

                                                            <div class="col-md-4">
                                                                <label class="form-label fw-semibold">หน่วยนับ (Unit)</label>
                                                                <input type="text" name="unit" class="form-control"
                                                                    placeholder="เช่น ตัว, ชิ้น, ขวด">
                                                            </div>
                                                        </div>

                                                        <div class="mt-3">
                                                            <label class="form-label fw-semibold">คำอธิบาย</label>
                                                            <textarea name="description" rows="3" class="form-control"
                                                                placeholder="รายละเอียดสินค้า / เงื่อนไขเพิ่มเติม"></textarea>
                                                        </div>

                                                        <div class="mt-3 row g-3 align-items-center">
                                                            <div class="col-md-7">
                                                                <label class="form-label fw-semibold">รูปภาพสินค้า</label>
                                                                <input type="file" name="image" id="mainImageInput"
                                                                    class="form-control">
                                                                <div class="form-text">รองรับไฟล์ .jpg, .png ขนาดไม่เกิน 5 MB</div>
                                                            </div>
                                                            <div class="col-md-5 text-md-end mt-2 mt-md-0">
                                                                <img id="mainImagePreview" src="#" alt=""
                                                                    class="d-none img-thumbnail" style="max-height: 120px;">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- กลุ่ม ตัวเลือกสินค้า (Variants) -->
                                                    <div class="section-box">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <div>
                                                                <div class="section-box-title mb-0">
                                                                    ตัวเลือกสินค้า (Variants)
                                                                </div>
                                                                <small class="text-muted">
                                                                    ใช้สำหรับแยก สี / ไซส์ / แพ็กเกจ ฯลฯ ถ้าไม่มีตัวเลือก สามารถเว้นว่างได้
                                                                </small>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-outline-light bg-white text-primary"
                                                                id="addVariantBtn">
                                                                <i class="bi bi-plus-circle me-1"></i> เพิ่มตัวเลือก
                                                            </button>
                                                        </div>

                                                        <div id="variantsContainer" class="mt-2">
                                                            <!-- variant-row จะถูกเพิ่มเข้ามาด้วย JS -->
                                                        </div>
                                                    </div>

                                                    <div class="d-grid mt-3">
                                                        <button class="btn btn-success btn-lg">
                                                            <i class="bi bi-save2 me-1"></i> บันทึกสินค้าใหม่
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2) ฟอร์มเพิ่มสต็อกจากสินค้าเดิม (ฟังก์ชันรอง) -->
                                    <div class="col-lg-4 col-xl-3">
                                        <div class="card shadow-sm h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0 fw-semibold">
                                                    ➕ เพิ่มสต็อกจากสินค้าเดิม
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <form action="save_new_stock.php" method="POST" enctype="multipart/form-data">
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

                                                    <div class="d-grid mt-2">
                                                        <button class="btn btn-primary">
                                                            <i class="bi bi-box-seam me-1"></i> เพิ่มสต็อก
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
                                        <div class="variant-row-header">
                                            <div>
                                                <strong>ตัวเลือกสินค้า</strong>
                                                <small class="d-block">เช่น สีแดง / ไซส์ M / แพ็ก 3 ชิ้น</small>
                                            </div>
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
                                                <label class="form-label">SKU (ของตัวเลือกนี้)</label>
                                                <input type="text" name="variant_sku[]" class="form-control" placeholder="เช่น SHIRT-001-BLACK-M">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">ราคา</label>
                                                <input type="number" step="0.01" name="variant_price[]" class="form-control">
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">สต็อก</label>
                                                <input type="number" name="variant_stock[]" class="form-control">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">รูป (ถ้ามี)</label>
                                                <input type="file" name="variant_image[]" class="form-control">
                                            </div>
                                        </div>
                                    `;

                                    container.appendChild(div);

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
                                                                    console.log("Update successful:", result);

                                                                    const text = result.trim().toLowerCase();

                                                                    if (text.includes("success")) {
                                                                        window.location.href = "addStock.php?success=updated";
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
                                document.addEventListener('click', function(e) {
                                    if (e.target && e.target.id === 'addNewVariantInEdit') {
                                        const container = document.getElementById('newVariantContainer');
                                        if (!container) return;

                                        const div = document.createElement('div');
                                        div.className = 'variant-row mb-2';

                                        div.innerHTML = `
                                            <div class="d-flex justify-content-between mb-2">
                                                <strong>ตัวเลือกใหม่</strong>
                                                <button type="button" class="btn btn-sm btn-outline-danger removeNewVariant">
                                                    <i class="bi bi-x-circle"></i> ลบ
                                                </button>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">ชื่อ</label>
                                                    <input type="text" name="new_variant_name[]" class="form-control" required>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">ราคา</label>
                                                    <input type="number" step="0.01" name="new_variant_price[]" class="form-control">
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">สต็อก</label>
                                                    <input type="number" name="new_variant_stock[]" class="form-control">
                                                </div>
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">รูป (ถ้ามี)</label>
                                                    <input type="file" name="new_variant_image[]" class="form-control">
                                                </div>
                                            </div>
                                        `;

                                        container.appendChild(div);

                                        div.querySelector('.removeNewVariant').onclick = () => div.remove();
                                    }
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
                        </div>
                    </section>
                </div>
            </div>
        </main>

        <?php include BACKEND_PATH . '/partials/admin_footer.php'; ?>
    </div>

    <?php include BACKEND_PATH . '/partials/admin_script.php'; ?>

</body>

</html>