<nav class="main-header navbar navbar-expand navbar-white navbar-light shadow-sm">
  <div class="container-fluid">

    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
          <i class="fas fa-bars"></i>
        </a>
      </li>
    </ul>

    <a class="navbar-brand fw-bold ms-2" href="<?= BACKEND_URL ?>/dashboard.php">
      Line-Shop Admin
    </a>

    <ul class="navbar-nav ms-auto align-items-center">
      <li class="nav-item me-2 d-none d-md-block text-muted small">
        <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
      </li>
      <li class="nav-item">
        <a class="btn btn-outline-danger btn-sm" href="<?= BACKEND_URL ?>/Users/ad_logout.php">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </li>
    </ul>

  </div>
</nav>
