<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

// รับ id แบบปลอดภัย
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    exit("<p class='text-danger'>ไม่พบสินค้า</p>");
}

// ----------------------------------------
// โหลดข้อมูลสินค้า (log อัตโนมัติ)
// ----------------------------------------
$resP = db_query(
    $conn,
    "SELECT * FROM products WHERE id = ?",
    [$id],
    "i"
);

$p = $resP ? $resP->fetch_assoc() : null;

if (!$p) {
    exit("<p class='text-danger'>ไม่พบสินค้า</p>");
}

// ----------------------------------------
// โหลดตัวเลือกสินค้า (variants)
// ----------------------------------------
$resV = db_query(
    $conn,
    "SELECT * FROM product_variants WHERE product_id = ?",
    [$id],
    "i"
);
?>

<form id="updateProductForm">

    <input type="hidden" name="id" value="<?= $p['id'] ?>">

    <div class="mb-3">
        <label>ชื่อสินค้า</label>
        <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" class="form-control">
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label>ราคา</label>
            <input type="number" name="price" value="<?= $p['price'] ?>" class="form-control">
        </div>

        <div class="col-md-4 mb-3">
            <label>สต็อก</label>
            <input type="number" name="stock" value="<?= $p['stock'] ?>" class="form-control">
        </div>
    </div>

    <div class="mb-3">
        <label>คำอธิบาย</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars($p['description']) ?></textarea>
    </div>

    <hr>
    <h5>ตัวเลือกสินค้า (Variants)</h5>

    <?php if ($resV->num_rows > 0): ?>
        <?php while ($row = $resV->fetch_assoc()): ?>
            <div class="border rounded p-2 mb-2">
                <input type="hidden" name="variant_id[]" value="<?= $row['id'] ?>">

                <div class="row">
                    <div class="col-md-4">
                        <label>ชื่อ</label>
                        <input type="text" name="variant_name[]" value="<?= htmlspecialchars($row['variant_name']) ?>" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label>ราคา</label>
                        <input type="number" name="variant_price[]" value="<?= $row['price'] ?>" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label>สต็อก</label>
                        <input type="number" name="variant_stock[]" value="<?= $row['stock'] ?>" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label>ลบ</label>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm w-100 deleteVariantBtn"
                            data-id="<?= $row['id'] ?>">
                            ลบ
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted small mb-2">ยังไม่มีตัวเลือกสินค้า</p>
    <?php endif; ?>

    <hr class="my-3">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">เพิ่มตัวเลือกสินค้าใหม่</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addNewVariantInEdit">
            <i class="bi bi-plus-circle"></i> เพิ่มตัวเลือกใหม่
        </button>
    </div>
    <p class="small text-muted mb-2">
        ใช้เมื่อต้องการเพิ่มสี / ไซส์ใหม่ให้สินค้านี้
    </p>

    <div id="newVariantContainer"></div>

    <button type="submit" class="btn btn-primary mt-3">บันทึกการแก้ไข</button>

</form>