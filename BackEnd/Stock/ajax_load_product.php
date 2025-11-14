<?php
require_once '../../config.php';
$conn = connectDB();

$id = intval($_GET['id']);
$p = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
$v = $conn->query("SELECT * FROM product_variants WHERE product_id = $id");
?>

<form id="updateProductForm">

<input type="hidden" name="id" value="<?= $p['id'] ?>">

<div class="mb-3">
    <label>ชื่อสินค้า</label>
    <input type="text" name="name" value="<?= $p['name'] ?>" class="form-control">
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
    <textarea name="description" class="form-control"><?= $p['description'] ?></textarea>
</div>

<hr>
<h5>ตัวเลือกสินค้า (Variants)</h5>

<?php while ($row = $v->fetch_assoc()): ?>
<div class="border rounded p-2 mb-2">
    <input type="hidden" name="variant_id[]" value="<?= $row['id'] ?>">

    <div class="row">
        <div class="col-md-4">
            <label>ชื่อ</label>
            <input type="text" name="variant_name[]" value="<?= $row['variant_name'] ?>" class="form-control">
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

<button type="submit" class="btn btn-primary mt-3">บันทึกการแก้ไข</button>

</form>
