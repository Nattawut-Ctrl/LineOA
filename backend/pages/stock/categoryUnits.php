<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BACKEND_URL . '/pages/users/login.php');
    exit;
}

$pageTitle  = "จัดการหมวดหมู่และหน่วยนับ";
$activeMenu = "category-units";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
    <style>
        .card {
            border-radius: 0.75rem;
        }

        .page-title {
            font-weight: 600;
        }

        .section-label {
            font-size: .85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6c757d;
        }

        .category-unit-card {
            border: 1px solid rgba(124, 58, 237, .25);
            box-shadow: 0 0.55rem 1.5rem rgba(124, 58, 237, .1);
        }

        .category-unit-card .card-header {
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
            color: #fff;
        }

        .category-unit-card .card-header h5 {
            color: #fff;
        }

        .tab-content {
            margin-top: 1.5rem;
        }

        .item-badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            background-color: #e7f3ff;
            color: #0066cc;
        }

        .item-badge.inactive {
            background-color: #f5f5f5;
            color: #999;
        }

        .list-group-item {
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .action-buttons {
            white-space: nowrap;
        }
    </style>

    <script>
        const BACKEND_URL = "<?= BACKEND_URL ?>";
        const STOCK_API = BACKEND_URL + "/api/stock";
    </script>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini">
    <div class="app-wrapper">
        <!-- Top bar / Navbar -->
        <?php include BACKEND_PATH . '/partials/admin_navbar.php'; ?>
        <?php include BACKEND_PATH . '/partials/admin_sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content">
                <div class="container-fluid">

                    <section class="content pt-3">
                        <div class="container-fluid">

                            <div class="container pb-4">

                                <!-- Header Page -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h1 class="h3 page-title mb-1">จัดการหมวดหมู่และหน่วยนับ</h1>
                                        <p class="text-muted mb-0">
                                            เพิ่ม / แก้ไข / ลบหมวดหมู่และหน่วยนับสินค้า
                                        </p>
                                    </div>
                                </div>

                                <!-- แสดงผลสำเร็จ / error -->
                                <?php if (isset($_GET['success'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?php
                                        if ($_GET['success'] === 'category_created') {
                                            echo "🎉 เพิ่มหมวดหมู่เรียบร้อยแล้ว!";
                                        } elseif ($_GET['success'] === 'category_updated') {
                                            echo "🔄 อัปเดตหมวดหมู่เรียบร้อย!";
                                        } elseif ($_GET['success'] === 'category_deleted') {
                                            echo "🗑️ ลบหมวดหมู่เรียบร้อย!";
                                        } elseif ($_GET['success'] === 'unit_created') {
                                            echo "🎉 เพิ่มหน่วยนับเรียบร้อยแล้ว!";
                                        } elseif ($_GET['success'] === 'unit_updated') {
                                            echo "🔄 อัปเดตหน่วยนับเรียบร้อย!";
                                        } elseif ($_GET['success'] === 'unit_deleted') {
                                            echo "🗑️ ลบหน่วยนับเรียบร้อย!";
                                        }
                                        ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($_GET['error'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        ❗ เกิดข้อผิดพลาด: <?= htmlspecialchars($_GET['error'] ?? 'ไม่สามารถดำเนินการได้') ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Tab Navigation -->
                                <ul class="nav nav-tabs" id="categoryUnitTab">
                                    <li class="nav-item">
                                        <button class="nav-link active" data-bs-target="#tabCategories" data-bs-toggle="tab">
                                            📂 หมวดหมู่
                                        </button>
                                    </li>

                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-target="#tabUnits" data-bs-toggle="tab">
                                            📏 หน่วยนับ
                                        </button>
                                    </li>
                                </ul>

                                <!-- Tab Content -->
                                <div class="tab-content">

                                    <!-- Tab 1: Categories -->
                                    <div class="tab-pane fade show active" id="tabCategories">
                                        <div class="row g-4 mt-1">

                                            <!-- Add Category Form -->
                                            <div class="col-lg-4">
                                                <div class="card shadow-sm category-unit-card h-100">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">🆕 เพิ่มหมวดหมู่ใหม่</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <form id="addCategoryForm" action="<?= BACKEND_URL ?>/api/stock/manage_categories.php" method="POST">
                                                            <input type="hidden" name="action" value="create">

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">ชื่อหมวดหมู่</label>
                                                                <input type="text" name="name" class="form-control" required
                                                                    placeholder="เช่น เสื้อผ้า, รองเท้า">
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">คำอธิบาย</label>
                                                                <textarea name="description" rows="3" class="form-control"
                                                                    placeholder="รายละเอียดหมวดหมู่"></textarea>
                                                            </div>

                                                            <div class="d-grid">
                                                                <button type="submit" class="btn btn-success">
                                                                    <i class="bi bi-plus-circle me-1"></i> เพิ่มหมวดหมู่
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Categories List -->
                                            <div class="col-lg-8">
                                                <div class="card shadow-sm h-100">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">📋 รายการหมวดหมู่</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div id="categoriesList">
                                                            <div class="spinner-border spinner-border-sm" role="status">
                                                                <span class="visually-hidden">กำลังโหลด...</span>
                                                            </div>
                                                            กำลังโหลดข้อมูล...
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Tab 2: Units -->
                                    <div class="tab-pane fade" id="tabUnits">
                                        <div class="row g-4 mt-1">

                                            <!-- Add Unit Form -->
                                            <div class="col-lg-4">
                                                <div class="card shadow-sm category-unit-card h-100">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">🆕 เพิ่มหน่วยนับใหม่</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <form id="addUnitForm" action="<?= BACKEND_URL ?>/api/stock/manage_units.php" method="POST">
                                                            <input type="hidden" name="action" value="create">

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">ชื่อหน่วยนับ</label>
                                                                <input type="text" name="name" class="form-control" required
                                                                    placeholder="เช่น ตัว, ชิ้น, ขวด">
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">สัญลักษณ์ (ตัวย่อ)</label>
                                                                <input type="text" name="symbol" class="form-control"
                                                                    placeholder="เช่น ตัว, ชิ้น, ขวด">
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">คำอธิบาย</label>
                                                                <textarea name="description" rows="3" class="form-control"
                                                                    placeholder="รายละเอียดหน่วยนับ"></textarea>
                                                            </div>

                                                            <div class="d-grid">
                                                                <button type="submit" class="btn btn-success">
                                                                    <i class="bi bi-plus-circle me-1"></i> เพิ่มหน่วยนับ
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Units List -->
                                            <div class="col-lg-8">
                                                <div class="card shadow-sm h-100">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">📋 รายการหน่วยนับ</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div id="unitsList">
                                                            <div class="spinner-border spinner-border-sm" role="status">
                                                                <span class="visually-hidden">กำลังโหลด...</span>
                                                            </div>
                                                            กำลังโหลดข้อมูล...
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                </div><!-- tab-content -->

                            </div> <!-- container -->

                        </div>
                    </section>

                </div>
            </div>
        </main>

    </div>

    <?php include BACKEND_PATH . '/partials/admin_footer.php'; ?>
    <?php include BACKEND_PATH . '/partials/admin_script.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            loadCategories();
            loadUnits();

            // Handle category form submission
            document.getElementById('addCategoryForm').addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch(STOCK_API + '/manage_categories.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            document.getElementById('addCategoryForm').reset();
                            loadCategories();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('เกิดข้อผิดพลาด: ' + error.message);
                    });
            });

            // Handle unit form submission
            document.getElementById('addUnitForm').addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch(STOCK_API + '/manage_units.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            document.getElementById('addUnitForm').reset();
                            loadUnits();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('เกิดข้อผิดพลาด: ' + error.message);
                    });
            });
        });

        function loadCategories() {
            fetch(STOCK_API + '/get_categories.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        renderCategories(data.data);
                    } else {
                        document.getElementById('categoriesList').innerHTML =
                            '<div class="alert alert-warning">ไม่มีข้อมูลหมวดหมู่</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('categoriesList').innerHTML =
                        '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                });
        }

        function loadUnits() {
            fetch(STOCK_API + '/get_units.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        renderUnits(data.data);
                    } else {
                        document.getElementById('unitsList').innerHTML =
                            '<div class="alert alert-warning">ไม่มีข้อมูลหน่วยนับ</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('unitsList').innerHTML =
                        '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                });
        }

        function renderCategories(categories) {
            if (categories.length === 0) {
                document.getElementById('categoriesList').innerHTML =
                    '<div class="alert alert-warning">ไม่มีข้อมูลหมวดหมู่</div>';
                return;
            }

            let html = '<div class="list-group">';
            categories.forEach(cat => {
                const statusBadge = cat.status === 'active' ?
                    '<span class="item-badge">✓ ใช้งาน</span>' :
                    '<span class="item-badge inactive">✕ ปิดใช้งาน</span>';

                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-semibold">${escapeHtml(cat.name)}</h6>
                            ${cat.description ? '<small class="text-muted d-block">' + escapeHtml(cat.description) + '</small>' : ''}
                        </div>
                        <div class="action-buttons">
                            ${statusBadge}
                            <button class="btn btn-sm btn-danger ms-2" onclick="deleteCategory(${cat.id})">
                                <i class="bi bi-trash"></i> ลบ
                            </button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('categoriesList').innerHTML = html;
        }

        function renderUnits(units) {
            if (units.length === 0) {
                document.getElementById('unitsList').innerHTML =
                    '<div class="alert alert-warning">ไม่มีข้อมูลหน่วยนับ</div>';
                return;
            }

            let html = '<div class="list-group">';
            units.forEach(unit => {
                const statusBadge = unit.status === 'active' ?
                    '<span class="item-badge">✓ ใช้งาน</span>' :
                    '<span class="item-badge inactive">✕ ปิดใช้งาน</span>';

                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-semibold">${escapeHtml(unit.name)} ${unit.symbol ? '(' + escapeHtml(unit.symbol) + ')' : ''}</h6>
                            ${unit.description ? '<small class="text-muted d-block">' + escapeHtml(unit.description) + '</small>' : ''}
                        </div>
                        <div class="action-buttons">
                            ${statusBadge}
                            <button class="btn btn-sm btn-danger ms-2" onclick="deleteUnit(${unit.id})">
                                <i class="bi bi-trash"></i> ลบ
                            </button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('unitsList').innerHTML = html;
        }

        // function editCategory(id) {
        //     alert('ฟังก์ชันแก้ไขจะถูกเพิ่มเติมเร็ว ๆ นี้');
        // }

        function deleteCategory(id) {
            if (confirm('คุณแน่ใจหรือที่จะลบหมวดหมู่นี้?')) {
                fetch(STOCK_API + '/manage_categories.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'delete',
                        id: id
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            loadCategories();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('เกิดข้อผิดพลาด: ' + error.message);
                    });
            }
        }

        // function editUnit(id) {
        //     alert('ฟังก์ชันแก้ไขจะถูกเพิ่มเติมเร็ว ๆ นี้');
        // }

        function deleteUnit(id) {
            if (confirm('คุณแน่ใจหรือที่จะลบหน่วยนับนี้?')) {
                fetch(STOCK_API + '/manage_units.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'delete',
                        id: id
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            loadUnits();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('เกิดข้อผิดพลาด: ' + error.message);
                    });
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    </script>

</body>

</html>
