<?php
session_start();

// ตรวจสอบว่าเข้าสู่ระบบแล้วหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Users/line-entry.php");
    exit;
}

require_once '../../config.php';
$conn = connectDB();
$user_id = (int)$_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// รายการสินค้าตัวอย่าง
$products = [
    [
        'name' => 'เสื้อ',
        'price' => '250 บาท',
        'image' => '../../uploads/shirt1.png',
        'description' => 'เสื้อยืดไม่ย้วย',
        'category' => 'เสื้อ',
        'stock' => 10
    ],
    [
        'name' => 'กระบอกน้ำ',
        'price' => '500 บาท',
        'image' => '../../uploads/bottle1.png',
        'description' => 'กระบอกน้ำเก็บความเย็นกันความร้อน ทนทาน ตกน้ำไม่ไหล ตกไฟไม่ไหม้',
        'category' => 'กระบอกน้ำ',
        'stock' => 15
    ],
];
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยินดีต้อนรับ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        /* เพิ่มสไตล์สำหรับแถบบาร์ */
        .cart-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            display: none; /* ซ่อนแถบบาร์ไว้ */
            z-index: 1050;
            padding: 15px;
            transition: transform 0.3s ease-in-out;
        }

        .cart-bar.show {
            display: block;
            transform: translateY(0);
        }

        .cart-bar .btn-close {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="#">Line-Shop</a>
            <div class="collapse navbar-collapse">
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
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero bg-primary text-white text-center py-5">
        <h1 class="display-4">ยินดีต้อนรับกลับ!</h1>
        <p class="lead">สวัสดีคุณ <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
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
                            <!-- เปลี่ยนจากปุ่มเป็นการเปิดแถบบาร์ -->
                            <button class="btn btn-primary w-100 open-cart-bar" data-product='<?php echo json_encode($product); ?>'>ซื้อเลย</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Cart Bar -->
    <div class="cart-bar">
        <button onclick="closeCartBar()">&times;</button>
        <h5>เลือกจำนวนสินค้า</h5>
        <div class="form-group mb-3">
            <label for="quantity">จำนวน:</label>
            <input type="number" id="quantity" class="form-control" value="1" min="1" max="10">
        </div>
        <h5>เลือกชนิดสินค้า</h5>
        <select id="product-type" class="form-select">
            <!-- แสดงประเภทสินค้าจากการเลือก -->
        </select>
        <button class="btn btn-success mt-3 w-100" onclick="confirmPurchase()">ยืนยันการซื้อ</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <script>
        // เปิดแถบบาร์
        document.querySelectorAll('.open-cart-bar').forEach(function(button) {
            button.addEventListener('click', function() {
                const product = JSON.parse(button.getAttribute('data-product'));
                openCartBar(product);
            });
        });

        function openCartBar(product) {
            // แสดงแถบบาร์
            document.querySelector('.cart-bar').classList.add('show');
            document.querySelector('#quantity').value = 1;
            document.querySelector('#product-type').innerHTML = `<option value="${product.category}">${product.category}</option>`;
            // เก็บข้อมูลสินค้าที่เลือก
            window.selectedProduct = product;
        }

        // ปิดแถบบาร์
        function closeCartBar() {
            document.querySelector('.cart-bar').classList.remove('show');
        }

        // ยืนยันการซื้อและส่งข้อมูลไปหน้า payment.php
        function confirmPurchase() {
            const quantity = document.querySelector('#quantity').value;
            const productType = document.querySelector('#product-type').value;

            // ส่งข้อมูลไปที่ payment.php
            const product = window.selectedProduct;
            const url = new URL('payment.php', window.location.origin);
            const params = new URLSearchParams({
                product_id: product.name, // ส่งชื่อสินค้าหรือ id ของสินค้าก็ได้
                quantity: quantity,
                price: product.price,
                category: product.category,
            });

            window.location.href = url + '?' + params.toString();
        }
    </script>

</body>

</html>
