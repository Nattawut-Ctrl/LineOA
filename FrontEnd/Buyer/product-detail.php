<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once UTILS_PATH . '/image_helper.php';

require_once SERVICES_PATH . '/productService.php';
require_once SERVICES_PATH . '/cartService.php';
require_once SERVICES_PATH . '/userService.php';

$conn    = connectDBWithLog();
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
  header("Location: ../Users/line-entry.php?from=shop");
  exit;
}

$user = getUserById($conn, $user_id);
if (!$user) {
  unset($_SESSION['user_id']);
  header("Location: ../Users/line-entry.php?from=register");
  exit;
}

// ───────────────────── สินค้าหลัก ─────────────────────
$pid     = (int)($_GET['id'] ?? 0);
$product = $pid > 0 ? getProductByIdWithVariants($conn, $pid) : null;

if (!$product) {
  http_response_code(404);
  echo "ไม่พบสินค้า";
  exit;
}

$variants   = $product['variants'] ?? [];
$cart_items = getCartItems($conn, $user_id);
// ปรับ path รูปของสินค้าหลักและตัวเลือกให้เป็น URL เต็ม ปลอดภัยบนทุก OS/เบราว์เซอร์
$product['image'] = buildImageUrl($product['image'] ?? '');

foreach ($variants as &$v) {
  if (!empty($v['image'])) {
    $v['image'] = buildImageUrl($v['image']);
  } else {
    // ถ้า variant ไม่มีรูป ให้ใช้รูปหลักของสินค้า
    $v['image'] = $product['image'];
  }
}
unset($v);


// ───────────────────── สินค้าแนะนำ ─────────────────────
// ใช้ getAllProductsWithVariants() ที่มีอยู่แล้ว
$recommended = [];
try {
  $allProducts = getAllProductsWithVariants($conn); // คืนแบบ [product_id => [...]]
  foreach ($allProducts as $p) {
    if ((int)$p['id'] === (int)$product['id']) {
      continue; // ไม่เอาตัวเดียวกัน
    }

    // ถ้ามี category ให้แนะนำในหมวดเดียวกันก่อน
    if (!empty($product['category']) && !empty($p['category'])) {
      if ($p['category'] !== $product['category']) {
        continue;
      }
    }

    $recommended[] = $p;
    if (count($recommended) >= 8) {
      break;
    }
  }
} catch (Throwable $e) {
  // ถ้ามี error ก็ปล่อย $recommended ว่าง ๆ ไป ไม่ต้องทำให้หน้าเด้ง
}

// ปรับ path รูปของสินค้าแนะนำให้เป็น URL เต็ม
foreach ($recommended as &$rp) {
  if (!empty($rp['image'])) {
    $rp['image'] = buildImageUrl($rp['image']);
  }
}
unset($rp);

?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($product['name']); ?> | Line Shop</title>

  <?php include BASE_PATH . '/partials/bootstrap.php'; ?>

  <style>
    body {
      background: #f6f7fb;
      font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    }

    .topbar {
      background: linear-gradient(90deg, rgba(238, 77, 45, 0.97), rgba(255, 143, 90, 0.97));
    }

    .price {
      color: #ee4d2d;
      font-size: 1.6rem;
      font-weight: 800;
    }

    .variant-chip-main.active,
    .variant-chip-bar.active {
      border-color: #ee4d2d !important;
      color: #ee4d2d;
      background: #fff5f0;
    }

    .sticky-buybar {
      box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    }

    /* Cart Bar (แถบล่าง) */
    #cartBarOverlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.35);
      display: none;
      z-index: 1050;
    }

    #cartBarOverlay.show {
      display: block;
    }

    #cartBar {
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      background: #ffffff;
      border-radius: 16px 16px 0 0;
      box-shadow: 0 -6px 24px rgba(15, 23, 42, 0.35);
      transform: translateY(100%);
      transition: transform 0.22s ease-out;
      z-index: 1060;
    }

    #cartBar.show {
      transform: translateY(0%);
    }

    .cart-thumb {
      width: 80px;
      height: 80px;
      border-radius: 16px;
      object-fit: cover;
    }

    .cart-bar-header {
      border-bottom: 1px solid #f1f1f1;
    }

    .cart-bar-footer {
      border-top: 1px solid #f1f1f1;
    }

    /* ปุ่มขึ้นบนสุด */
    #scrollTopBtn {
      position: fixed;
      right: 16px;
      bottom: 90px;
      z-index: 1040;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      display: none;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    }

    /* การ์ดสินค้าแนะนำ */
    .reco-card {
      border-radius: 18px;
      overflow: hidden;
      border: none;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
      transition: transform 0.15s ease-out, box-shadow 0.15s ease-out;
      cursor: pointer;
    }

    .reco-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
    }

    .reco-img {
      width: 100%;
      height: 140px;
      object-fit: cover;
    }

    .reco-price {
      color: #ee4d2d;
      font-weight: 700;
    }
  </style>
</head>

<body>

  <!-- Topbar -->
  <nav class="navbar topbar navbar-dark sticky-top">
    <div class="container-fluid">
      <a href="Buyer.php" class="navbar-brand d-flex align-items-center gap-2">
        <i class="bi bi-chevron-left"></i>
        <span class="fw-semibold">กลับ</span>
      </a>

      <div class="text-white fw-bold">รายละเอียดสินค้า</div>

      <!-- ปุ่มตะกร้า (กดแล้วเปิด Modal ดูได้) -->
      <button type="button"
        id="openCartBtn"
        class="btn btn-link text-white position-relative text-decoration-none p-0 border-0">
        <i class="bi bi-cart3 fs-4"></i>
        <span id="cartBadge"
          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark d-none">
          0
        </span>
      </button>
    </div>
  </nav>

  <!-- Main -->
  <main class="container my-3 mb-5 pb-5">

    <!-- รูปสินค้า -->
    <div class="bg-white rounded-4 p-2 shadow-sm mb-3">
      <div class="ratio ratio-1x1 rounded-4 overflow-hidden">
        <img id="mainImage"
          src="<?php echo htmlspecialchars($product['image']); ?>"
          class="w-100 h-100 object-fit-cover"
          alt="<?php echo htmlspecialchars($product['name']); ?>">
      </div>
    </div>

    <!-- ชื่อ / ราคา / หมวดหมู่ / stock -->
    <div class="bg-white rounded-4 p-3 shadow-sm mb-3">
      <div class="d-flex align-items-start justify-content-between gap-2">
        <h5 class="fw-bold mb-2">
          <?php echo htmlspecialchars($product['name']); ?>
        </h5>
        <?php if (!empty($product['category'])): ?>
          <span class="badge rounded-pill text-bg-light">
            <i class="bi bi-tag me-1 text-danger"></i>
            <?php echo htmlspecialchars($product['category']); ?>
          </span>
        <?php endif; ?>
      </div>

      <div class="price mb-1" id="priceText">
        ฿<?php echo number_format((float)($product['price'] ?? 0), 2); ?>
      </div>

      <div class="text-muted small">
        คงเหลือ: <span id="stockText"><?php echo (int)($product['stock'] ?? 0); ?></span>
      </div>
    </div>

    <!-- ตัวเลือก (variants) ด้านบน ไม่มีช่องจำนวน -->
    <?php if (!empty($variants)): ?>
      <div class="bg-white rounded-4 p-3 shadow-sm mb-3">
        <div class="fw-semibold mb-2">ตัวเลือกสินค้า</div>
        <div class="d-flex flex-wrap gap-2" id="variantListMain">
          <?php foreach ($variants as $v): ?>
            <button type="button"
              class="btn btn-outline-secondary btn-sm rounded-pill variant-chip-main"
              data-variant='<?php echo json_encode($v, JSON_UNESCAPED_UNICODE); ?>'>
              <?php echo htmlspecialchars($v['variant_name']); ?>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="small text-muted mt-2" id="variantHintMain">กรุณาเลือกตัวเลือก</div>
      </div>
    <?php endif; ?>

    <!-- รายละเอียด -->
    <div class="bg-white rounded-4 p-3 shadow-sm mb-3">
      <div class="fw-semibold mb-2">รายละเอียดสินค้า</div>
      <div class="text-muted" style="white-space:pre-line;">
        <?php echo htmlspecialchars($product['description'] ?? ''); ?>
      </div>
    </div>

    <!-- สินค้าแนะนำ -->
    <?php if (!empty($recommended)): ?>
      <div class="bg-white rounded-4 p-3 shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="fw-semibold mb-0">สินค้าแนะนำสำหรับคุณ</h6>
          <span class="small text-muted">ดูเพิ่มเติมในหน้าร้าน</span>
        </div>
        <div class="row g-3">
          <?php foreach ($recommended as $rp): ?>
            <div class="col-6 col-md-3">
              <div class="card reco-card h-100"
                onclick="window.location.href='product-detail.php?id=<?php echo (int)$rp['id']; ?>'">
                <img src="<?php echo htmlspecialchars($rp['image']); ?>"
                  class="reco-img"
                  alt="<?php echo htmlspecialchars($rp['name']); ?>">
                <div class="card-body p-2">
                  <div class="small text-truncate">
                    <?php echo htmlspecialchars($rp['name']); ?>
                  </div>
                  <div class="reco-price mt-1">
                    ฿<?php echo number_format((float)($rp['price'] ?? 0), 2); ?>
                  </div>
                  <div class="small text-muted">
                    คงเหลือ <?php echo (int)($rp['stock'] ?? 0); ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </main>

  <!-- Buy bar (ปุ่มดำเนินการ) -->
  <div class="position-fixed bottom-0 start-0 end-0 bg-white p-2 sticky-buybar">
    <div class="container d-flex gap-2">
      <button id="addCartBtn" class="btn btn-outline-danger w-50 fw-semibold rounded-3">
        <i class="bi bi-cart-plus me-1"></i> เพิ่มตะกร้า
      </button>
      <button id="buyNowBtn" class="btn btn-danger w-50 fw-semibold rounded-3">
        <i class="bi bi-lightning-charge me-1"></i> ซื้อเลย
      </button>
    </div>
  </div>

  <!-- ปุ่มลอยขึ้นบนสุด -->
  <button id="scrollTopBtn" class="btn btn-danger d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short fs-4"></i>
  </button>

  <!-- Overlay มืดด้านหลัง Cart Bar -->
  <div id="cartBarOverlay"></div>

  <!-- Cart Bar (แถบล่าง) -->
  <div id="cartBar" class="p-3">
    <!-- Header -->
    <div class="cart-bar-header d-flex align-items-center gap-3 pb-3">
      <img id="cartThumb" class="cart-thumb"
        src="<?php echo htmlspecialchars($product['image']); ?>"
        alt="<?php echo htmlspecialchars($product['name']); ?>">
      <div class="flex-grow-1">
        <div class="small text-muted mb-1">กำลังสั่งซื้อ</div>
        <div class="fw-semibold text-truncate">
          <?php echo htmlspecialchars($product['name']); ?>
        </div>
        <div class="price mt-1 mb-0" id="cartPriceText">
          ฿<?php echo number_format((float)($product['price'] ?? 0), 2); ?>
        </div>
      </div>
      <button type="button"
        class="btn btn-sm btn-outline-secondary rounded-circle"
        onclick="closeCartBar()">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- ตัวเลือกใน Cart Bar -->
    <?php if (!empty($variants)): ?>
      <div class="py-3 border-bottom">
        <div class="small text-muted mb-2">ตัวเลือกสินค้า</div>
        <div class="d-flex flex-wrap gap-2" id="variantListBar">
          <!-- จะถูก render ด้วย JS -->
        </div>
        <div class="small text-muted mt-2" id="variantHintBar">
          กรุณาเลือกตัวเลือก
        </div>
      </div>
    <?php endif; ?>

    <!-- จำนวน -->
    <div class="py-3 cart-bar-footer d-flex justify-content-between align-items-center">
      <div class="small text-muted">
        จำนวน<br>
        <span>คงเหลือ: <span id="cartStockText"><?php echo (int)($product['stock'] ?? 0); ?></span></span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm rounded-circle fw-bold"
          style="width:36px;height:36px;" onclick="changeCartQty(-1)">−</button>
        <input id="cartQtyInput" type="number" class="form-control text-center"
          style="width:90px;" min="1" value="1">
        <button class="btn btn-outline-secondary btn-sm rounded-circle fw-bold"
          style="width:36px;height:36px;" onclick="changeCartQty(1)">+</button>
      </div>
    </div>

    <!-- ปุ่มยืนยัน (แสดงทีละปุ่ม ตามโหมด) -->
    <div class="pt-2">
      <div class="d-flex gap-2" id="cartBarButtons">
        <!-- กรณีกดจาก "เพิ่มตะกร้า" -->
        <button id="cartAddConfirmBtn" class="btn btn-outline-danger flex-fill fw-semibold d-none">
          <i class="bi bi-cart-plus me-1"></i> เพิ่มลงตะกร้า
        </button>
        <!-- กรณีกดจาก "ซื้อเลย" -->
        <button id="cartBuyConfirmBtn" class="btn btn-danger flex-fill fw-semibold d-none">
          <i class="bi bi-lightning-charge me-1"></i> ซื้อเลย
        </button>
      </div>
    </div>
  </div>

  <!-- Cart Modal (เหมือน Buyer.php) -->
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


  <!-- Toast แจ้งเตือน -->
  <div class="position-fixed bottom-50 start-50 translate-middle bg-dark text-white px-3 py-2 rounded-3 small d-none"
    id="cartToast">
    เพิ่มลงตะกร้าเรียบร้อยแล้ว
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const product = <?php echo json_encode($product, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const variants = product.variants || [];

    const initialCart = <?php echo json_encode($cart_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> || [];
    let cart = Array.isArray(initialCart) ?
      initialCart.map(it => ({
        product_id: it.product_id,
        // ถ้าเป็น 0 ให้ถือว่าไม่มี variant → แปลงเป็น null
        variant_id: it.variant_id ? it.variant_id : null,
        name: it.name,
        image: it.image,
        price: Number(it.price || 0),
        quantity: Number(it.quantity || 0)
      })) : [];

    let selectedVariant = null;
    let cartQty = 1;
    let cartMode = 'add'; // 'add' หรือ 'buy'

    const mainImageEl = document.getElementById('mainImage');
    const priceTextEl = document.getElementById('priceText');
    const stockTextEl = document.getElementById('stockText');
    const cartThumbEl = document.getElementById('cartThumb');
    const cartPriceTextEl = document.getElementById('cartPriceText');
    const cartStockTextEl = document.getElementById('cartStockText');
    const cartQtyInputEl = document.getElementById('cartQtyInput');
    const cartBarEl = document.getElementById('cartBar');
    const cartBarOverlayEl = document.getElementById('cartBarOverlay');
    const cartToastEl = document.getElementById('cartToast');
    const cartBadgeEl = document.getElementById('cartBadge');
    const variantListBarEl = document.getElementById('variantListBar');
    const variantHintBarEl = document.getElementById('variantHintBar');
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    const cartAddConfirmBtn = document.getElementById('cartAddConfirmBtn');
    const cartBuyConfirmBtn = document.getElementById('cartBuyConfirmBtn');

    const openCartBtnEl = document.getElementById('openCartBtn');
    const cartModalEl = document.getElementById('cartModal');
    let cartModalInstance = null;

    // ───────────────────── Helpers ─────────────────────
    function formatPrice(p) {
      return '฿' + Number(p || 0).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    function getCurrentPrice() {
      if (selectedVariant && selectedVariant.price != null) {
        return selectedVariant.price;
      }
      return product.price || 0;
    }

    function getCurrentStock() {
      if (selectedVariant && selectedVariant.stock != null) {
        return selectedVariant.stock;
      }
      return product.stock || 0;
    }

    function updatePriceAndStockUI() {
      const price = getCurrentPrice();
      const stock = getCurrentStock();

      priceTextEl.textContent = formatPrice(price);
      cartPriceTextEl.textContent = formatPrice(price);

      stockTextEl.textContent = stock;
      cartStockTextEl.textContent = stock;

      // รูป
      const img = (selectedVariant && selectedVariant.image) ? selectedVariant.image : product.image;
      if (img) {
        mainImageEl.src = img;
        cartThumbEl.src = img;
      }

      // จำกัดจำนวนไม่เกินสต็อก
      if (stock > 0 && cartQty > stock) {
        cartQty = stock;
        cartQtyInputEl.value = cartQty;
      }
    }

    function updateCartBadge() {
      const totalQty = (cart || []).reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
      if (totalQty > 0) {
        cartBadgeEl.textContent = totalQty;
        cartBadgeEl.classList.remove('d-none');
      } else {
        cartBadgeEl.classList.add('d-none');
      }
    }

    function showToast(message) {
      cartToastEl.textContent = message || 'ดำเนินการสำเร็จ';
      cartToastEl.classList.remove('d-none');
      setTimeout(() => {
        cartToastEl.classList.add('d-none');
      }, 2000);
    }

    // ───────────────────── Variant ด้านบน ─────────────────────
    document.querySelectorAll('.variant-chip-main').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.variant-chip-main')
          .forEach(b => b.classList.remove('active'));

        btn.classList.add('active');
        selectedVariant = JSON.parse(btn.dataset.variant);

        const hintMain = document.getElementById('variantHintMain');
        if (hintMain) {
          hintMain.textContent = 'เลือกแล้ว: ' + (selectedVariant.variant_name || '');
        }

        syncVariantActiveInBar();
        updatePriceAndStockUI();
      });
    });

    // ───────────────────── Cart Bar ─────────────────────
    function renderVariantsInBar() {
      if (!variantListBarEl) return;
      variantListBarEl.innerHTML = '';

      if (!variants.length) return;

      variants.forEach(v => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary btn-sm rounded-pill variant-chip-bar';
        btn.textContent = v.variant_name || '';
        btn.dataset.variant = JSON.stringify(v);

        if (selectedVariant && String(selectedVariant.id) === String(v.id)) {
          btn.classList.add('active');
        }

        btn.addEventListener('click', () => {
          document.querySelectorAll('.variant-chip-bar')
            .forEach(b => b.classList.remove('active'));

          btn.classList.add('active');
          selectedVariant = JSON.parse(btn.dataset.variant);

          if (variantHintBarEl) {
            variantHintBarEl.textContent = 'เลือกแล้ว: ' + (selectedVariant.variant_name || '');
          }
          syncVariantActiveInMain();
          updatePriceAndStockUI();
        });

        variantListBarEl.appendChild(btn);
      });

      if (variantHintBarEl) {
        if (selectedVariant) {
          variantHintBarEl.textContent = 'เลือกแล้ว: ' + (selectedVariant.variant_name || '');
        } else {
          variantHintBarEl.textContent = 'กรุณาเลือกตัวเลือก';
        }
      }
    }

    function syncVariantActiveInMain() {
      if (!selectedVariant) return;
      document.querySelectorAll('.variant-chip-main').forEach(btn => {
        const v = JSON.parse(btn.dataset.variant);
        if (String(v.id) === String(selectedVariant.id)) {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      });

      const hintMain = document.getElementById('variantHintMain');
      if (hintMain && selectedVariant) {
        hintMain.textContent = 'เลือกแล้ว: ' + (selectedVariant.variant_name || '');
      }
    }

    function syncVariantActiveInBar() {
      if (!selectedVariant || !variantListBarEl) return;
      document.querySelectorAll('.variant-chip-bar').forEach(btn => {
        const v = JSON.parse(btn.dataset.variant);
        if (String(v.id) === String(selectedVariant.id)) {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      });

      if (variantHintBarEl && selectedVariant) {
        variantHintBarEl.textContent = 'เลือกแล้ว: ' + (selectedVariant.variant_name || '');
      }
    }

    function updateCartBarButtons() {
      if (cartMode === 'add') {
        cartAddConfirmBtn.classList.remove('d-none');
        cartBuyConfirmBtn.classList.add('d-none');
      } else {
        cartAddConfirmBtn.classList.add('d-none');
        cartBuyConfirmBtn.classList.remove('d-none');
      }
    }

    function openCartBar(mode) {
      cartMode = mode || 'add';
      updateCartBarButtons();

      cartQty = 1;
      cartQtyInputEl.value = cartQty;

      renderVariantsInBar();
      updatePriceAndStockUI();

      cartBarOverlayEl.classList.add('show');
      cartBarEl.classList.add('show');
    }

    function closeCartBar() {
      cartBarOverlayEl.classList.remove('show');
      cartBarEl.classList.remove('show');
    }

    cartBarOverlayEl.addEventListener('click', closeCartBar);

    function changeCartQty(delta) {
      const stock = getCurrentStock();
      cartQty = Number(cartQtyInputEl.value || 1);
      cartQty += delta;
      if (cartQty < 1) cartQty = 1;
      if (stock > 0 && cartQty > stock) cartQty = stock;
      cartQtyInputEl.value = cartQty;
    }

    function requireVariantIfNeeded() {
      if (variants.length > 0 && !selectedVariant) {
        showToast('กรุณาเลือกตัวเลือกสินค้า');
        return false;
      }
      return true;
    }

    // ─────────────── syncCartToServer (ใช้ format เดียวกับ Buyer.php) ───────────────
    function syncCartToServer() {
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
          if (data.status !== 'ok') {
            console.error('Sync error:', data);
            throw new Error(data.message || 'sync_failed');
          }
          return data;
        })
        .catch(err => {
          console.error('Fetch error:', err);
          throw err;
        });
    }

    // เพิ่มของลง array cart แล้ว sync ไป server
    function addCartLogic(qty) {
      const variantId = selectedVariant ? selectedVariant.id : null;
      const price = getCurrentPrice();
      const imageUrl = (selectedVariant && selectedVariant.image) ? selectedVariant.image : product.image;
      const stock = getCurrentStock();

      const existing = cart.find(
        item =>
        String(item.product_id) === String(product.id) &&
        String(item.variant_id ?? '') === String(variantId ?? '')
      );

      const currentQty = existing ? Number(existing.quantity) || 0 : 0;
      const newTotal = currentQty + qty;

      if (stock > 0 && newTotal > stock) {
        const canAdd = stock - currentQty;
        if (canAdd <= 0) {
          alert('ในตะกร้ามีครบจำนวนสต็อกแล้ว');
        } else {
          alert('เพิ่มได้สูงสุดอีก ' + canAdd + ' ชิ้น');
        }
        return false;
      }

      if (existing) {
        existing.quantity = newTotal;
      } else {
        cart.push({
          product_id: product.id,
          variant_id: variantId,
          name: product.name,
          variant_name: selectedVariant ? selectedVariant.variant_name : null,
          image: imageUrl,
          price: price,
          quantity: qty
        });
      }

      updateCartBadge();
      renderCartModal();

      return true;
    }

    async function addToCart() {
      if (!requireVariantIfNeeded()) return;
      const qty = Number(cartQtyInputEl.value || 1);
      const ok = addCartLogic(qty);
      if (!ok) return;

      try {
        await syncCartToServer();
        showToast('เพิ่มลงตะกร้าเรียบร้อยแล้ว');
        closeCartBar();
      } catch (err) {
        alert('เกิดข้อผิดพลาดในการบันทึกตะกร้า กรุณาลองใหม่อีกครั้ง');
        console.error(err);
      }
    }

    async function buyNow() {
      if (!requireVariantIfNeeded()) return;

      const qty = Number(cartQtyInputEl.value || 1);
      const ok = addCartLogic(qty); // ให้ตะกร้าตรงกับที่ซื้อ
      if (!ok) return;

      const pid = product.id;
      const vid = selectedVariant ? selectedVariant.id : 0;

      try {
        await syncCartToServer();
      } catch (e) {
        console.error(e);
        alert('บันทึกตะกร้าล้มเหลว ลองใหม่อีกครั้งได้ไหม');
        return;
      }

      const url =
        `payment.php?mode=single&product_id=${encodeURIComponent(pid)}&variant_id=${encodeURIComponent(vid)}&quantity=${encodeURIComponent(qty)}`;
      window.location.href = url;
    }

    function removeCartItem(index) {
      if (index < 0 || index >= cart.length) return;

      cart.splice(index, 1);
      updateCartBadge();
      renderCartModal();
      syncCartToServer();
    }

    function renderCartModal() {
      const container = document.getElementById('cartItemsContainer');
      const totalEl = document.getElementById('cartTotal');

      if (!container || !totalEl) return;

      container.innerHTML = '';

      if (!cart || cart.length === 0) {
        container.innerHTML = '<div class="alert alert-info rounded-3 mb-0"><i class="bi bi-info-circle me-2"></i>ยังไม่มีสินค้าในตะกร้า</div>';
        totalEl.textContent = '0 บาท';
        return;
      }

      let total = 0;

      cart.forEach((item, index) => {
        const qty = Number(item.quantity || 0);
        const price = Number(item.price || 0);
        const lineTotal = price * qty;
        total += lineTotal;

        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 mb-3 p-2 cart-row';

        const name = item.name || '';
        const variantName = item.variant_name ? ` (${item.variant_name})` : '';

        row.innerHTML = `
      <div class="rounded-3 overflow-hidden bg-light" style="width:60px;height:60px;">
        <img src="${item.image}" class="w-100 h-100" style="object-fit: cover;">
      </div>
      <div class="flex-grow-1 min-width-0">
        <div class="small fw-semibold text-truncate">${name}${variantName}</div>
        <div class="small text-muted">จำนวน: <span class="fw-bold">${qty}</span> ชิ้น</div>
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

    // ───────────────────── Scroll to top ─────────────────────
    window.addEventListener('scroll', () => {
      if (window.scrollY > 400) {
        scrollTopBtn.style.display = 'flex';
      } else {
        scrollTopBtn.style.display = 'none';
      }
    });

    scrollTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    // ───────────────────── Event ปุ่มหลัก ─────────────────────
    document.getElementById('addCartBtn').addEventListener('click', () => {
      openCartBar('add'); // จะโชว์เฉพาะปุ่ม "เพิ่มลงตะกร้า"
    });

    document.getElementById('buyNowBtn').addEventListener('click', () => {
      openCartBar('buy'); // จะโชว์เฉพาะปุ่ม "ซื้อเลย"
    });

    cartAddConfirmBtn.addEventListener('click', addToCart);
    cartBuyConfirmBtn.addEventListener('click', buyNow);

    // ปุ่มตะกร้าบน Topbar -> เปิด Modal ดูตะกร้า
    if (cartModalEl) {
      cartModalInstance = new bootstrap.Modal(cartModalEl);
    }

    openCartBtnEl.addEventListener('click', () => {
      renderCartModal();
      if (cartModalInstance) {
        cartModalInstance.show();
      }
    });

    // ───────────────────── Init ─────────────────────
    document.addEventListener('DOMContentLoaded', () => {
      updatePriceAndStockUI();
      updateCartBadge();
    });
  </script>

</body>

</html>