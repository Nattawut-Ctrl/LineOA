<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once UTILS_PATH . '/image_helper.php';
require_once UTILS_PATH . '/product_image_helper.php';
require_once UTILS_PATH . '/stock_helper.php';

require_once SERVICES_PATH . '/productService.php';
require_once SERVICES_PATH . '/cartService.php';
require_once SERVICES_PATH . '/userService.php';
require_once FRONTEND_PATH . '/services/AddressService.php';
require_once SHARED_PARTIALS_PATH . '/sweetalert.php';

$conn    = connectDBWithLog();
$user_id = require_user_id();

// ----------------------- Fetch Pending Orders -----------------------
$pendingOrderCount = 0;

$sql = "
    SELECT COUNT(*) AS c
    FROM payment_intents
    WHERE user_id = ?
      AND status = 'active'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$pendingOrderCount = (int)$row['c'];

// ----------------------- Fetch User Info via Service -----------------------
$user = getUserById($conn, $user_id);
if (!$user) {
    unset($_SESSION['user_id']);
    header("Location: " . FRONTEND_URL . "/pages/users/line-entry.php?from=register");
    exit;
}

// ----------------------- Fetch Addresses (for checkout validation) -----------------------
$addresses = getUserAddresses($conn, $user_id);

// ----------------------- Fetch Products + Variants via Service -----------------------
$productsAssoc = getAllProductsWithVariants($conn);
$products      = array_values($productsAssoc);

foreach ($products as &$p) {
    $p['image'] = getProductMainImageUrl($conn, (int)$p['id']);

    if (!empty($p['variants'])) {
        foreach ($p['variants'] as &$v) {
            if (!empty($v['image'])) {
                $v['image'] = buildImageUrl($v['image']);
            } else {
                $v['image'] = $p['image'];
            }
        }
        unset($v);
    }
}
unset($p);

// ----------------------- Fetch Categories via Service -----------------------
$categories = array_merge(['ทั้งหมด'], getAllCategories($conn));

// ----------------------- Fetch Cart Items via Service (shape same as old SQL) -----------------------
$cart_items = getCartItems($conn, $user_id);

foreach ($cart_items as &$item) {
    if (!empty($item['product_id'])) {
        $item['image'] = getProductMainImageUrl($conn, (int)$item['product_id']);
    } else {
        $item['image'] = buildImageUrl($item['image'] ?? '');
    }
}
unset($item);

$activeMenu = 'main';

// ------------------------ View Part ------------------------
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line-Shop</title>
    <?php require_once SHARED_PARTIALS_PATH . '/bootstrap.php'; ?>
    <?php require_once SHARED_PARTIALS_PATH . '/sweetalert.php'; ?>

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
            z-index: 2100 !important;
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
            font-size: 0.75rem;
            font-weight: 700;
            padding: 6px 10px !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(4px);
            letter-spacing: 0.5px;
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

        .swal2-container {
            z-index: 3000 !important;
        }

        .buyer-main-padding {
            padding-bottom: 8rem;
        }

        @media (min-width: 768px) {
            .buyer-main-padding {
                padding-bottom: 2rem;
            }
        }

        body {
            padding-bottom: 30px;
        }

        @media (max-width: 767.98px) {
            body {
                padding-bottom: 3rem;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <?php include FRONTEND_PATH . '/partials/buyer_main_navbar.php'; ?>

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
                <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-bs-toggle="collapse"
                    data-bs-target="#categoryCollapse">
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
    <main class="container py-4 buyer-main-padding">
        <div class="row g-3 g-md-4" id="product-list">
            <?php foreach ($products as $product): ?>

                <?php
                $imgPath = $product['image'] ?? '';

                if ($imgPath !== '' && !preg_match('#^https?://#', $imgPath)) {
                    while (strpos($imgPath, '../') === 0) {
                        $imgPath = substr($imgPath, 3);
                    }

                    $imgPath = rtrim(BASE_URL, '/') . '/' . ltrim($imgPath, '/');
                }

                $availableStock = getAvailableStock($conn, (int)$product['id'], null);

                $productForJs          = $product;
                $productForJs['image'] = $imgPath;
                $productForJs['available_stock'] = $availableStock;

                $btnDisabled = $availableStock <= 0 ? 'disabled aria-disabled="true"' : '';
                ?>

                <div class="col-6 col-md-4 col-lg-3 product-item mb-2 mb-md-3"
                    data-category="<?php echo $product['category']; ?>">

                    <div class="card product-card clickable-card h-100 border-0 shadow-sm bg-white"
                        data-href="product-detail.php?id=<?php echo (int)$product['id']; ?>">

                        <div class="position-relative product-img-wrap rounded-top-4 overflow-hidden">
                            <?php $img = buildImageUrl($product['image'] ?? ''); ?>
                            <img src="<?= htmlspecialchars($img) ?>" class="card-img-top w-100 h-100"
                                alt="<?= htmlspecialchars($product['name']); ?>" loading="lazy">

                            <?php if (!empty($product['category'])): ?>
                                <span
                                    class="badge text-bg-light position-absolute top-0 start-0 m-2 rounded-pill shadow-sm small">
                                    <i class="bi bi-tag me-1 text-danger"></i>
                                    <?= htmlspecialchars($product['category']); ?>
                                </span>
                            <?php endif; ?>

                            <span class="badge position-absolute bottom-0 end-0 m-2 badge-stock shadow-sm
                                   <?php echo $availableStock <= 0
                                        ? 'bg-secondary text-light'
                                        : 'bg-success text-white'; ?>">
                                <?php if ($availableStock <= 0): ?>
                                    <i class="bi bi-exclamation-circle me-1"></i>หมดชั่วคราว
                                <?php else: ?>
                                    <i class="bi bi-check-circle-fill me-1"></i>คงเหลือ <?= $availableStock; ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="card-body d-flex flex-column p-2 p-md-3">
                            <h6 class="card-title text-truncate small fw-semibold mb-1">
                                <?= htmlspecialchars($product['name']); ?>
                            </h6>
                            <p class="text-danger fw-bold fs-6 mb-1">
                                ฿
                                <?= number_format($product['price']); ?>
                            </p>
                            <small class="text-muted text-truncate flex-grow-1 mb-2">
                                <?= htmlspecialchars($product['description']); ?>
                            </small>
                            <div class="d-grid gap-1 mt-1">
                                <!-- ใช้ open-cart-bar แทนได้ -->
                                <button class="btn btn-sm btn-outline-danger fw-semibold rounded-3 open-cart-bar"
                                    data-mode="add"
                                    data-product='<?= json_encode($productForJs, JSON_UNESCAPED_UNICODE); ?>'>
                                    <i class="bi bi-cart-plus me-1"></i> เพิ่มตะกร้า
                                </button>
                                <button class="btn btn-sm fw-semibold rounded-3 text-white open-cart-bar"
                                    style="background: linear-gradient(135deg, #ff7043, #ff9800);"
                                    data-mode="buy"
                                    data-product='<?= json_encode($productForJs, JSON_UNESCAPED_UNICODE); ?>'>
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
    <div class="position-fixed bottom-0 start-0 end-0 bg-white border-top border-3" id="cartBar"
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
                <div class="rounded-3 overflow-hidden bg-light flex-shrink-0" style="width:80px;height:80px;">
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
                    <button type="button" id="btnQtyMinus" class="btn btn-outline-secondary btn-sm rounded-circle fw-bold"
                        style="width: 36px; height: 36px;" onclick="changeQuantity(-1)">−</button>
                    <input type="number" id="quantity" value="1" min="1" class="form-control text-center fw-bold"
                        style="width: 80px;">
                    <button type="button" id="btnQtyPlus" class="btn btn-outline-secondary btn-sm rounded-circle fw-bold"
                        style="width: 36px; height: 36px;" onclick="changeQuantity(1)">+</button>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="button" id="btnAddToCartBar" class="btn btn-outline-danger fw-bold rounded-3" onclick="addCurrentToCart()">
                    <i class="bi bi-cart-plus me-1"></i> เพิ่มลงตะกร้า
                </button>
                <button type="button" id="btnBuyNowBar" class="btn fw-bold rounded-3 text-white"
                    style="background: linear-gradient(135deg, #ee4d2d, #ff7043);" onclick="confirmPurchase()">
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
                    <button type="button" class="btn fw-bold text-white rounded-3 px-4"
                        style="background: linear-gradient(135deg, #ff7043, #ff9800);" id="goPaymentBtn">
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
    <?php include FRONTEND_PATH . '/partials/buyer_bottom_nav.php'; ?>


    <!-- SCRIPTS -->
    <script>
        let selectedProduct = null;
        let selectedVariant = null;

        const initialCart = <?php echo json_encode($cart_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || [];
        let cart = Array.isArray(initialCart) ?
            initialCart.map(it => ({
                product_id: it.product_id,
                variant_id: (it.variant_id ? it.variant_id : null),
                name: it.name,
                image: it.image,
                price: Number(it.price || 0),
                quantity: Number(it.quantity || 0),
            })) : [];

        let cartModal = null;
        let cartToast = null;

        document.getElementById("nav-home")?.addEventListener("click", (e) => {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });

        function getMaxStockForCurrent() {
            if (!selectedProduct) return Infinity;

            if (selectedVariant) {
                if (selectedVariant.available_stock != null) {
                    return Number(selectedVariant.available_stock) || 0;
                }
                if (selectedProduct.stock != null) {
                    return Number(selectedProduct.stock) || 0;
                }
            }

            if (selectedProduct.available_stock != null) {
                return Number(selectedProduct.available_stock) || 0;
            }

            return Number(selectedProduct.stock ?? 0);
        }

        function updateCartBarControls(available) {
            const qtyInput = document.getElementById('quantity');
            const btnMinus = document.getElementById('btnQtyMinus');
            const btnPlus = document.getElementById('btnQtyPlus');
            const btnAdd = document.getElementById('btnAddToCartBar');
            const btnBuy = document.getElementById('btnBuyNowBar');
            const stockEl = document.getElementById('stockInfo');

            available = Number(available || 0);

            if (available <= 0) {
                if (qtyInput) {
                    qtyInput.value = 0;
                    qtyInput.disabled = true;
                }
                if (btnMinus) btnMinus.disabled = true;
                if (btnPlus) btnPlus.disabled = true;
                if (btnAdd) btnAdd.disabled = true;
                if (btnBuy) btnBuy.disabled = true;

                if (stockEl) stockEl.textContent = 'สินค้าหมด';
            } else {
                if (qtyInput) {
                    if (Number(qtyInput.value) <= 0) qtyInput.value = 1;
                    qtyInput.disabled = false;
                }
                if (btnMinus) btnMinus.disabled = false;
                if (btnPlus) btnPlus.disabled = false;
                if (btnAdd) btnAdd.disabled = false;
                if (btnBuy) btnBuy.disabled = false;

                if (stockEl) stockEl.textContent = available;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {

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

                    const mode = btn.getAttribute('data-mode') || 'add';
                    selectedProduct = JSON.parse(btn.getAttribute('data-product'));

                    openCartBar(selectedProduct, mode);
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
                    Swal.fire({
                        title: 'ตะกร้าว่างเปล่า',
                        text: 'กรุณาเพิ่มสินค้าลงตะกร้าก่อน',
                        icon: 'warning',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }

                const hasAddress = <?php echo !empty($addresses) ? 'true' : 'false'; ?>;
                if (!hasAddress) {
                    Swal.fire({
                        title: 'ยังไม่มีที่อยู่จัดส่ง',
                        text: 'กรุณาเพิ่มที่อยู่ก่อนสั่งซื้อสินค้า',
                        icon: 'info',
                        confirmButtonText: 'ไปเพิ่มที่อยู่',
                        showCancelButton: true,
                        cancelButtonText: 'ยกเลิก',
                        didOpen: function() {
                            // ตั้ง z-index สูงที่สุด
                            const modal = document.querySelector('.swal2-container');
                            if (modal) modal.style.zIndex = '9999';
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'buyer_address_form.php?return_to=' + encodeURIComponent(window.location.pathname + window.location.search) + '&back_to=' + encodeURIComponent(window.location.pathname + window.location.search);
                        }
                    });
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

                // force_new flag: start a fresh intent even if an active intent exists in session
                const force = document.createElement('input');
                force.type = 'hidden';
                force.name = 'force_new';
                force.value = '1';
                form.appendChild(force);

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
                    // alert('มีสินค้าในสต็อกสูงสุด ' + maxStock + ' ชิ้น');
                }
                qtyInput.value = val;
            });
        });

        function flyToCart(sourceImgEl, cartIconEl) {
            const imgRect = sourceImgEl.getBoundingClientRect();
            const cartRect = cartIconEl.getBoundingClientRect();

            const flyImg = sourceImgEl.cloneNode(true);
            flyImg.classList.add('fly-img');
            document.body.appendChild(flyImg);

            flyImg.style.left = imgRect.left + 'px';
            flyImg.style.top = imgRect.top + 'px';
            flyImg.style.transform = 'translate(0, 0)';
            flyImg.style.opacity = '1';

            requestAnimationFrame(() => {
                const deltaX = cartRect.left + cartRect.width / 2 - (imgRect.left + imgRect.width / 2);
                const deltaY = cartRect.top + cartRect.height / 2 - (imgRect.top + imgRect.height / 2);

                flyImg.style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.3)`;
                flyImg.style.opacity = '0';
            });

            flyImg.addEventListener('transitionend', () => {
                flyImg.remove();

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

        let cartMode = 'add'; // 'add' | 'buy'

        function updateCartBarButtons() {
            const addBtn = document.getElementById('btnAddToCartBar');
            const buyBtn = document.getElementById('btnBuyNowBar');
            if (!addBtn || !buyBtn) return;

            if (cartMode === 'buy') {
                addBtn.classList.add('d-none');
                buyBtn.classList.remove('d-none');
            } else {
                buyBtn.classList.add('d-none');
                addBtn.classList.remove('d-none');
            }
        }

        function openCartBar(product, mode) {
            document.body.classList.add('cartbar-open');

            cartMode = mode || 'add';
            updateCartBarButtons();

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
            const available = (product.available_stock != null) ?
                Number(product.available_stock) :
                Number(product.stock ?? 0);

            updateCartBarControls(available);

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

                        const vAvailable = (variant.available_stock != null) ?
                            Number(variant.available_stock) :
                            Number(variant.stock ?? 0);

                        selectedVariant = {
                            id: btn.dataset.id,
                            name: btn.dataset.name,
                            price: newPrice,
                            image: newImage,
                            stock: variant.stock,
                            available_stock: vAvailable
                        };

                        updateCartBarControls(vAvailable);
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
            document.body.classList.remove('cartbar-open');
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
                // alert('มีสินค้าในสต็อกสูงสุด ' + maxStock + ' ชิ้น');
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
                    SA.warning('คำเตือน', 'มีสินค้าในสต็อก ' + maxStock + ' ชิ้น ตอนนี้ในตะกร้าคุณมี ' + inCartQty + ' ชิ้น');
                    // alert(
                    //     'มีสินค้าในสต็อก ' + maxStock +
                    //     ' ชิ้น ตอนนี้ในตะกร้าคุณมี ' + inCartQty +
                    //     ' ชิ้น สามารถเพิ่มได้อีกสูงสุด ' + canAdd + ' ชิ้น'
                    // );
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

            const maxStock = Number(
                base.available_stock != null ? base.available_stock : (base.stock ?? 0)
            );
            const inCartQty = existing ? existing.quantity : 0;
            const newTotal = inCartQty + qty;

            if (maxStock > 0 && newTotal > maxStock) {
                if (inCartQty >= maxStock) {
                    alert('คุณมีสินค้านี้ในตะกร้าครบจำนวนสต็อกแล้ว (' + maxStock + ' ชิ้น)');
                } else {
                    SA.warning('คำเตือน', 'มีสินค้าในสต็อก ' + maxStock + ' ชิ้น ตอนนี้ในตะกร้าคุณมี ' + inCartQty + ' ชิ้น');
                    // alert(
                    //     'มีสินค้าในสต็อก ' + maxStock +
                    //     ' ชิ้น ตอนนี้ในตะกร้าคุณมี ' + inCartQty +
                    //     ' ชิ้น สามารถเพิ่มได้อีกสูงสุด ' + (maxStock - inCartQty) + ' ชิ้น'
                    // );
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
                Swal.fire({
                    title: 'จำนวนเกินสต็อก',
                    text: 'มีสินค้าแค่ ' + maxStock + ' ชิ้น',
                    icon: 'warning',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            // ✅ ตรวจสอบว่ามีที่อยู่หรือไม่
            const hasAddress = <?php echo !empty($addresses) ? 'true' : 'false'; ?>;
            if (!hasAddress) {
                Swal.fire({
                    title: 'ยังไม่มีที่อยู่จัดส่ง',
                    text: 'กรุณาเพิ่มที่อยู่ก่อนสั่งซื้อสินค้า',
                    icon: 'info',
                    confirmButtonText: 'ไปเพิ่มที่อยู่',
                    showCancelButton: true,
                    cancelButtonText: 'ยกเลิก',
                    didOpen: function() {
                        // ตั้ง z-index สูงที่สุด
                        const modal = document.querySelector('.swal2-container');
                        if (modal) modal.style.zIndex = '9999';
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'buyer_address_form.php?return_to=' + encodeURIComponent(window.location.pathname + window.location.search) + '&back_to=' + encodeURIComponent(window.location.pathname + window.location.search);
                    }
                });
                return;
            }

            const product = selectedProduct;

            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'payment.php';

            const fields = {
                mode: 'single',
                product_id: product.id,
                // product_name: product.name,
                quantity: qty,
            };

            if (selectedVariant) {
                fields.variant_id = selectedVariant.id;
                // fields.variant_name = selectedVariant.name;
                // fields.variant_image = selectedVariant.image;
            }

            // force_new flag: ensure user starts a new intent rather than reusing session's active intent
            const force = document.createElement('input');
            force.type = 'hidden';
            force.name = 'force_new';
            force.value = '1';
            form.appendChild(force);

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

        function syncCartToServer() {

            fetch("<?= FRONTEND_URL ?>/api/buyer/save_cart.php", {
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

        const pendingOrderCount = <?= $pendingOrderCount ?>;
        const orderBadge = document.getElementById("orderBadge");

        if (orderBadge && pendingOrderCount > 0) {
            // orderBadge.textContent = pendingOrderCount;
            orderBadge.textContent = '';
            orderBadge.classList.remove("d-none");
        }

        // Start shared notification polling if available, otherwise run a single check as fallback
        if (window.BuyerNoti && typeof window.BuyerNoti.startPolling === 'function') {
            window.BuyerNoti.startPolling();
        } else {
            fetch('<?= FRONTEND_URL ?>/api/buyer/check_buyer_notifications.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById("notiBadge");
                    if (!badge) return;

                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.classList.remove("d-none");
                    } else {
                        badge.classList.add("d-none");
                    }
                })
                .catch(err => console.error("checkNotifications error:", err));
        }
    </script>
</body>

</html>