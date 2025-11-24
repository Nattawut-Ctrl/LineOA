<div class="sidebar">
    <div class="px-4 mb-3 text-muted text-uppercase small">Navigation</div>

    <a href="../dashboard.php"
       class="nav-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-house"></i> Home
    </a>

    <!-- <a href="../Users/manage_users.php"
       class="nav-link <?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">
        <i class="bi bi-people"></i> Users
    </a> -->

    <a href="/Stock/addStock.php"
       class="nav-link <?= ($activeMenu ?? '') === 'stock' ? 'active' : '' ?>">
        <i class="bi bi-box-seam"></i> Items
    </a>

    <a href="../payments/list.php"
       class="nav-link <?= ($activeMenu ?? '') === 'slip' ? 'active' : '' ?>">
        <i class="bi bi-receipt"></i> Payment Slips
    </a>

    <a href="../settings/backup.php"
       class="nav-link">
        <i class="bi bi-arrow-repeat"></i> Backup & Restore
    </a>
</div>
