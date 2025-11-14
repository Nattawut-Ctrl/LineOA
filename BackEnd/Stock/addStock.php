<?php
session_start();
require_once '../../config.php';
$conn = connectDB();

// โหลดรายการสินค้าเดิมไว้สำหรับ dropdown
$products = [];
$res = $conn->query("SELECT id, name FROM products ORDER BY id DESC");
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เพิ่มสินค้า / เพิ่มสต็อก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .variant-row {
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-4">

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


        <h3 class="mb-3">📦 จัดการสินค้า</h3>

        <!-- ------------------------------ -->
        <!-- 1) ฟอร์มเพิ่มสต็อกจากสินค้าเดิม -->
        <!-- ------------------------------ -->
        <div class="card p-3 mb-4">
            <h5>➕ เพิ่มสต็อกจากสินค้าเดิม</h5>

            <form action="save_new_stock.php" method="POST">

                <div class="mb-3">
                    <label class="form-label">เลือกสินค้า</label>
                    <select name="product_id" id="productSelect" class="form-select" required>
                        <option value="">-- เลือก --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="variantArea"></div>

                <button class="btn btn-primary mt-3">เพิ่มสต็อก</button>
            </form>
        </div>


        <!-- --------------------------------- -->
        <!-- 2) ฟอร์มเพิ่มสินค้าใหม่ + variants -->
        <!-- --------------------------------- -->
        <div class="card p-3">
            <h5>🆕 เพิ่มสินค้าใหม่</h5>

            <form action="save_new_product.php" method="POST" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">ชื่อสินค้า</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label class="form-label">หมวดหมู่</label>
                        <input type="text" name="category" class="form-control" required>
                    </div>

                    <div class="mb-3 col-md-4">
                        <label class="form-label">ราคาเริ่มต้น</label>
                        <input type="number" name="price" step="0.01" class="form-control" required>
                    </div>

                    <div class="mb-3 col-md-4">
                        <label class="form-label">สต็อกเริ่มต้น</label>
                        <input type="number" name="stock" class="form-control" value="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">คำอธิบาย</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">รูปภาพสินค้า</label>
                    <input type="file" name="image" class="form-control">
                </div>

                <hr>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">ตัวเลือกสินค้า (Variants)</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addVariantBtn">
                        + เพิ่มตัวเลือก
                    </button>
                </div>

                <div id="variantsContainer"></div>

                <button class="btn btn-success mt-3">บันทึกสินค้าใหม่</button>
            </form>
        </div>

        <?php
        $productsList = $conn->query("
    SELECT p.*, 
        (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count
    FROM products p
    ORDER BY p.id DESC
");
        ?>

        <h3 class="mt-5">📦 รายการสินค้า</h3>

        <table class="table table-bordered bg-white mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>รูป</th>
                    <th>ชื่อสินค้า</th>
                    <th>ราคา</th>
                    <th>สต็อก</th>
                    <th>ตัวเลือก</th>
                    <th width="200">จัดการ</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($p = $productsList->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><img src="<?= $p['image'] ?>" width="60"></td>
                        <td><?= $p['name'] ?></td>
                        <td><?= $p['price'] ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><?= $p['variant_count'] ?> รายการ</td>

                        <td>

                            <!-- ปุ่มแก้ไข เปิด Modal -->
                            <button
                                class="btn btn-warning btn-sm editProductBtn"
                                data-id="<?= $p['id'] ?>">
                                แก้ไข
                            </button>

                            <!-- ปุ่มลบ -->
                            <button
                                class="btn btn-danger btn-sm deleteProductBtn"
                                data-id="<?= $p['id'] ?>">
                                ลบ
                            </button>

                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>


    </div>


    <script>
        // ---------------------------
        // โหลดตัวเลือก variant ของสินค้าเดิมเพื่อเพิ่มสต็อก
        // ---------------------------
        document.getElementById('productSelect').addEventListener('change', function() {
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


        // ---------------------------
        // เพิ่มตัวเลือกสินค้าใหม่
        // ---------------------------
        document.getElementById('addVariantBtn').addEventListener('click', () => {
            const container = document.getElementById('variantsContainer');

            const div = document.createElement('div');
            div.className = 'variant-row';

            div.innerHTML = `
        <div class="d-flex justify-content-between mb-2">
            <strong>ตัวเลือก</strong>
            <button type="button" class="btn btn-sm btn-danger removeVariant">ลบ</button>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>ชื่อ (เช่น สีแดง / ไซส์ M)</label>
                <input type="text" name="variant_name[]" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>ราคา</label>
                <input type="number" step="0.01" name="variant_price[]" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label>สต็อก</label>
                <input type="number" name="variant_stock[]" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <label>รูป</label>
                <input type="file" name="variant_image[]" class="form-control">
            </div>
        </div>
    `;

            container.appendChild(div);

            // ปุ่มลบตัวเลือก
            div.querySelector('.removeVariant').onclick = () => div.remove();
        });
    </script>

    <script>
        // รอหน้าโหลดครบก่อน
        document.addEventListener("DOMContentLoaded", function() {
            // ตั้งเวลา 3 วินาทีแล้วค่อยปิด alert
            setTimeout(function() {
                const alertList = document.querySelectorAll('.alert');

                alertList.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });

            }, 3000); // 3000 ms = 3 วินาที
        });
    </script>

    <!-- EDIT PRODUCT MODAL -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
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
        // เปิด modal สำหรับแก้ไขสินค้า
        document.querySelectorAll('.editProductBtn').forEach(btn => {
            btn.addEventListener('click', e => {
                let id = btn.dataset.id;

                fetch("ajax_load_product.php?id=" + id)
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById("editProductContent").innerHTML = html;

                        const myModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                        myModal.show();

                        // form update
                        document.getElementById("updateProductForm").onsubmit = function(ev) {
                            ev.preventDefault();

                            fetch("ajax_update_product.php", {
                                    method: "POST",
                                    body: new FormData(this)
                                }).then(res => res.text())
                                .then(result => {
                                    if (result === "success") {
                                        location.reload();
                                    }
                                });
                        };
                    });
            });
        });

        // ลบสินค้า
        document.querySelectorAll('.deleteProductBtn').forEach(btn => {
            btn.onclick = () => {
                if (!confirm("ลบสินค้านี้?")) return;

                fetch("ajax_delete_product.php", {
                        method: "POST",
                        body: new FormData(Object.assign(document.createElement('form'), {
                            innerHTML: `<input name="id" value="${btn.dataset.id}">`
                        }))
                    }).then(r => r.text())
                    .then(txt => {
                        if (txt === "success") location.reload();
                    });
            };
        });
    </script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

</body>

</html>