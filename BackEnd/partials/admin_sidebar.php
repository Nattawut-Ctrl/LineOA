<!-- BackEnd/partials/admin_sidebar.php -->
<aside class="main-sidebar sidebar-dark-primary elevation-2">
  <a href="../Stock/addStock.php" class="brand-link text-decoration-none">
    <span class="brand-text fw-bold ms-2">Line-Shop</span>
  </a>

  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" role="menu">

        <li class="nav-item">
          <a href="../Stock/addStock.php" class="nav-link <?= ($activeMenu ?? '')==='stock'?'active':'' ?>">
            <i class="nav-icon bi bi-box-seam"></i>
            <p>จัดการสินค้า / สต็อก</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="../payments/list.php" class="nav-link <?= ($activeMenu ?? '')==='slip'?'active':'' ?>">
            <i class="nav-icon bi bi-receipt"></i>
            <p>ตรวจสลิปการโอน</p>
          </a>
        </li>

        <li class="nav-item mt-2">
          <a href="../Users/ad_register.php" class="nav-link <?= ($activeMenu ?? '')==='admin'?'active':'' ?>">
            <i class="nav-icon bi bi-person-gear"></i>
            <p>จัดการแอดมิน</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</aside>
