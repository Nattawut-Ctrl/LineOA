<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
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

$productImageUrl = buildImageUrlFromPath($p['image'] ?? '');

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
        <div class="col-md-6 mb-3">
            <label>หน่วยนับ</label>
            <input type="text" name="unit"
                value="<?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?>"
                class="form-control">
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label>ราคา</label>
            <input type="number" name="price" value="<?= $p['price'] ?>" class="form-control" readonly>
            <!-- <input type="hidden" name="price" value="<?= htmlspecialchars($p['price']) ?>"> -->
        </div>

        <?php
        // ค่า default จาก products ก่อน
        $stockTotal = (int)($p['stock'] ?? 0);
        $reserved   = (int)($p['reserved_stock'] ?? 0);

        // ถ้ามีตัวเลือกสินค้า ให้ใช้ยอดรวมจากตาราง product_variants แทน
        $resSum = db_query(
            $conn,
            "SELECT 
                 COALESCE(SUM(stock), 0) AS stock_sum,
                 COALESCE(SUM(reserved_stock), 0) AS reserved_sum
             FROM product_variants
             WHERE product_id = ?",
            [$id],
            "i"
        );

        if ($resSum && $rowSum = $resSum->fetch_assoc()) {
            // ถ้ามีอย่างน้อย 1 ตัวเลือก ให้ override ด้วยค่าจาก variants
            if ($rowSum['stock_sum'] !== null) {
                $stockTotal = (int)$rowSum['stock_sum'];
                $reserved   = (int)$rowSum['reserved_sum'];
            }
        }

        $available = max(0, $stockTotal - $reserved);
        ?>
        <div class="col-md-8 mb-3">
            <label>สต็อก</label>
            <div class="p-2 border rounded bg-light">
                <div>คงเหลือขายได้: <strong><?= $available ?></strong></div>
                <small class="text-muted">
                    จองไว้: <?= $reserved ?> | สต็อกรวม: <?= $stockTotal ?>
                </small>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label>คำอธิบาย</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars($p['description']) ?></textarea>
    </div>

    <!-- รูปหลักของสินค้า -->
    <hr>
    <h5>รูปสินค้าหลัก</h5>

    <div class="row mb-3">
        <div class="col-md-4">
            <?php if (!empty($productImageUrl)): ?>
                <div class="mb-2">
                    <img src="<?= htmlspecialchars($productImageUrl) ?>"
                        class="img-thumbnail"
                        style="width:120px;height:120px;object-fit:cover;">
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="product_image_delete" id="product_image_delete" value="1">
                    <label class="form-check-label" for="product_image_delete">
                        ลบรูปปัจจุบัน (ถ้าไม่เลือก รูปจะยังอยู่)
                    </label>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-2">ยังไม่มีรูปหลัก</p>
            <?php endif; ?>

            <label class="form-label">เลือกรูปใหม่ (ถ้าต้องการเปลี่ยน)</label>
            <input type="file" name="product_image" class="form-control form-control-sm" accept="image/*">
            <small class="text-muted d-block mt-1">ถ้าไม่เลือก รูปเดิมจะยังคงอยู่ (ถ้าไม่ได้ติ๊ก "ลบรูป")</small>
        </div>
    </div>
    <!-- รูปเพิ่มเติม -->
    <hr>
    <h5>รูปสินค้าเพิ่มเติม</h5>
    <div class="mb-3">
        <div id="productImageDropzone"
            class="dropzone"
            data-product-id="<?= (int)$p['id'] ?>">
        </div>
        <small class="text-muted d-block mt-1">
            ลากรูปมาวาง หรือคลิกเพื่อเลือก สามารถเพิ่มได้หลายรูป
        </small>
    </div>

    <hr>
    <h5>ตัวเลือกสินค้า</h5>

    <?php if ($resV->num_rows > 0): ?>
        <?php while ($v = $resV->fetch_assoc()):
            $vid    = (int)$v['id'];
            $imgUrl = buildImageUrlFromPath($v['image'] ?? '');

            $stockTotal = (int)$v['stock'];
            $reserved   = (int)($v['reserved_stock'] ?? 0);
            $available  = max(0, $stockTotal - $reserved);
        ?>
            <div class="variant-row mb-3 border rounded-3 p-2">
                <input type="hidden" name="variant_id[]" value="<?= $vid ?>">

                <div class="row g-3">
                    <!-- รูป variant -->
                    <div class="col-md-3">
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

                        <input type="file"
                            name="variant_image[<?= $vid ?>]"
                            class="form-control form-control-sm"
                            accept="image/*">
                    </div>
                    <!-- ชื่อ / รหัสสินค้า -->
                    <div class="col-md-3">
                        <label class="form-label">ชื่อ</label>
                        <input type="text"
                            name="variant_name[]"
                            value="<?= htmlspecialchars($v['variant_name']) ?>"
                            class="form-control mb-2">
                        <label class="form-label mb-1">รหัสสินค้า</label>
                        <input type="text"
                            name="variant_sku[]"
                            value="<?= htmlspecialchars($v['sku']) ?>"
                            class="form-control">
                    </div>
                    <!-- ราคา -->
                    <div class="col-md-2">
                        <label class="form-label">ราคา</label>
                        <input type="number"
                            step="0.01"
                            name="variant_price[]"
                            value="<?= $v['price'] ?>"
                            class="form-control" 
                            readonly>
                    </div>

                    <!-- สต๊อก + ปุ่มลบ -->
                    <div class="col-md-4">
                        <label class="form-label">สต็อก</label>
                        <div class="p-2 border rounded bg-light mb-2">
                            <div>คงเหลือขายได้: <strong><?= $available ?></strong></div>
                            <small class="text-muted">
                                จองไว้: <?= $reserved ?> | สต็อกทั้งหมด: <?= $stockTotal ?>
                            </small>
                        </div>
                        <!-- <label class="form-label small mb-1">แก้ไขสต็อกรวม</label> -->
                        <div class="d-flex gap-2">
                            <!-- <input type="number"
                                name="variant_stock[]"
                                value="<?= $stockTotal ?>"
                                class="form-control"> -->
                            <button type="button"
                                class="btn btn-sm btn-outline-danger deleteVariantBtn"
                                data-id="<?= $vid ?>">
                                <i class="bi bi-trash"></i> ลบตัวเลือกสินค้านี้
                            </button>
                        </div>
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

    <button type="submit" class="btn btn-primary mt-3" id="btnUpdateProduct">บันทึกการแก้ไข</button>

</form>