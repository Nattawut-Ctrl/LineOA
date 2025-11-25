<nav class="app-header navbar navbar-expand bg-dark" data-bs-theme="dark">
  <div class="container-fluid">

    <ul class="navbar-nav">
      <li class="nav-item">
        <button class="nav-link btn text-white" data-lte-toggle="sidebar" role="button">
          <i class="bi bi-list"></i>
        </button>
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
