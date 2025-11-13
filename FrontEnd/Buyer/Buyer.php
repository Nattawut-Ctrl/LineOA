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

$products = [];

$sql = "SELECT id, name, price, image, description, category, stock FROM products";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[$row['id']] = $row;
        $products[$row['id']]['variants'] = [];
    }
} else {
    $products = [];
}

$variant_sql = "SELECT id, product_id, variant_name, price, stock, image FROM product_variants";
$variant_result = $conn->query($variant_sql);

if ($variant_result && $variant_result->num_rows > 0) {
    while ($vrow = $variant_result->fetch_assoc()) {
        $pid = $vrow['product_id'];
        if (isset($products[$pid])) {
            $products[$pid]['variants'][] = $vrow;
        }
    }
}

$products = array_values($products);

$categories = ['ทั้งหมด'];
$cat_sql = "SELECT DISTINCT category FROM products";
$cat_result = $conn->query($cat_sql);

if ($cat_result && $cat_result->num_rows > 0) {
    while ($cat_row = $cat_result->fetch_assoc()) {
        $categories[] = $cat_row['category'];
    }
}

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line-Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

        .variant-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 150px;
            overflow-y: auto;
        }

        .variant-item {
            border: 1px solid #ddd;
            border-radius: 18px;
            padding: 6px 12px;
            font-size: 0.85rem;
            background: #fff;
            cursor: pointer;
            white-space: nowrap;
        }

        .variant-item.active {
            border-color: #ee4d2d;
            background: #ffe9e3;
            color: #ee4d2d;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand text-white" href="#">🛍️ Line-Shop</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item position-relative">
                    <a href="#" class="nav-link text-white" id="cartIcon">
                        <i class="bi bi-cart3"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            id="cartCountBadge"
                            style="font-size:0.7rem; display:none;">
                            0
                        </span>
                    </a>
                </li>
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
                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-sm btn-outline-secondary flex-fill add-cart-btn"
                                    data-product='<?php echo json_encode($product, JSON_UNESCAPED_UNICODE); ?>'>
                                    เพิ่มลงตะกร้า
                                </button>
                                <button class="btn btn-sm btn-buy text-white flex-fill open-cart-bar"
                                    data-product='<?php echo json_encode($product, JSON_UNESCAPED_UNICODE); ?>'>
                                    ซื้อเลย
                                </button>
                            </div>
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

                <div>
                    <label>สินค้าคงเหลือ</label>
                    <span id="stockInfo" class="ms-2 text-muted">--</span>
                </div>

            </div>
        </div>

        <div class="mb-3" id="variantWrapper" style="display: none;">
            <label class="form-label">ตัวเลือกสินค้า</label>
            <div id="variantList" class="variant-list"></div>
        </div>

        <div class="mb-3">
            <label class="form-label">จำนวน</label>
            <div class="quantity-control">
                <button class="btn btn-outline-secondary" onclick="changeQuantity(-1)">-</button>
                <input type="number" id="quantity" value="1" min="1" class="form-control text-center" style="width:70px;">
                <button class="btn btn-outline-secondary" onclick="changeQuantity(1)">+</button>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary w-50" onclick="addCurrentToCart()">เพิ่มลงตะกร้า</button>
            <button class="btn btn-buy text-white w-50" onclick="confirmPurchase()">ซื้อเลย</button>
        </div>

        <!-- <button class="btn btn-buy w-100 text-white py-2" onclick="confirmPurchase()">ยืนยันการซื้อ</button> -->
    </div>

    <!-- ✅ Modal แสดงรายการตะกร้า -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ตะกร้าสินค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cartItemsContainer"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div class="fw-bold">รวม: <span id="cartTotal">0 บาท</span></div>
                    <button type="button" class="btn btn-buy text-white">
                        ไปหน้าชำระเงิน
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ---------------------------------------------SCRIPT---------------------------------------------- -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedProduct = null;
        let selectedVariant = null;

        // ✅ ตะกร้า
        let cart = [];
        let cartModal = null;

        // ✅ เตรียม event ตอน DOM พร้อม
        document.addEventListener('DOMContentLoaded', () => {
            // ปุ่ม "ซื้อเลย" บนการ์ด
            document.querySelectorAll('.open-cart-bar').forEach(btn => {
                btn.addEventListener('click', () => {
                    selectedProduct = JSON.parse(btn.getAttribute('data-product'));
                    openCartBar(selectedProduct);
                });
            });

            // ปุ่ม "เพิ่มลงตะกร้า" บนการ์ด -> เปิด cart bar ให้เลือกตัวเลือก/จำนวนก่อน
            document.querySelectorAll('.add-cart-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    selectedProduct = JSON.parse(btn.getAttribute('data-product'));
                    openCartBar(selectedProduct);
                });
            });

            // modal ตะกร้า
            const modalEl = document.getElementById('cartModal');
            if (modalEl) {
                cartModal = new bootstrap.Modal(modalEl);
            }

            // ไอคอนตะกร้าบน navbar
            const cartIcon = document.getElementById('cartIcon');
            if (cartIcon) {
                cartIcon.addEventListener('click', (e) => {
                    e.preventDefault();
                    renderCartModal();
                    if (cartModal) cartModal.show();
                });
            }
        });

        function openCartBar(product) {
            const bar = document.getElementById('cartBar');
            const imgEl = document.getElementById('cartProductImage');
            const nameEl = document.getElementById('cartProductName');
            const priceEl = document.getElementById('cartProductPrice');

            selectedVariant = null; // reset ทุกครั้งที่เปิด

            imgEl.src = product.image;
            nameEl.innerText = product.name;
            priceEl.innerText = product.price + ' บาท';
            document.getElementById('quantity').value = 1;

            // แสดง stock ของ product ก่อน (default)
            const stockEl = document.getElementById("stockInfo");
            stockEl.textContent = product.stock ?? "--";


            const variantWrapper = document.getElementById('variantWrapper');
            const variantList = document.getElementById('variantList');

            if (variantList) {
                variantList.innerHTML = '';
            }

            if (product.variants && product.variants.length > 0) {
                variantWrapper.style.display = 'block';

                product.variants.forEach((variant, index) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'variant-item';
                    btn.textContent = variant.variant_name;

                    // เก็บข้อมูลใน data-*
                    btn.dataset.id = variant.id;
                    btn.dataset.name = variant.variant_name;
                    btn.dataset.price = variant.price || product.price;
                    btn.dataset.image = variant.image || product.image;

                    btn.addEventListener('click', () => {
                        // ลบ active ของตัวอื่น
                        document
                            .querySelectorAll('#variantList .variant-item')
                            .forEach(el => el.classList.remove('active'));

                        // set active ตัวนี้
                        btn.classList.add('active');

                        // อัปเดตราคา + รูป
                        const newPrice = btn.dataset.price;
                        const newImage = btn.dataset.image;

                        priceEl.innerText = newPrice + ' บาท';
                        imgEl.src = newImage;

                        // เก็บค่าไว้ใช้ตอนยืนยันซื้อ
                        selectedVariant = {
                            id: btn.dataset.id,
                            name: btn.dataset.name,
                            price: newPrice,
                            image: newImage
                        };

                        const stockEl = document.getElementById("stockInfo");
                        stockEl.textContent = variant.stock ?? "--";

                    });

                    variantList.appendChild(btn);

                    // เลือกตัวแรกเป็น default เหมือน Shopee
                    if (index === 0) {
                        btn.click();
                    }
                });

            } else {
                variantWrapper.style.display = 'none';
                priceEl.innerText = product.price + ' บาท';
                imgEl.src = product.image;
            }

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

        // ✅ เพิ่มสินค้าปัจจุบันลงตะกร้า
        function addCurrentToCart() {
            if (!selectedProduct) return;

            const qty = parseInt(document.getElementById('quantity').value) || 1;
            const base = selectedProduct;
            const variant = selectedVariant;

            const productId = base.id;
            const variantId = variant ? variant.id : null;
            const price = variant ? Number(variant.price) : Number(base.price);
            const name = base.name + (variant ? ` (${variant.name})` : '');
            const image = (variant && variant.image) ? variant.image : base.image;

            const existing = cart.find(
                item => item.product_id == productId && item.variant_id == variantId
            );

            if (existing) {
                existing.quantity += qty;
            } else {
                cart.push({
                    product_id: productId,
                    variant_id: variantId,
                    name: name,
                    price: price,
                    image: image,
                    quantity: qty
                });
            }

            updateCartBadge();
            alert('เพิ่มสินค้าในตะกร้าแล้ว');
        }

        function removeCartItem(index) {
            if (index < 0 || index >= cart.length) return;

            cart.splice(index, 1); // ลบออกจาก array
            updateCartBadge(); // อัปเดตจำนวนบน badge
            renderCartModal(); // วาด modal ใหม่

            // ถ้าลบจนเหลือ 0 ชิ้น จะเจอข้อความ "ยังไม่มีสินค้าในตะกร้า" อัตโนมัติ
        }


        // ✅ badge บน icon ตะกร้า
        function updateCartBadge() {
            const badge = document.getElementById('cartCountBadge');
            if (!badge) return;

            const count = cart.reduce((sum, item) => sum + item.quantity, 0);

            if (count > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = count;
            } else {
                badge.style.display = 'none';
            }
        }

        function renderCartModal() {
            const container = document.getElementById('cartItemsContainer');
            const totalEl = document.getElementById('cartTotal');

            container.innerHTML = '';

            if (cart.length === 0) {
                container.innerHTML = '<p class="text-muted mb-0">ยังไม่มีสินค้าในตะกร้า</p>';
                totalEl.textContent = '0 บาท';
                return;
            }

            let total = 0;

            cart.forEach((item, index) => {
                const lineTotal = item.price * item.quantity;
                total += lineTotal;

                const row = document.createElement('div');
                row.className = 'd-flex align-items-center mb-2';

                row.innerHTML = `
            <img src="${item.image}" width="50" class="rounded me-2">
            <div class="flex-grow-1">
                <div class="small">${item.name}</div>
                <div class="small text-muted">จำนวน: ${item.quantity}</div>
            </div>
            <div class="text-end small me-2">${lineTotal.toLocaleString()} บาท</div>
            <button type="button"
                    class="btn btn-sm btn-outline-danger remove-cart-item"
                    data-index="${index}">
                <i class="bi bi-trash"></i>
            </button>
        `;

                container.appendChild(row);
            });

            totalEl.textContent = total.toLocaleString() + ' บาท';

            // ผูก event ให้ปุ่มลบ
            container.querySelectorAll('.remove-cart-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    const idx = parseInt(btn.dataset.index);
                    removeCartItem(idx);
                });
            });
        }




        function confirmPurchase() {
            const qty = document.getElementById('quantity').value;
            const product = selectedProduct;

            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'payment.php';

            const fields = {
                product_id: product.id,
                product_name: product.name,
                quantity: qty,
                price: product.price, // จะถูก override ถ้ามี variant
            };

            // ถ้ามีเลือก variant
            if (selectedVariant) {
                fields.variant_id = selectedVariant.id;
                fields.variant_name = selectedVariant.name;
                fields.price = selectedVariant.price; // ใช้ราคาตามตัวเลือก
                fields.variant_image = selectedVariant.image; // ถ้าอยากส่งรูปไปด้วย
            }

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