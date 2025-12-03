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
    "SELECT id, name, stock, image FROM products WHERE id = ?",
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
    "SELECT id, variant_name, price, stock, image
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

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:80px;">รูป</th>
                    <th>ตัวเลือก</th>
                    <th style="width:120px;">สต็อกปัจจุบัน</th>
                    <th style="width:130px;">เพิ่มสต็อก</th>
                    <!-- <th style="width:260px;">จัดการรูป</th> -->
                </tr>
            </thead>
            <tbody>
                <?php while ($v = $resVar->fetch_assoc()): 
                    $vid    = (int)$v['id'];
                    $vName  = $v['variant_name'] ?: ('ตัวเลือก #' . $vid);
                    $vStock = (int)$v['stock'];
                    $imgUrl = buildImageUrlFromPath($v['image'] ?? '');
                ?>
                    <tr>
                        <td>
                            <?php if (!empty($imgUrl)): ?>
                                <img src="<?= htmlspecialchars($imgUrl) ?>"
                                     class="img-thumbnail"
                                     style="width:60px;height:60px;object-fit:cover;">
                            <?php else: ?>
                                <span class="text-muted small">ไม่มีรูป</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($vName) ?></div>
                            <?php if ($v['price'] !== null): ?>
                                <div class="text-muted small">
                                    ราคา: <?= number_format((float)$v['price'], 2) ?> บาท
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle border border-secondary text-secondary">
                                <?= $vStock ?> ชิ้น
                            </span>
                        </td>
                        <td>
                            <input type="number"
                                   name="add_stock[<?= $vid ?>]"
                                   class="form-control form-control-sm"
                                   min="0"
                                   value="0">
                        </td>
                        <!-- <td>
                            <div class="small text-muted mb-1">
                                อัปโหลดรูปใหม่ (ถ้าต้องการเปลี่ยน)
                            </div>
                            <input type="file"
                                   name="variant_image[<?= $vid ?>]"
                                   class="form-control form-control-sm mb-1"
                                   accept="image/*">

                            <?php if (!empty($v['image'])): ?>
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="variant_image_delete[]"
                                           value="<?= $vid ?>"
                                           id="delImg<?= $vid ?>">
                                    <label class="form-check-label small" for="delImg<?= $vid ?>">
                                        ลบรูปเดิมออก
                                    </label>
                                </div>
                            <?php endif; ?>
                        </td> -->
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<?php
// ถ้าไม่มี variants → ให้เพิ่มสต็อกสินค้าหลักตรง ๆ
else: ?>

    <div class="border rounded-3 p-3 bg-light-subtle">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="fw-semibold mb-1">
                    เพิ่มสต็อกสินค้าหลัก
                </div>
                <div class="text-muted small">
                    สินค้านี้ไม่มีตัวเลือกย่อย ระบบจะเพิ่มสต็อกให้กับสินค้าโดยตรง
                </div>
            </div>
            <div class="text-end">
                <div class="small text-muted mb-1">สต็อกปัจจุบัน</div>
                <span class="badge bg-secondary-subtle border border-secondary text-secondary">
                    <?= (int)$product['stock'] ?> ชิ้น
                </span>
            </div>
        </div>

        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <label class="form-label fw-semibold">จำนวนที่ต้องการเพิ่ม</label>
                <input type="number"
                       name="product_add_stock"
                       class="form-control"
                       min="1"
                       value="1">
            </div>
        </div>
    </div>

<?php endif; ?>
