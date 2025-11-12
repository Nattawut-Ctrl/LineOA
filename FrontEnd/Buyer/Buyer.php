<?php
session_start();

// ถ้าไม่มี user_id แปลว่ายังไม่ login จาก LINE
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Users/line-entry.php");   // ให้กลับไปเริ่มที่ LIFF อีกครั้ง
    exit;
}

// ถ้าอยากดึงข้อมูล user จาก DB ด้วย
require_once '../../config.php';
$conn = connectDB();
$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ตัวอย่างรายการสินค้า
$products = [
    [
        'name' => 'เสื้อ',
        'price' => '250 บาท',
        'image' => '../../uploads/shirt1.png',
        'description' => 'เสื้อยืดไม่ย้วย',
        'category' => 'เสื้อ'
    ],
    [
        'name' => 'กระบอกน้ำ',
        'price' => '500 บาท',
        'image' => '../../uploads/bottle1.png',
        'description' => 'กระบอกน้ำเก็บความเย็นกันความร้อน ทนทาน ตกน้ำไม่ไหล ตกไฟไม่ไหม้',
        'category' => 'กระบอกน้ำ'
    ],
];

// จากนี้ค่อยเป็น HTML เดิมของ Buyer.php
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยินดีต้อนรับ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="#">Line-Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">สินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#">บัญชีของฉัน</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="../Users/line-entry.php">ออกจากระบบ</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero bg-primary text-white text-center py-5">
        <h1 class="display-4">ยินดีต้อนรับกลับ!</h1>
        <p class="lead">สวัสดีคุณ <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
    </section>

    <!-- หมวดหมู่สินค้า -->
    <section class="container my-4">
        <div class="text-center">
            <h2>เลือกหมวดหมู่สินค้า</h2>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-secondary" id="categoryAll">ทั้งหมด</button>
                <button type="button" class="btn btn-secondary" id="categoryShirt">เสื้อ</button>
                <button type="button" class="btn btn-secondary" id="categoryBottle">กระบอกน้ำ</button>
            </div>
        </div>
    </section>

    <!-- Product Listing -->
    <section class="container my-5" id="product-list">
        <h2 class="text-center mb-4">สินค้าแนะนำ</h2>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col product-card" data-category="<?php echo $product['category']; ?>">
                    <div class="card d-flex flex-column h-100">
                        <img src="<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo $product['name']; ?></h5>
                            <p class="card-text"><?php echo $product['description']; ?></p>
                            <div class="mt-auto">
                                <p class="h5 "><?php echo $product['price']; ?></p>
                                <a href="#" class="btn btn-primary">ซื้อเลย</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer -->
    <!-- <footer class="bg-primary text-white text-center py-3">

    </footer> -->

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <script>
        // ฟังก์ชันการกรองสินค้าโดยหมวดหมู่
        document.getElementById("categoryAll").addEventListener("click", function() {
            filterProducts("all");
        });

        document.getElementById("categoryShirt").addEventListener("click", function() {
            filterProducts("เสื้อ");
        });

        document.getElementById("categoryBottle").addEventListener("click", function() {
            filterProducts("กระบอกน้ำ");
        });

        // ฟังก์ชันกรองสินค้าตามหมวดหมู่
        function filterProducts(category) {
            var cards = document.querySelectorAll(".product-card");
            cards.forEach(function(card) {
                if (category === "all") {
                    card.style.display = "block";
                } else {
                    if (card.getAttribute("data-category") === category) {
                        card.style.display = "block";
                    } else {
                        card.style.display = "none";
                    }
                }
            });
        }
    </script>
</body>

</html>