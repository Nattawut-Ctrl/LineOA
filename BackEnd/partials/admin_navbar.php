<nav class="app-header navbar navbar-expand bg-body">
  <div class="container-fluid">

    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
          <i class="fa-solid fa-bars"></i>
        </a>
      </li>
      <li class="nav-item d-none d-md-inline-block">
        <a href="<?= BACKEND_URL ?>/dashboard.php" class="nav-link fw-semibold">
          Line-Shop Admin
        </a>
      </li>
    </ul>

    <ul class="navbar-nav ms-auto align-items-center">
      <li class="nav-item me-2 small text-muted">
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
