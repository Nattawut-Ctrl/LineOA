<aside class="main-sidebar sidebar-dark-primary elevation-2">

  <!-- Brand -->
  <a href="../dashboard.php" class="brand-link brand-gradient text-decoration-none">
    <span class="brand-text fw-bold ms-2">Line-Shop</span>
    <small class="ms-1 opacity-75">Admin</small>
  </a>

  <div class="sidebar">

    <!-- User Panel -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
      <div class="image">
        <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png"
             class="img-circle elevation-2" alt="Admin" width="40" height="40">
      </div>
      <div class="info">
        <div class="d-block text-white fw-semibold">
          <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
        </div>
        <small class="text-muted">Administrator</small>
      </div>
    </div>

    <!-- Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" role="menu">

        <li class="nav-item">
          <a href="../dashboard.php"
             class="nav-link <?= ($activeMenu ?? '')==='dashboard' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="../Stock/addStock.php"
             class="nav-link <?= ($activeMenu ?? '')==='stock' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-box-seam"></i>
            <p>สินค้า / สต็อก</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="../payments/list.php"
             class="nav-link <?= ($activeMenu ?? '')==='slip' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-receipt"></i>
            <p>ตรวจสลิปการโอน</p>
          </a>
        </li>

        <li class="nav-header text-uppercase small mt-2">Settings</li>

        <li class="nav-item">
          <a href="../Users/ad_register.php"
             class="nav-link <?= ($activeMenu ?? '')==='admin' ? 'active' : '' ?>">
            <i class="nav-icon bi bi-person-gear"></i>
            <p>เพิ่ม/จัดการแอดมิน</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</aside>
