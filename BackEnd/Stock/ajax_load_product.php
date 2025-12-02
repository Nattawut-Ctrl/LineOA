<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/product_image_helper.php';

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

$sku  = isset($p['sku'])  ? (string)$p['sku']  : '';
$unit = isset($p['unit']) ? (string)$p['unit'] : '';

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

<form id="updateProductForm" method="post" action="ajax_update_product.php" enctype="multipart/form-data">

    <input type="hidden" name="id" value="<?= $p['id'] ?>">

    <div class="mb-3">
        <label>ชื่อสินค้า</label>
        <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" class="form-control">
    </div>

    <div class="row">
        <!-- <div class="col-md-6 mb-3">
            <label>SKU</label>
            <input type="text" name="sku"
                value="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>"
                class="form-control">
        </div> -->
        <div class="col-md-6 mb-3">
            <label>หน่วยนับ (Unit)</label>
            <input type="text" name="unit"
                value="<?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?>"
                class="form-control">
        </div>
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
        <?php while ($v = $resV->fetch_assoc()):
            $vid    = (int)$v['id'];
            $imgUrl = buildImageUrlFromPath($v['image'] ?? '');
        ?>
            <div class="variant-row mb-2">
                <input type="hidden" name="variant_id[]" value="<?= $vid ?>">

                <div class="row">
                    <!-- รูป variant (ดู + เปลี่ยนได้) -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">รูปตัวเลือก</label>
                        <?php if (!empty($imgUrl)): ?>
                            <div class="mb-1">
                                <img src="<?= htmlspecialchars($imgUrl) ?>"
                                    class="img-thumbnail"
                                    style="width:80px;height:80px;object-fit:cover;">
                            </div>
                            <small class="text-muted d-block mb-1">
                                เลือกรูปใหม่เพื่อแทนที่รูปเดิม
                            </small>
                        <?php else: ?>
                            <div class="text-muted small mb-1">
                                ยังไม่มีรูป เลือกรูปเพื่อเพิ่มได้
                            </div>
                        <?php endif; ?>

                        <!-- เปลี่ยนรูป (ถ้าไม่เลือก รูปจะไม่เปลี่ยน) -->
                        <input type="file"
                            name="variant_image[<?= $vid ?>]"
                            class="form-control form-control-sm"
                            accept="image/*">
                    </div>

                    <!-- ข้อมูลอื่น ๆ ของ variant -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">ชื่อ</label>
                        <input type="text"
                            name="variant_name[]"
                            value="<?= htmlspecialchars($v['variant_name']) ?>"
                            class="form-control">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label">ราคา</label>
                        <input type="number"
                            step="0.01"
                            name="variant_price[]"
                            value="<?= $v['price'] ?>"
                            class="form-control">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label">สต็อก</label>
                        <input type="number"
                            name="variant_stock[]"
                            value="<?= $v['stock'] ?>"
                            class="form-control">
                    </div>

                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="button"
                            class="btn btn-sm btn-outline-danger deleteVariantBtn w-100"
                            data-id="<?= $vid ?>">
                            <i class="bi bi-trash"></i> ลบตัวเลือก
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