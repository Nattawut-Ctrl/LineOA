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
            <p>Dashboard</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/orders/order.php"
            class="nav-link <?= ($activeMenu ?? '') === 'order' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-cart"></i>
            <p>Orders</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/stock/addStock.php"
            class="nav-link <?= ($activeMenu ?? '') === 'stock' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-box-seam"></i>
            <p>Products & Stock</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?= BACKEND_URL ?>/pages/payments/list.php"
            class="nav-link <?= ($activeMenu ?? '') === 'slip' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-receipt"></i>
            <p>Payment Slips</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</aside>