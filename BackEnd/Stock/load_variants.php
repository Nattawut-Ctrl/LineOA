<?php
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';

// ใช้ตัวเชื่อมต่อที่มี log
$conn = connectDBWithLog();

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// ถ้าไม่ได้ส่ง product_id มา หรือเป็น 0 → ไม่ต้อง query
if ($product_id <= 0) {
    echo "";
    exit;
}

// ดึง variants พร้อม log อัตโนมัติ
$res = db_query(
    $conn,
    "SELECT id, variant_name, stock 
     FROM product_variants 
     WHERE product_id = ?",
    [$product_id],
    "i"
);

$html = "";

if ($res && $res->num_rows > 0) {
    $html .= "<label class='form-label'>เลือกตัวเลือกสินค้า</label>";
    $html .= "<select name='variant_id' class='form-select mb-3'>";
    while ($v = $res->fetch_assoc()) {
        $id   = (int)$v['id'];
        $name = htmlspecialchars($v['variant_name']);
        $stock = (int)$v['stock'];

        $html .= "<option value='{$id}'>{$name} (สต็อก: {$stock})</option>";
    }
    $html .= "</select>";
}

// ช่องกรอกจำนวนสต็อกเพิ่ม
$html .= "
    <label class='form-label'>เพิ่มจำนวนสต็อก</label>
    <input type='number' name='add_stock' class='form-control' min='1' required>
";

echo $html;
