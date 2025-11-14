<?php
require_once '../../config.php';
$conn = connectDB();

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

$res = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id");

$html = "";

if ($res->num_rows > 0) {
    $html .= "<label class='form-label'>เลือกตัวเลือกสินค้า</label>";
    $html .= "<select name='variant_id' class='form-select mb-3'>";
    while ($v = $res->fetch_assoc()) {
        $html .= "<option value='{$v['id']}'>{$v['variant_name']} (สต็อก: {$v['stock']})</option>";
    }
    $html .= "</select>";
}

$html .= "
    <label class='form-label'>เพิ่มจำนวนสต็อก</label>
    <input type='number' name='add_stock' class='form-control' min='1' required>
";

echo $html;
?>