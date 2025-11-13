<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Users/line-entry.php");
    exit;
}

require_once '../../config.php';
$conn = connectDB();
$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$products = [
    [
        'name' => 'เสื้อยืดลายมินิมอล',
        'price' => 250,
        'image' => '../../uploads/shirt1.png',
        'description' => 'ผ้าคอตตอน 100% ใส่สบาย ไม่ย้วย',
        'category' => 'เสื้อ',
        'stock' => 10
    ],
    [
        'name' => 'กระบอกน้ำเก็บความเย็น',
        'price' => 500,
        'image' => '../../uploads/bottle1.png',
        'description' => 'เก็บอุณหภูมิได้นาน 8 ชม. กันรั่ว 100%',
        'category' => 'กระบอกน้ำ',
        'stock' => 15
    ],
];

$categories = ['ทั้งหมด', 'เสื้อ', 'กระบอกน้ำ'];
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line-Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #ee4d2d;
        }

        .navbar-brand {
            font-weight: bold;
        }

        /* สินค้า */
        .product-card {
            transition: all 0.2s ease-in-out;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .price {
            color: #ee4d2d;
            font-weight: bold;
            font-size: 1.1rem;
        }

        /* Cart popup */
        .cart-bar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -100%;
            background: #fff;
            box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.15);
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            z-index: 1050;
            transition: bottom 0.3s ease-in-out;
            padding: 20px;
        }

        .cart-bar.show {
            bottom: 0;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .quantity-control button {
            width: 35px;
            height: 35px;
            font-size: 20px;
            line-height: 20px;
        }

        .btn-buy {
            background-color: #ee4d2d;
            border: none;
        }

        .btn-buy:hover {
            background-color: #d7381c;
        }

        /* หมวดหมู่สินค้า */
        .category-bar {
            background: white;
            border-bottom: 1px solid #ddd;
        }

        .category-item {
            color: #333;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        .category-item:hover,
        .category-item.active {
            background: #ee4d2d;
            color: white;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand text-white" href="#">🛍️ Line-Shop</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a href="#" class="nav-link text-white">หน้าแรก</a></li>
                <li class="nav-item"><a href="#" class="nav-link text-white">สินค้า</a></li>
                <li class="nav-item"><a href="#" class="nav-link text-white">บัญชีของฉัน</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero -->
    <div class="py-4 text-center bg-light">
        <h3>สวัสดีคุณ <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> 👋</h3>
        <p class="text-muted">เลือกช้อปสินค้าได้เลย!</p>
    </div>

    <!-- Category Bar -->
    <div class="category-bar py-2">
        <div class="container">
            <!-- Mobile button -->
            <div class="d-md-none text-center mb-2">
                <button class="btn btn-outline-dark btn-sm" data-bs-toggle="collapse" data-bs-target="#categoryCollapse">
                    หมวดหมู่ ▾
                </button>
            </div>

            <!-- Category list -->
            <div id="categoryCollapse" class="collapse d-md-block text-center">
                <?php foreach ($categories as $cat): ?>
                    <a href="#" class="category-item mx-1" data-category="<?php echo $cat; ?>"><?php echo $cat; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <!-- Search Bar -->
    <form class="d-flex" role="search" onsubmit="searchCategory(event)">
        <div class="input-group">
            <span class="input-group-text" id="search-icon">
                <i class="bi bi-search"></i> <!-- ใช้ไอคอนของ Bootstrap Icons -->
            </span>
            <input class="form-control" type="search" placeholder="ค้นหา..." aria-label="Search" id="searchInput">
            <button class="btn btn-outline-dark" type="submit">ค้นหา</button>
        </div>
    </form>


    <!-- สินค้า -->
    <div class="container my-4">
        <div class="row g-4" id="product-list">
            <?php foreach ($products as $product): ?>
                <div class="col-6 col-md-3 product-item" data-category="<?php echo $product['category']; ?>">
                    <div class="card product-card h-100">
                        <img src="<?php echo $product['image']; ?>" class="card-img-top rounded" alt="">
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title text-truncate"><?php echo $product['name']; ?></h6>
                            <p class="price mb-1"><?php echo number_format($product['price']); ?> บาท</p>
                            <small class="text-muted flex-grow-1"><?php echo $product['description']; ?></small>
                            <button class="btn btn-sm btn-buy text-white mt-3 open-cart-bar" data-product='<?php echo json_encode($product); ?>'>
                                🛒 ซื้อเลย
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart Bar -->
    <div class="cart-bar" id="cartBar">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <!-- <h5 class="mb-0">🛍️ เพิ่มสินค้าในรถเข็น</h5> -->
            <button class="btn-close" onclick="closeCartBar()"></button>
        </div>

        <div class="d-flex align-items-center mb-3">
            <img id="cartProductImage" src="" width="70" class="rounded me-3">
            <div>
                <h6 id="cartProductName" class="mb-1"></h6>
                <span class="price" id="cartProductPrice"></span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">จำนวน</label>
            <div class="quantity-control">
                <button class="btn btn-outline-secondary" onclick="changeQuantity(-1)">-</button>
                <input type="number" id="quantity" value="1" min="1" class="form-control text-center" style="width:70px;">
                <button class="btn btn-outline-secondary" onclick="changeQuantity(1)">+</button>
            </div>
        </div>

        <button class="btn btn-buy w-100 text-white py-2" onclick="confirmPurchase()">ยืนยันการซื้อ</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedProduct = null;

        // เปิด Cart Bar
        document.querySelectorAll('.open-cart-bar').forEach(btn => {
            btn.addEventListener('click', () => {
                selectedProduct = JSON.parse(btn.getAttribute('data-product'));
                openCartBar(selectedProduct);
            });
        });

        function openCartBar(product) {
            const bar = document.getElementById('cartBar');
            document.getElementById('cartProductImage').src = product.image;
            document.getElementById('cartProductName').innerText = product.name;
            document.getElementById('cartProductPrice').innerText = product.price + ' บาท';
            document.getElementById('quantity').value = 1;
            bar.classList.add('show');
        }

        function closeCartBar() {
            document.getElementById('cartBar').classList.remove('show');
        }

        function changeQuantity(change) {
            const input = document.getElementById('quantity');
            let value = parseInt(input.value);
            value = Math.max(1, value + change);
            input.value = value;
        }

        function confirmPurchase() {
            const qty = document.getElementById('quantity').value;
            const product = selectedProduct;

            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'payment.php';

            const fields = {
                product_name: product.name,
                quantity: qty,
                price: product.price,
            };

            for (const key in fields) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        }

        // ฟิลเตอร์หมวดหมู่
        document.querySelectorAll('.category-item').forEach(item => {
            item.addEventListener('click', e => {
                e.preventDefault();
                const selected = item.getAttribute('data-category');
                document.querySelectorAll('.category-item').forEach(a => a.classList.remove('active'));
                item.classList.add('active');

                document.querySelectorAll('.product-item').forEach(card => {
                    const cat = card.getAttribute('data-category');
                    card.style.display = (selected === 'ทั้งหมด' || cat === selected) ? 'block' : 'none';
                });
            });
        });

        // ฟังก์ชันค้นหา
        function searchCategory(e) {
            e.preventDefault(); // หยุดการรีเฟรชหน้าหลังจาก submit ฟอร์ม
            const keyword = document.getElementById('searchInput').value.trim().toLowerCase(); // รับคำค้นหาจาก input
            const products = document.querySelectorAll('.product-item'); // เลือกทุกรายการสินค้า

            let found = false;

            // วนลูปผ่านสินค้าแต่ละชิ้น
            products.forEach(product => {
                const productName = product.querySelector('.card-title').textContent.toLowerCase();
                const match = productName.includes(keyword); // เช็คว่าชื่อสินค้าตรงกับคำค้นหาหรือไม่

                if (match) {
                    product.style.display = 'block'; // แสดงสินค้าที่ตรง
                    found = true;
                } else {
                    product.style.display = 'none'; // ซ่อนสินค้าที่ไม่ตรง
                }
            });

            if (!found) {
                alert('ไม่พบสินค้าที่ค้นหา: ' + keyword); // แจ้งเตือนหากไม่พบสินค้าที่ตรงกับคำค้น
            }
        }
    </script>
</body>

</html>