<?php
// ตัวอย่างข้อมูลสินค้า (ในกรณีจริงจะดึงจากฐานข้อมูล)
$products = [
    1 => ['title' => 'เสื้อผ้าผู้ชาย', 'price' => 299, 'image' => 'https://via.placeholder.com/150', 'sold' => 0, 'description' => 'เสื้อผ้าผู้ชายทรงสวย ใส่สบาย'],
    2 => ['title' => 'กระบอกน้ำสแตนเลส', 'price' => 150, 'image' => 'https://via.placeholder.com/150', 'sold' => 0, 'description' => 'กระบอกน้ำสแตนเลส ทนทาน ดีไซน์ทันสมัย']
];

// รับ `id` จาก URL
$productId = $_GET['id'] ?? null;
$product = $products[$productId] ?? null;

if ($product === null) {
    die("สินค้าไม่พบ");
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายละเอียดสินค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="img-fluid">
            </div>
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product['title']); ?></h1>
                <p class="text-muted">ราคาสินค้า: <?php echo number_format($product['price']); ?> ฿</p>
                <p class="text-muted"><?php echo $product['sold']; ?> ขายแล้ว</p>
                <p><?php echo $product['description']; ?></p>

                <button class="btn btn-primary btn-lg">ซื้อเลย</button>
                <button class="btn btn-outline-secondary btn-lg mt-3">เพิ่มลงตะกร้า</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
