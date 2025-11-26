<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';

require_once SERVICES_PATH . '/productService.php';
require_once SERVICES_PATH . '/cartService.php';
require_once SERVICES_PATH . '/userService.php';

$conn    = connectDBWithLog();
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header("Location: ../Users/line-entry.php?from=shop");
    exit;
}

// ----------------------- Fetch User Info via Service -----------------------
$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: ../Users/line-entry.php?from=register");
    exit;
}

// ----------------------- Fetch Products + Variants via Service -----------------------
$productsAssoc = getAllProductsWithVariants($conn);
$products      = array_values($productsAssoc);

// ----------------------- Fetch Categories via Service -----------------------
$categories = array_merge(['ทั้งหมด'], getAllCategories($conn));

// ----------------------- Fetch Cart Items via Service (shape same as old SQL) -----------------------
$cart_items = getCartItems($conn, $user_id);

// ------------------------ View Part ------------------------
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line-Shop</title>
    <?php include BASE_PATH . '/partials/bootstrap.php'; ?>

    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top left, #ffe0e3 0, #fffaf1 35%, #e3f2fd 100%);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        /* Navbar */
        .navbar-glass {
            background: linear-gradient(90deg,
                    rgba(238, 77, 45, 0.95),
                    rgba(255, 143, 90, 0.95));
            backdrop-filter: blur(12px);
        }

        /* Search ใน Navbar */
        .navbar-search-form {
            flex: 1;
        }

        .navbar-search-input-group {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 999px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        .navbar-search-input-group .form-control {
            border: 0;
            font-size: 0.9rem;
            padding-top: .35rem;
            padding-bottom: .35rem;
        }

        .navbar-search-input-group .input-group-text {
            border: 0;
            background: transparent;
        }

        .navbar-search-input-group .btn-search {
            border-radius: 0;
            font-size: 0.85rem;
        }

        @media (min-width: 768px) {
            .navbar-search-input-group .form-control {
                font-size: 0.95rem;
            }
        }

        /* Hero */
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

        /* Product Card */
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

        /* Cart bar */
        #cartBar {
            box-shadow: 0 -12px 30px rgba(0, 0, 0, 0.12);
        }

        /* Variants */
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

        .fly-img {
            position: fixed;
            z-index: 2000;
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            transition: transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1),
                opacity 0.6s ease;
            pointer-events: none;
        }

        @keyframes cart-bounce {
            0% {
                transform: translateY(0);
            }

            30% {
                transform: translateY(-3px);
            }

            60% {
                transform: translateY(1px);
            }

            100% {
                transform: translateY(0);
            }
        }

        .shake-cart {
            animation: cart-bounce 0.3s ease;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 62px;
            background: #fff;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 2000;
        }

        .bottom-nav .nav-item {
            flex: 1;
            text-align: center;
            font-size: 0.75rem;
            color: #777;
            text-decoration: none;
            padding-top: 4px;
        }

        .bottom-nav .nav-item i {
            font-size: 1.3rem;
        }

        .bottom-nav .nav-item.active,
        .bottom-nav .nav-item:hover {
            color: #ee4d2d;
        }

        .bottom-nav .nav-item.active i {
            color: #ee4d2d;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-glass sticky-top shadow-sm">
        <div class="container-fluid px-3 py-2">

            <a class="navbar-brand fw-bold d-none d-md-flex align-items-center gap-2 me-3" href="#">
                <span class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center"
                    style="width:32px;height:32px;">
                    <i class="bi bi-bag-check text-danger"></i>
                </span>
                <span>Line-Shop</span>
            </a>

            <form class="navbar-search-form" role="search">
                <div class="input-group navbar-search-input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                    <input class="form-control"
                        type="search"
                        placeholder="ค้นหาสินค้า เช่น เสื้อยืด, รองเท้า, กระเป๋า..."
                        aria-label="ค้นหา"
                        id="searchInput"
                        readonly>
                    <!-- <button class="btn btn-light btn-search d-none d-sm-inline" type="button">
                        <i class="bi bi-camera"></i>
                    </button> -->
                </div>
            </form>


            <!-- ขวาสุด: ชื่อ user + ตะกร้า -->
            <div class="d-flex align-items-center ms-2 gap-2">
                <span class="text-white-50 d-none d-md-inline small">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($user['first_name']); ?>
                </span>

                <button class="btn btn-link text-white position-relative p-0" id="cartIcon" type="button">
                    <i class="bi bi-cart3 fs-4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge bg-warning text-dark rounded-pill"
                        id="cartCountBadge" style="font-size:0.65rem; display:none;">
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
                สวัสดีคุณ
                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> 👋
            </h3>
            <p class="text-white-50 mb-3 mb-md-4">
                เลือกช้อปสินค้าแสนสะดวก พร้อมดีลพิเศษสำหรับคุณ
            </p>
            <div class="hero-chip shadow-sm">
                <i class="bi bi-stars text-warning"></i>
                <span class="small">ช้อปง่าย • จ่ายสะดวก • ดูสลิปได้</span>
            </div>
        </div>
    </section>

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
                    <?php foreach ($categories as $cat): ?>
                        <button type="button"
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
        <div class="row g-3 g-md-4" id="product-list">
            <?php foreach ($products as $product): ?>
                <div class="col-6 col-md-4 col-lg-3 product-item mb-2 mb-md-3"
                    data-category="<?php echo $product['category']; ?>">

                    <div class="card product-card clickable-card h-100 border-0 shadow-sm bg-white"
                        data-href="product-detail.php?id=<?php echo (int)$product['id']; ?>">
                        <div class="position-relative product-img-wrap rounded-top-4 overflow-hidden">
                            <img src="<?php echo $product['image']; ?>"
                                class="card-img-top w-100 h-100"
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                loading="lazy">
                            <?php if (!empty($product['category'])): ?>
                                <span
                                    class="badge text-bg-light position-absolute top-0 start-0 m-2 rounded-pill shadow-sm small">
                                    <i class="bi bi-tag me-1 text-danger"></i>
                                    <?php echo htmlspecialchars($product['category']); ?>
                                </span>
                            <?php endif; ?>
                            <span
                                class="badge bg-success-subtle text-success-emphasis position-absolute bottom-0 end-0 m-2 badge-stock shadow-sm">
                                คงเหลือ <?php echo (int)$product['stock']; ?>
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column p-2 p-md-3">
                            <h6 class="card-title text-truncate small fw-semibold mb-1">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h6>
                            <p class="text-danger fw-bold fs-6 mb-1">
                                ฿<?php echo number_format($product['price']); ?>
                            </p>
                            <small class="text-muted text-truncate flex-grow-1 mb-2">
                                <?php echo htmlspecialchars($product['description']); ?>
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
        <div class="container py-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill text-bg-danger-subtle text-danger-emphasis">
                        <i class="bi bi-pencil-square me-1"></i> เพิ่มสินค้า
                    </span>
                </div>
                <button type="button" class="btn-close" onclick="closeCartBar()"></button>
            </div>

            <div class="d-flex gap-3 mb-3">
                <div class="rounded-3 overflow-hidden bg-light flex-shrink-0"
                    style="width:80px;height:80px;">
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
                <button type="button" class="btn btn-outline-danger fw-bold rounded-3"
                    onclick="addCurrentToCart()">
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
                        <span
                            class="bg-danger-subtle text-danger-emphasis rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width:32px;height:32px;">
                            <i class="bi bi-bag-check-fill"></i>
                        </span>
                        <span>ตะกร้าสินค้า</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cartItemsContainer"></div>
                </div>
                <div
                    class="modal-footer border-0 d-flex justify-content-between align-items-center pt-0">
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

    <!-- Toast แจ้งเตือนเพิ่มสินค้า -->
    <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1100;">
        <div id="cartToast" class="toast align-items-center text-bg-success border-0 shadow-lg" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body small fw-semibold" id="cartToastBody">
                    เพิ่มสินค้าลงตะกร้าแล้ว
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <nav class="bottom-nav d-md-none">
        <a href="Buyer.php" class="nav-item active" id="nav-home">
            <i class="bi bi-house-door"></i>
            <span>หน้าแรก</span>
        </a>

        <a href="order-history.php" class="nav-item" id="nav-orders">
            <i class="bi bi-receipt"></i>
            <span>ออเดอร์</span>
        </a>

        <a href="notifications.php" class="nav-item" id="nav-noti">
            <i class="bi bi-bell"></i>
            <span>แจ้งเตือน</span>
        </a>

        <a href="profile.php" class="nav-item" id="nav-me">
            <i class="bi bi-person"></i>
            <span>ฉัน</span>
        </a>
    </nav>

    <!-- SCRIPTS -->
    <script>
        let selectedProduct = null;
        let selectedVariant = null;

        const initialCart = <?php echo json_encode($cart_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || [];
        let cart = Array.isArray(initialCart) ?
            initialCart.map(it => ({
                product_id: it.product_id,
                // ถ้าในฐานข้อมูลเป็น 0 แต่เราอยากถือว่า "ไม่มี variant" ให้แปลงเป็น null
                variant_id: (it.variant_id ? it.variant_id : null),
                name: it.name,
                image: it.image,
                price: Number(it.price || 0),
                quantity: Number(it.quantity || 0),
            })) : [];

        let cartModal = null;
        let cartToast = null;

        function getMaxStockForCurrent() {
            if (!selectedProduct) return Infinity;

            // ถ้าเลือก variant อยู่ ใช้ stock ของ variant
            if (selectedVariant && selectedVariant.stock != null) {
                return Number(selectedVariant.stock) || 0;
            }

            // ถ้าไม่มี variant ใช้ stock ของตัว product
            return Number(selectedProduct.stock ?? 0);
        }


        document.addEventListener('DOMContentLoaded', () => {

            // ✅ คลิกที่การ์ดเพื่อไปหน้ารายละเอียด
            document.querySelectorAll('.product-card.clickable-card').forEach(card => {
                card.addEventListener('click', () => {
                    const href = card.dataset.href;
                    if (href) window.location.href = href;
                });
            });

            const navSearchInput = document.getElementById('searchInput');
            if (navSearchInput) {
                navSearchInput.addEventListener('focus', () => {
                    window.location.href = 'search.php';
                });
                navSearchInput.addEventListener('click', () => {
                    window.location.href = 'search.php';
                });
            }

            document.querySelectorAll('.open-cart-bar').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectedProduct = JSON.parse(btn.getAttribute('data-product'));
                    openCartBar(selectedProduct);
                });
            });

            // ปุ่ม "เพิ่มตะกร้า" = Quick add + animation บินเข้าตะกร้า
            document.querySelectorAll('.add-cart-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const product = JSON.parse(btn.getAttribute('data-product'));

                    // ถ้ามี variants ให้บังคับเลือกเหมือนเดิม (เปิด cartBar)
                    if (product.variants && product.variants.length > 0) {
                        selectedProduct = product;
                        openCartBar(selectedProduct);
                        return;
                    }

                    // ถ้าไม่มี variant -> เพิ่มลง cart ทันที 1 ชิ้น
                    quickAddToCart(product);

                    // เล่น animation บินเข้าตะกร้า
                    const cardImg = btn.closest('.product-card')
                        .querySelector('.product-img-wrap img');
                    const cartIcon = document.getElementById('cartIcon');
                    if (cardImg && cartIcon) {
                        flyToCart(cardImg, cartIcon);
                    }
                });
            });

            const modalEl = document.getElementById('cartModal');
            if (modalEl) {
                cartModal = new bootstrap.Modal(modalEl);
            }

            const toastEl = document.getElementById('cartToast');
            if (toastEl) {
                cartToast = new bootstrap.Toast(toastEl, {
                    delay: 2000
                });
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

            const qtyInput = document.getElementById('quantity');
            qtyInput.addEventListener('change', () => {
                let val = parseInt(qtyInput.value) || 1;
                const maxStock = getMaxStockForCurrent();
                if (val < 1) val = 1;
                if (maxStock > 0 && val > maxStock) {
                    val = maxStock;
                    alert('มีสินค้าในสต็อกสูงสุด ' + maxStock + ' ชิ้น');
                }
                qtyInput.value = val;
            });
        });

        function flyToCart(sourceImgEl, cartIconEl) {
            const imgRect = sourceImgEl.getBoundingClientRect();
            const cartRect = cartIconEl.getBoundingClientRect();

            // clone รูป
            const flyImg = sourceImgEl.cloneNode(true);
            flyImg.classList.add('fly-img');
            document.body.appendChild(flyImg);

            // จุดเริ่มต้น (ที่รูปจริงอยู่)
            flyImg.style.left = imgRect.left + 'px';
            flyImg.style.top = imgRect.top + 'px';
            flyImg.style.transform = 'translate(0, 0)';
            flyImg.style.opacity = '1';

            // บังคับให้ browser คำนวณ layout ก่อน transition
            requestAnimationFrame(() => {
                const deltaX = cartRect.left + cartRect.width / 2 - (imgRect.left + imgRect.width / 2);
                const deltaY = cartRect.top + cartRect.height / 2 - (imgRect.top + imgRect.height / 2);

                flyImg.style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.3)`;
                flyImg.style.opacity = '0';
            });

            flyImg.addEventListener('transitionend', () => {
                flyImg.remove();

                // แถม effect กระดิก icon ตะกร้านิดหน่อย
                cartIconEl.classList.add('shake-cart');
                setTimeout(() => cartIconEl.classList.remove('shake-cart'), 300);
            }, {
                once: true
            });
        }

        function showCartToast(message) {
            const bodyEl = document.getElementById('cartToastBody');
            if (!cartToast || !bodyEl) return;

            bodyEl.textContent = message;
            cartToast.show();
        }


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
                            image: newImage,
                            stock: variant.stock
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
            let value = parseInt(input.value) || 1;

            const maxStock = getMaxStockForCurrent();

            value += change;
            if (value < 1) value = 1;

            if (maxStock > 0 && value > maxStock) {
                value = maxStock;
                alert('มีสินค้าในสต็อกสูงสุด ' + maxStock + ' ชิ้น');
            }

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

            const maxStock = getMaxStockForCurrent();
            const inCartQty = existing ? existing.quantity : 0;
            const newTotal = inCartQty + qty;

            if (maxStock > 0 && newTotal > maxStock) {
                const canAdd = maxStock - inCartQty;
                if (canAdd <= 0) {
                    alert('คุณมีสินค้านี้ในตะกร้าครบจำนวนสต็อกแล้ว (' + maxStock + ' ชิ้น)');
                } else {
                    alert(
                        'มีสินค้าในสต็อก ' + maxStock +
                        ' ชิ้น ตอนนี้ในตะกร้าคุณมี ' + inCartQty +
                        ' ชิ้น สามารถเพิ่มได้อีกสูงสุด ' + canAdd + ' ชิ้น'
                    );
                }
                return;
            }

            if (existing) {
                existing.quantity = newTotal;
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
            showCartToast('เพิ่มสินค้าลงตะกร้าแล้ว');
            syncCartToServer();
        }

        function quickAddToCart(base) {
            const qty = 1; // เพิ่มทีละ 1 ชิ้น
            const productId = base.id;
            const variantId = null;
            const price = Number(base.price);
            const name = base.name;
            const image = base.image;

            const existing = cart.find(
                item => item.product_id == productId && item.variant_id == variantId
            );

            const maxStock = Number(base.stock ?? 0);
            const inCartQty = existing ? existing.quantity : 0;
            const newTotal = inCartQty + qty;

            if (maxStock > 0 && newTotal > maxStock) {
                if (inCartQty >= maxStock) {
                    alert('คุณมีสินค้านี้ในตะกร้าครบจำนวนสต็อกแล้ว (' + maxStock + ' ชิ้น)');
                } else {
                    alert(
                        'มีสินค้าในสต็อก ' + maxStock +
                        ' ชิ้น ตอนนี้ในตะกร้าคุณมี ' + inCartQty +
                        ' ชิ้น สามารถเพิ่มได้อีกสูงสุด ' + (maxStock - inCartQty) + ' ชิ้น'
                    );
                }
                return;
            }

            if (existing) {
                existing.quantity = newTotal;
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
            if (!selectedProduct) return;

            const qty = parseInt(document.getElementById('quantity').value) || 1;
            const maxStock = getMaxStockForCurrent();

            if (maxStock > 0 && qty > maxStock) {
                alert('เลือกจำนวนเกินสต็อก (มีแค่ ' + maxStock + ' ชิ้น)');
                return;
            }

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

        // function searchCategory(e) {
        //     e.preventDefault();
        //     const keyword = document.getElementById('searchInput').value.trim().toLowerCase();
        //     const products = document.querySelectorAll('.product-item');

        //     let found = false;

        //     products.forEach(product => {
        //         const productName = product.querySelector('.card-title').textContent.toLowerCase();
        //         const match = productName.includes(keyword);

        //         if (match) {
        //             product.style.display = 'block';
        //             found = true;
        //         } else {
        //             product.style.display = 'none';
        //         }
        //     });

        //     if (!found) {
        //         alert('ไม่พบสินค้าที่ค้นหา: ' + keyword);
        //     }
        // }

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