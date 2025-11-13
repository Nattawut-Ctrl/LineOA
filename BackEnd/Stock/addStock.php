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

        <form action="save_new_stock.php" method="POST" enctype="multipart/form-data">

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

</div>


<script>
// ---------------------------
// โหลดตัวเลือก variant ของสินค้าเดิมเพื่อเพิ่มสต็อก
// ---------------------------
document.getElementById('productSelect').addEventListener('change', function () {
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

</body>
</html>
