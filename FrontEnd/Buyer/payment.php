<?php
session_start();

// ป้องกันคนที่ยังไม่ได้ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Users/line-entry.php");
    exit;
}

// ดึงค่าจากหน้าก่อน (GET)
$product_id = $_GET['product_id'] ?? null;
$variant_id = $_GET['variant_id'] ?? null;
$variant_name = $_GET['variant_name'] ?? null;
$product_name = $_GET['product_name'] ?? '';
$quantity = (int)($_GET['quantity'] ?? 1);
$price = (float)($_GET['price'] ?? 0);
$total = $quantity * $price;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน | Line-Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<!-- แถบเมนูหัวข้อ -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">Line-Shop</a>
    </div>
</nav>

<!-- ส่วนของเนื้อหา -->
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h4 class="text-center mb-4">💳 ชำระเงิน</h4>

            <!-- รายละเอียดสินค้า -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered text-center align-middle">
                    <tr><th>สินค้า</th><td><?= htmlspecialchars($product_name) ?></td></tr>
                    <tr><th>จำนวน</th><td><?= $quantity ?> ชิ้น</td></tr>
                    <tr><th>ราคาต่อชิ้น</th><td><?= number_format($price, 2) ?> บาท</td></tr>
                    <tr class="table-warning"><th>รวมทั้งหมด</th><td><strong><?= number_format($total, 2) ?> บาท</strong></td></tr>
                </table>
            </div>

            <!-- QR Code -->
            <div class="text-center mb-4">
                <h6 class="fw-bold mb-2">📱 สแกนเพื่อชำระเงิน</h6>
                <img src="../../uploads/qr-payment.png" class="img-fluid rounded border" style="max-width:220px;" alt="QR Payment">
                <p class="text-muted small mt-2">กรุณาชำระเงินและอัปโหลดสลิปด้านล่าง</p>
            </div>

            <!-- ฟอร์มอัปโหลดสลิป -->
            <form action="upload_slip.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="product_name" value="<?= htmlspecialchars($product_name) ?>">
                <input type="hidden" name="quantity" value="<?= $quantity ?>">
                <input type="hidden" name="price" value="<?= $price ?>">
                <input type="hidden" name="total" value="<?= $total ?>">

                <div class="mb-3">
                    <label for="slipInput" class="form-label fw-semibold">📤 อัปโหลดสลิปการโอนเงิน</label>
                    <input type="file" class="form-control" id="slipInput" name="slip" accept="image/*" required onchange="previewSlip(event)">
                    <img id="preview" class="img-fluid mt-3 rounded d-none border" style="max-width: 300px;">
                </div>

                <div class="mb-3">
                    <label for="paymentTime" class="form-label fw-semibold">⏰ เวลาที่โอนเงิน</label>
                    <input type="datetime-local" id="paymentTime" name="payment_time" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-danger w-100 py-2">ยืนยันการชำระเงิน</button>
            </form>
        </div>
    </div>
</div>

<script>
function previewSlip(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('preview');
    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }
}
</script>

</body>
</html>
