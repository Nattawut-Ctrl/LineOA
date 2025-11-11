<?php
// ข้อมูลสินค้า (จากฐานข้อมูล)
$products = [
    ['title' => 'เสื้อผ้าผู้ชาย', 'price' => 299, 'image' => 'https://via.placeholder.com/150', 'sold' => 0, 'category' => 'เสื้อผ้า', 'description' => 'เสื้อผ้าผู้ชายทรงสวย ใส่สบาย'],
    ['title' => 'กระบอกน้ำสแตนเลส', 'price' => 150, 'image' => 'https://via.placeholder.com/150', 'sold' => 0, 'category' => 'กระบอกน้ำ', 'description' => 'กระบอกน้ำสแตนเลส ทนทาน ดีไซน์ทันสมัย'],
];

$categories = ['ทั้งหมด', 'เสื้อผ้า', 'กระบอกน้ำ'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>หน้าซื้อสินค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-img-top {
            object-fit: cover;
            height: 200px;
        }

        .product-card {
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">แชท</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">การแจ้งเตือน</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">ตะกร้า</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Search Bar -->
    <div class="container py-4">
        <form id="searchForm" role="search">
            <div class="input-group">
                <input type="search" id="searchInput" class="form-control" placeholder="ค้นหาสินค้า เช่น เสื้อ รองเท้า โทรศัพท์">
                <button class="btn btn-outline-secondary" type="button" id="searchBtn">ค้นหา</button>
            </div>
        </form>
    </div>

    <!-- Categories -->
    <div class="container py-3">
        <div class="btn-group btn-group-sm" role="group" aria-label="หมวดหมู่สินค้า">
            <?php foreach ($categories as $cat): ?>
                <button type="button" class="btn btn-outline-secondary category-btn" data-category="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Products -->
    <div class="container py-3" id="productContainer">
        <div class="row g-3" id="productList">
            <?php foreach ($products as $p): ?>
                <div class="col-6 col-sm-4 col-md-3 product-card" data-category="<?php echo htmlspecialchars($p['category']); ?>" data-product-id= "<?php echo $p['id']; ?>">
                    <div class="card">
                        <img src="<?php echo $p['image']; ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" class="card-img-top" loading="lazy">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($p['title']); ?></h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-success fw-bold"><?php echo number_format($p['price']); ?> ฿</span>
                                <span class="text-muted"><?php echo $p['sold']; ?> ขายแล้ว</span>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm mt-auto addToCartBtn">เพิ่มลงตะกร้า</button>
                            <button class="btn btn-primary btn-sm mt-2">ซื้อเลย</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Load More Button -->
    <div class="container py-3 text-center">
        <button class="btn btn-light border" id="loadMoreBtn">ดูเพิ่มเติม</button>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">รายละเอียดสินค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img id="productImage" src="" alt="" class="img-fluid">
                        </div>
                        <div class="col-md-6">
                            <h1 id="productTitle"></h1>
                            <p class="text-muted" id="productPrice"></p>
                            <p class="text-muted" id="productSold"></p>
                            <p id="productDescription"></p>

                            <button class="btn btn-primary btn-lg">ซื้อเลย</button>
                            <button class="btn btn-outline-secondary btn-lg mt-3">เพิ่มลงตะกร้า</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ฟังก์ชันเมื่อคลิกที่สินค้าจะเปิด modal และแสดงข้อมูลสินค้า
        document.querySelectorAll('.product-card').forEach(function(card) {
            card.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id'); // ดึง ID ของสินค้า

                // ดึงข้อมูลสินค้า (ในที่นี้จะต้องมีข้อมูลของสินค้าใน array หรือจากฐานข้อมูล)
                // ตัวอย่างนี้สมมติว่า products เป็น array ของสินค้าที่มีข้อมูล
                const product = products.find(p => p.id == productId); // หาสินค้าจาก ID

                // กำหนดข้อมูลใน modal
                document.getElementById('productImage').src = product.image;
                document.getElementById('productTitle').innerText = product.title;
                document.getElementById('productPrice').innerText = `${product.price} ฿`;
                document.getElementById('productSold').innerText = `${product.sold} ขายแล้ว`;
                document.getElementById('productDescription').innerText = product.description;

                // เปิด modal
                const modal = new bootstrap.Modal(document.getElementById('productModal'));
                modal.show();
            });
        });



        // Function for search
        document.getElementById('searchBtn').addEventListener('click', function() {
            let searchQuery = document.getElementById('searchInput').value.toLowerCase();
            let products = document.querySelectorAll('.product-card');

            products.forEach(function(product) {
                let title = product.querySelector('.card-title').textContent.toLowerCase();
                if (title.includes(searchQuery)) {
                    product.style.display = '';
                } else {
                    product.style.display = 'none';
                }
            });
        });

        // Function for category filter
        const categoryButtons = document.querySelectorAll('.category-btn');
        categoryButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                let selectedCategory = button.getAttribute('data-category');
                let products = document.querySelectorAll('.product-card');

                products.forEach(function(product) {
                    let productCategory = product.getAttribute('data-category');
                    if (selectedCategory === productCategory || selectedCategory === 'ทั้งหมด') {
                        product.style.display = '';
                    } else {
                        product.style.display = 'none';
                    }
                });
            });
        });

        // Function for add to cart
        document.querySelectorAll('.addToCartBtn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                alert('สินค้าถูกเพิ่มลงในตะกร้าแล้ว!');
            });
        });
    </script>
</body>

</html>