<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/product_image_helper.php';

require_admin();
$conn = connectDBWithLog();

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    echo '<div class="text-muted">กรุณาเลือกสินค้า</div>';
    exit;
}

// ดึงข้อมูลสินค้า
$resProduct = db_query(
    $conn,
    "SELECT id, name, stock, reserved_stock, image FROM products WHERE id = ?",
    [$product_id],
    "i"
);

if (!$resProduct || $resProduct->num_rows === 0) {
    echo '<div class="text-danger">ไม่พบสินค้า</div>';
    exit;
}

$product = $resProduct->fetch_assoc();

// ดึงตัวเลือกสินค้า (variants)
$resVar = db_query(
    $conn,
    "SELECT id, variant_name, price, stock, reserved_stock, image
     FROM product_variants
     WHERE product_id = ?
     ORDER BY id ASC",
    [$product_id],
    "i"
);

// ถ้ามี variants → แสดงรายการตัวเลือก
if ($resVar && $resVar->num_rows > 0): ?>

    <div class="mb-2">
        <div class="fw-semibold mb-1">
            เพิ่มสต็อกให้ตัวเลือกสินค้า
        </div>
        <small class="text-muted">
            ระบุจำนวนที่ต้องการเพิ่มในแต่ละตัวเลือก สามารถเปลี่ยนรูป / ลบรูปตัวเลือกได้
        </small>
    </div>

    <?php while ($v = $resVar->fetch_assoc()):
        $vid         = (int)$v['id'];
        $variantName = $v['variant_name'] ?? '—';
        $price       = (float)($v['price'] ?? 0);
        $vStock      = (int)($v['stock'] ?? 0);
        $reserved    = (int)($v['reserved_stock'] ?? 0);
        $available   = max(0, $vStock - $reserved);

        $imgUrl = buildImageUrlFromPath($v['image'] ?? '');
    ?>
        <div class="border rounded-3 p-3 mb-3 d-flex align-items-start gap-3 bg-white">
            <!-- รูปตัวเลือก -->
            <div class="flex-shrink-0" style="width:80px;">
                <?php if ($imgUrl): ?>
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                        alt="<?php echo htmlspecialchars($variantName); ?>"
                        class="img-fluid rounded-3 border">
                <?php else: ?>
                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center"
                        style="width:80px;height:80px;">
                        <span class="text-muted small">ไม่มีรูป</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ข้อมูลตัวเลือก -->
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1">
                    <?php echo htmlspecialchars($variantName); ?>
                </div>
                <div class="small text-muted mb-2">
                    ราคา: ฿<?php echo number_format($price, 2); ?>
                </div>
                <div class="small">
                    คงเหลือขายได้:
                    <span class="fw-bold text-success"><?php echo $available; ?></span><br>
                    จองไว้:
                    <span class="fw-semibold"><?php echo $reserved; ?></span>
                    |
                    สต็อกรวม:
                    <span class="fw-semibold"><?php echo $vStock; ?></span>
                </div>
            </div>

            <!-- ช่องเพิ่มสต๊อก -->
            <div class="flex-shrink-0" style="width:130px;">
                <label class="small text-muted mb-1 d-block">เพิ่มสต็อก</label>
                <input type="number"
                    name="variant_stock[<?php echo $vid; ?>]"
                    min="0"
                    class="form-control form-control-sm text-end"
                    value="0">
            </div>
        </div>
    <?php endwhile; ?>

    <input type="hidden" name="product_has_variants" value="1">

<?php
// ถ้าไม่มี variants เลย → ให้ไปใช้สต็อกของ product หลัก
else: ?>

    <?php
    $total_stock = (int)($product['stock'] ?? 0);
    $reserved    = (int)($product['reserved_stock'] ?? 0);
    $available   = max(0, $total_stock - $reserved);
    ?>
    <div class="alert alert-info small mb-2">
        สินค้านี้ยังไม่มีตัวเลือก (variant) <br>
        ระบบจะใช้สต็อกจากสินค้าหลักโดยตรง
    </div>

    <div class="border rounded-3 p-3 mb-2 bg-light">
        <div class="small text-muted">
            คงเหลือขายได้:
            <span class="fw-bold text-success"><?php echo $available; ?></span><br>
            จองไว้:
            <span class="fw-semibold"><?php echo $reserved; ?></span>
            |
            สต็อกรวม:
            <span class="fw-semibold"><?php echo $total_stock; ?></span>
        </div>
    </div>

    <div class="mb-2">
        <label class="small mb-1">เพิ่มสต็อก (สินค้าหลัก)</label>
        <input type="number"
            name="product_stock"
            min="0"
            class="form-control form-control-sm text-end"
            value="0">
    </div>

    <input type="hidden" name="product_has_variants" value="0">

<?php endif; ?>