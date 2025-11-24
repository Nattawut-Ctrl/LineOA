<nav class="main-header navbar navbar-expand navbar-white navbar-light shadow-sm">
  <div class="container-fluid">

    <!-- sidebar toggle -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#">
          <i class="fas fa-bars"></i>
        </a>
      </li>
    </ul>

    <a class="navbar-brand fw-bold ms-2" href="../dashboard.php">
      Line-Shop Admin
    </a>

    <ul class="navbar-nav ms-auto align-items-center">
      <li class="nav-item me-2 d-none d-md-block text-muted small">
        สวัสดี, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
      </li>
      <li class="nav-item">
        <a class="btn btn-outline-danger btn-sm" href="../Users/ad_logout.php">
          <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
      </li>
    </ul>

  </div>
</nav>
