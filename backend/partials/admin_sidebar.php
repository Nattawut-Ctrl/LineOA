<aside class="app-sidebar sidebar-dark-primary elevation-2 " data-bs-theme="dark">
  <!-- Brand -->
  <div class="sidebar-brand">
    <a href="<?= BACKEND_URL ?>/pages/dashboard/dashboard.php" class="brand-link">
      <span class="brand-text fw-bold">Line-Shop Admin</span>
    </a>
  </div>

  <!-- Sidebar -->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" role="menu">

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/dashboard/dashboard.php"
            class="nav-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-house"></i>
            <p>แดชบอร์ด</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/orders/order.php"
            class="nav-link <?= ($activeMenu ?? '') === 'order' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-cart"></i>
            <p>รายการออเดอร์</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/stock/addStock.php"
            class="nav-link <?= ($activeMenu ?? '') === 'stock' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-box-seam"></i>
            <p>สินค้าและสต็อก</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/stock/categoryUnits.php"
            class="nav-link <?= ($activeMenu ?? '') === 'category-units' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-tags"></i>
            <p>ประเภทและหน่วยนับ</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/stock/receipt_create.php"
            class="nav-link <?= ($activeMenu ?? '') === 'receipt' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-journal-plus"></i>
            <p>ใบรับของ</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/stock/lots.php"
            class="nav-link <?= ($activeMenu ?? '') === 'lots' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-layers"></i>
            <p>ล็อตสินค้า</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/stock/profit_summary.php" 
            class="nav-link <?= ($activeMenu ?? '') === 'profit_summary' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-graph-up"></i>
            <p>สรุปกำไร</p>
          </a>
        </li>

        <!-- <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/stock/profit_summary.php" class="nav-link" data-bs-toggle="collapse" data-bs-target="#profitSubmenu" aria-expanded="false">
            <i class="nav-icon bi bi-graph-up"></i>
            <p>Profits <i class="bi bi-chevron-down ms-auto"></i></p>
          </a>
          <ul class="nav collapse" id="profitSubmenu">
            <li class="nav-item">
              <a href="<?= BACKEND_URL ?>/pages/stock/profit_by_lot.php"
                class="nav-link <?= ($activeMenu ?? '') === 'profit_by_lot' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-layers-half"></i>
                <p>Profit by Lot</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?= BACKEND_URL ?>/pages/stock/profit_by_product.php"
                class="nav-link <?= ($activeMenu ?? '') === 'profit_by_product' ? 'active' : '' ?>">
                <i class="nav-icon bi bi-box"></i>
                <p>Profit by Product</p>
              </a>
            </li>
          </ul>
        </li> -->

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/payments/list.php"
            class="nav-link <?= ($activeMenu ?? '') === 'slip' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-receipt"></i>
            <p>สลิปการชำระเงิน</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</aside>