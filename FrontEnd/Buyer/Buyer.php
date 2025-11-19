<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Users/line-entry.php");
    exit;
}

require_once '../../utils/db_with_log.php';
include_once '../../bootstrap.php';

$conn = connectDBWithLog();
$user_id = (int)$_SESSION['user_id'];

/* 1) โหลดชื่อผู้ใช้ */
$sqlUser  = "SELECT first_name, last_name FROM users WHERE id = ?";
$result   = db_query($conn, $sqlUser, [$user_id], "i");
$user     = $result ? $result->fetch_assoc() : ['first_name' => '', 'last_name' => ''];

/* 2) โหลด products ทั้งหมด */
$products = [];

$sqlProducts = "SELECT id, name, price, image, description, category, stock FROM products";
$resProd     = db_query($conn, $sqlProducts);   // ไม่มี params

if ($resProd && $resProd->num_rows > 0) {
    while ($row = $resProd->fetch_assoc()) {
        $products[$row['id']] = $row;
        $products[$row['id']]['variants'] = [];
    }
} else {
    $products = [];
}

/* 3) โหลด cart items ของ user คนนี้ */
$cart_items = [];
$sqlCart = "
    SELECT c.*, p.name AS name, p.image AS image,
           v.variant_name, v.image AS variant_image
    FROM cart_items c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_variants v ON c.variant_id = v.id
    WHERE c.user_id = ?
";
$resCart = db_query($conn, $sqlCart, [$user_id], "i");

if ($resCart && $resCart->num_rows > 0) {
    while ($row = $resCart->fetch_assoc()) {
        $cart_items[] = $row;
    }
}

/* 4) โหลด variants ทั้งหมด */
$variant_sql   = "SELECT id, product_id, variant_name, price, stock, image FROM product_variants";
$variant_result = db_query($conn, $variant_sql);

if ($variant_result && $variant_result->num_rows > 0) {
    while ($vrow = $variant_result->fetch_assoc()) {
        $pid = $vrow['product_id'];
        if (isset($products[$pid])) {
            $products[$pid]['variants'][] = $vrow;
        }
    }
}

$products = array_values($products);

/* 5) โหลดหมวดหมู่ */
$categories = ['ทั้งหมด'];
$cat_sql    = "SELECT DISTINCT category FROM products";
$cat_result = db_query($conn, $cat_sql);

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

    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top left, #ffe0e3 0, #fffaf1 35%, #e3f2fd 100%);
        }

        .navbar {
            backdrop-filter: blur(12px);
        }

        .navbar-glass {
            background: linear-gradient(90deg, rgba(238, 77, 45, 0.95), rgba(255, 143, 90, 0.95));
        }

        .hero-section {
            background: linear-gradient(135deg, #ee4d2d 0%, #ff7043 40%, #ffb74d 100%);
        }

        .hero-chip {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            padding: 6px 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(8px);
        }

        .product-card {
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            border-radius: 1rem;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-color: #ffcdd2;
        }

        .product-img-wrap {
            height: 180px;
            background: linear-gradient(135deg, #fff3e0, #ffebee);
        }

        .product-img-wrap img {
            object-fit: cover;
        }

        .category-chip {
            scrollbar-width: none;
        }

        .category-chip::-webkit-scrollbar {
            display: none;
        }

        .category-item.active {
            color: #fff !important;
            border-color: transparent !important;
            background: linear-gradient(135deg, #ff7043, #ffb74d) !important;
        }

        #cartBar {
            box-shadow: 0 -12px 30px rgba(0, 0, 0, 0.12);
        }

        .variant-pill {
            border-radius: 999px;
        }

        .variant-pill.active {
            color: #fff !important;
            border-color: transparent !important;
            background: linear-gradient(135deg, #ee4d2d, #ff7043) !important;
        }

        .badge-stock {
            font-size: 0.7rem;
        }

        .cart-row {
            background: linear-gradient(135deg, #fafafa, #fff);
            border-radius: 0.75rem;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-glass sticky-top shadow-sm">
        <div class="container-fluid px-3">
            <a class="navbar-brand fw-bold fs-5 d-flex align-items-center gap-2" href="#">
                <span class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center"
                    style="width:32px;height:32px;">
                    <i class="bi bi-bag-check text-danger"></i>
                </span>
                <span>Line-Shop</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="text-white-50 d-none d-md-inline small">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($user['first_name']); ?>
                </span>
                <button class="btn btn-light btn-sm rounded-circle position-relative shadow-sm" id="cartIcon" type="button">
                    <i class="bi bi-cart3 text-danger"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge bg-warning text-dark rounded-pill"
                        id="cartCountBadge"
                        style="font-size:0.65rem; display:none;">
                        0
                    </span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section py-4 py-md-5 text-center text-white">
        <div class="container">
            <h3 class="fw-bold mb-2">
                สวัสดีคุณ <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> 👋
            </h3>
            <p class="text-white-50 mb-3 mb-md-4">เลือกช้อปสินค้าแสนสะดวก พร้อมดีลพิเศษสำหรับคุณ</p>
            <div class="hero-chip">
                <i class="bi bi-stars text-warning"></i>
                <span class="small">ช้อปง่าย • จ่ายสะดวก • ดูสลิปได้</span>
            </div>
        </div>
    </section>

    <!-- Search Bar -->
    <div class="bg-white py-3 border-bottom border-light-subtle">
        <div class="container">
            <form role="search" onsubmit="searchCategory(event)">
                <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden">
                    <span class="input-group-text bg-white border-0">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                    <input class="form-control border-0" type="search"
                        placeholder="ค้นหาสินค้า เช่น เสื้อยืด, รองเท้า, กระเป๋า..."
                        aria-label="ค้นหา" id="searchInput">
                    <button class="btn btn-danger fw-bold px-4" type="submit">
                        ค้นหา
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Bar -->
    <div class="bg-white py-2 border-bottom border-light-subtle">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-2 d-md-none px-1">
                <div class="small text-muted">
                    <i class="bi bi-sliders me-1"></i> เลือกดูตามหมวดหมู่
                </div>
                <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button"
                    data-bs-toggle="collapse" data-bs-target="#categoryCollapse">
                    <i class="bi bi-funnel"></i> หมวดหมู่
                </button>
            </div>

            <div id="categoryCollapse" class="collapse d-md-block show">
                <div class="d-flex category-chip overflow-x-auto gap-2 py-1">
                    <?php foreach ($categories as $index => $cat): ?>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary rounded-pill text-nowrap flex-shrink-0 category-item <?php echo $cat === 'ทั้งหมด' ? 'active' : ''; ?>"
                            data-category="<?php echo $cat; ?>">
                            <?php if ($cat === 'ทั้งหมด'): ?>
                                <i class="bi bi-grid-3x3-gap-fill me-1"></i>
                            <?php else: ?>
                                <i class="bi bi-tag me-1"></i>
                            <?php endif; ?>
                            <?php echo $cat; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <main class="container py-4">
        <div class="row g-3" id="product-list">
            <?php foreach ($products as $product): ?>
                <div class="col-6 col-md-4 col-lg-3 product-item" data-category="<?php echo $product['category']; ?>">
                    <div class="card product-card h-100 border-0 shadow-sm">
                        <div class="position-relative product-img-wrap rounded-top-4 overflow-hidden">
                            <img src="<?php echo $product['image']; ?>"
                                class="card-img-top w-100 h-100"
                                alt="<?php echo $product['name']; ?>"
                                loading="lazy">
                            <?php if (!empty($product['category'])): ?>
                                <span class="badge text-bg-light position-absolute top-2 start-2 rounded-pill shadow-sm small">
                                    <i class="bi bi-tag me-1 text-danger"></i><?php echo $product['category']; ?>
                                </span>
                            <?php endif; ?>
                            <span class="badge bg-success-subtle text-success-emphasis position-absolute bottom-2 end-2 badge-stock shadow-sm">
                                คงเหลือ <?php echo (int)$product['stock']; ?>
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column p-2">
                            <h6 class="card-title text-truncate small fw-semibold mb-1">
                                <?php echo $product['name']; ?>
                            </h6>
                            <p class="text-danger fw-bold fs-6 mb-1">
                                ฿<?php echo number_format($product['price']); ?>
                            </p>
                            <small class="text-muted text-truncate flex-grow-1 mb-2">
                                <?php echo $product['description']; ?>
                            </small>
                            <div class="d-grid gap-1 mt-1">
                                <button
                                    class="btn btn-sm btn-outline-danger fw-semibold rounded-3 add-cart-btn"
                                    data-product='<?php echo json_encode($product, JSON_UNESCAPED_UNICODE); ?>'>
                                    <i class="bi bi-cart-plus me-1"></i> เพิ่มตะกร้า
                                </button>
                                <button
                                    class="btn btn-sm fw-semibold rounded-3 text-white open-cart-bar"
                                    style="background: linear-gradient(135deg, #ff7043, #ff9800);"
                                    data-product='<?php echo json_encode($product, JSON_UNESCAPED_UNICODE); ?>'>
                                    <i class="bi bi-lightning-charge me-1"></i> ซื้อเลย
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Cart Bar (Popup from bottom) -->
    <div class="position-fixed bottom-0 start-0 end-0 bg-white border-top border-3"
        id="cartBar"
        style="border-top-color: #ee4d2d!important; transform: translateY(100%); transition: transform 0.3s ease; z-index: 1050; border-top-left-radius: 20px; border-top-right-radius: 20px;">
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill text-bg-danger-subtle text-danger-emphasis">
                        <i class="bi bi-pencil-square me-1"></i> เพิ่มสินค้า
                    </span>
                </div>
                <button type="button" class="btn-close" onclick="closeCartBar()"></button>
            </div>

            <div class="d-flex gap-3 mb-3">
                <div class="rounded-3 overflow-hidden bg-light" style="width:80px;height:80px;">
                    <img id="cartProductImage" src="" class="w-100 h-100" style="object-fit: cover;">
                </div>
                <div class="flex-grow-1">
                    <h6 id="cartProductName" class="mb-1 small fw-semibold"></h6>
                    <p class="text-danger fw-bold fs-6 mb-1" id="cartProductPrice"></p>
                    <small class="text-muted">
                        คงเหลือ:
                        <span id="stockInfo" class="fw-semibold text-success">--</span>
                    </small>
                </div>
            </div>

            <div class="mb-3" id="variantWrapper" style="display: none;">
                <label class="form-label small fw-semibold mb-2">เลือกตัวเลือกสินค้า</label>
                <div id="variantList" class="d-flex flex-wrap gap-2"></div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold mb-2">จำนวน</label>
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <button type="button"
                        class="btn btn-outline-secondary btn-sm rounded-circle fw-bold"
                        style="width: 36px; height: 36px;"
                        onclick="changeQuantity(-1)">−</button>
                    <input type="number" id="quantity" value="1" min="1"
                        class="form-control text-center fw-bold"
                        style="width: 80px;">
                    <button type="button"
                        class="btn btn-outline-secondary btn-sm rounded-circle fw-bold"
                        style="width: 36px; height: 36px;"
                        onclick="changeQuantity(1)">+</button>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-danger fw-bold rounded-3" onclick="addCurrentToCart()">
                    <i class="bi bi-cart-plus me-1"></i> เพิ่มลงตะกร้า
                </button>
                <button type="button"
                    class="btn fw-bold rounded-3 text-white"
                    style="background: linear-gradient(135deg, #ee4d2d, #ff7043);"
                    onclick="confirmPurchase()">
                    <i class="bi bi-lightning-charge me-1"></i> ซื้อเลย
                </button>
            </div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <span class="bg-danger-subtle text-danger-emphasis rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width:32px;height:32px;">
                            <i class="bi bi-bag-check-fill"></i>
                        </span>
                        <span>ตะกร้าสินค้า</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cartItemsContainer"></div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-between align-items-center pt-0">
                    <div class="fw-bold fs-6">
                        รวมทั้งหมด:
                        <span class="text-danger" id="cartTotal">0 บาท</span>
                    </div>
                    <button type="button"
                        class="btn fw-bold text-white rounded-3 px-4"
                        style="background: linear-gradient(135deg, #ff7043, #ff9800);"
                        id="goPaymentBtn">
                        <i class="bi bi-credit-card me-1"></i> ชำระเงิน
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        let selectedProduct = null;
        let selectedVariant = null;

        let cart = <?php echo json_encode($cart_items, JSON_UNESCAPED_UNICODE); ?> || [];
        let cartModal = null;

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.open-cart-bar').forEach(btn => {
                btn.addEventListener('click', () => {
                    selectedProduct = JSON.parse(btn.getAttribute('data-product'));
                    openCartBar(selectedProduct);
                });
            });

            document.querySelectorAll('.add-cart-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    selectedProduct = JSON.parse(btn.getAttribute('data-product'));
                    openCartBar(selectedProduct);
                });
            });

            const modalEl = document.getElementById('cartModal');
            if (modalEl) {
                cartModal = new bootstrap.Modal(modalEl);
            }

            const cartIcon = document.getElementById('cartIcon');
            if (cartIcon) {
                cartIcon.addEventListener('click', (e) => {
                    e.preventDefault();
                    renderCartModal();
                    if (cartModal) cartModal.show();
                });
            }

            updateCartBadge();

            document.getElementById('goPaymentBtn').addEventListener('click', () => {
                if (cart.length === 0) {
                    alert('ตะกร้าว่างเปล่า');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'payment.php';

                const mode = document.createElement('input');
                mode.type = 'hidden';
                mode.name = 'mode';
                mode.value = 'cart';
                form.appendChild(mode);

                cart.forEach(item => {
                    const fields = {
                        product_id: item.product_id,
                        variant_id: item.variant_id || '',
                        product_name: item.name,
                        quantity: item.quantity,
                        price: item.price
                    };

                    for (const key in fields) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key + '[]';
                        input.value = fields[key];
                        form.appendChild(input);
                    }
                });

                document.body.appendChild(form);
                form.submit();
            });
        });

        function openCartBar(product) {
            const bar = document.getElementById('cartBar');
            const imgEl = document.getElementById('cartProductImage');
            const nameEl = document.getElementById('cartProductName');
            const priceEl = document.getElementById('cartProductPrice');

            selectedVariant = null;

            imgEl.src = product.image;
            nameEl.innerText = product.name;
            priceEl.innerText = '฿' + product.price;
            document.getElementById('quantity').value = 1;

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
                    btn.className = 'btn btn-sm btn-outline-danger variant-pill';
                    btn.textContent = variant.variant_name;

                    btn.dataset.id = variant.id;
                    btn.dataset.name = variant.variant_name;
                    btn.dataset.price = variant.price || product.price;
                    btn.dataset.image = variant.image || product.image;

                    btn.addEventListener('click', () => {
                        document.querySelectorAll('#variantList .btn').forEach(el => {
                            el.classList.remove('active', 'variant-pill');
                            el.classList.remove('active');
                            el.classList.add('variant-pill');
                        });

                        btn.classList.add('active', 'variant-pill');

                        const newPrice = btn.dataset.price;
                        const newImage = btn.dataset.image;

                        priceEl.innerText = '฿' + newPrice;
                        imgEl.src = newImage;

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

                    if (index === 0) {
                        btn.click();
                    }
                });

            } else {
                variantWrapper.style.display = 'none';
                priceEl.innerText = '฿' + product.price;
                imgEl.src = product.image;
            }

            bar.style.transform = 'translateY(0)';
        }

        function closeCartBar() {
            document.getElementById('cartBar').style.transform = 'translateY(100%)';
        }

        function changeQuantity(change) {
            const input = document.getElementById('quantity');
            let value = parseInt(input.value);
            value = Math.max(1, value + change);
            input.value = value;
        }

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
            syncCartToServer();
        }

        function removeCartItem(index) {
            if (index < 0 || index >= cart.length) return;

            cart.splice(index, 1);
            updateCartBadge();
            renderCartModal();
            syncCartToServer();
        }

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
                container.innerHTML = '<div class="alert alert-info rounded-3 mb-0"><i class="bi bi-info-circle me-2"></i>ยังไม่มีสินค้าในตะกร้า</div>';
                totalEl.textContent = '0 บาท';
                return;
            }

            let total = 0;

            cart.forEach((item, index) => {
                const lineTotal = item.price * item.quantity;
                total += lineTotal;

                const row = document.createElement('div');
                row.className = 'd-flex align-items-center gap-2 mb-3 p-2 cart-row';

                row.innerHTML = `
                    <div class="rounded-3 overflow-hidden bg-light" style="width:60px;height:60px;">
                        <img src="${item.image}" class="w-100 h-100" style="object-fit: cover;">
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="small fw-semibold text-truncate">${item.name}</div>
                        <div class="small text-muted">จำนวน: <span class="fw-bold">${item.quantity}</span> ชิ้น</div>
                    </div>
                    <div class="text-end me-2">
                        <div class="small text-muted">฿${lineTotal.toLocaleString()}</div>
                    </div>
                    <button type="button"
                        class="btn btn-sm btn-outline-danger rounded-3 remove-cart-item"
                        data-index="${index}">
                        <i class="bi bi-trash"></i>
                    </button>
                `;

                container.appendChild(row);
            });

            totalEl.textContent = total.toLocaleString() + ' บาท';

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

            syncCartToServer();

            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'payment.php';

            const fields = {
                product_id: product.id,
                product_name: product.name,
                quantity: qty,
            };

            if (selectedVariant) {
                fields.variant_id = selectedVariant.id;
                fields.variant_name = selectedVariant.name;
                fields.variant_image = selectedVariant.image;
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

        document.querySelectorAll('.category-item').forEach(item => {
            item.addEventListener('click', e => {
                e.preventDefault();
                const selected = item.getAttribute('data-category');
                document.querySelectorAll('.category-item').forEach(a => {
                    a.classList.remove('active');
                });
                item.classList.add('active');

                document.querySelectorAll('.product-item').forEach(card => {
                    const cat = card.getAttribute('data-category');
                    card.style.display = (selected === 'ทั้งหมด' || cat === selected) ? 'block' : 'none';
                });
            });
        });

        function searchCategory(e) {
            e.preventDefault();
            const keyword = document.getElementById('searchInput').value.trim().toLowerCase();
            const products = document.querySelectorAll('.product-item');

            let found = false;

            products.forEach(product => {
                const productName = product.querySelector('.card-title').textContent.toLowerCase();
                const match = productName.includes(keyword);

                if (match) {
                    product.style.display = 'block';
                    found = true;
                } else {
                    product.style.display = 'none';
                }
            });

            if (!found) {
                alert('ไม่พบสินค้าที่ค้นหา: ' + keyword);
            }
        }

        function syncCartToServer() {
            // console.log('cart before sync', cart);

            fetch('save_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        cart
                    })
                })
                .then(res => res.json())
                .then(data => {
                    // console.log('Server response:', data);
                    if (data.status === 'ok') {
                        console.log('Cart synced to server');
                    } else {
                        console.error('Sync error:', data);
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                });

        }
    </script>

</body>

</html>