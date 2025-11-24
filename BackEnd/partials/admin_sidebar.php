<aside class="app-sidebar bg-dark text-white shadow" data-bs-theme="dark">

    <!-- Brand -->
    <div class="sidebar-brand d-flex align-items-center px-3 py-2">
        <span class="brand-text fw-bold">Line-Shop Admin</span>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav flex-column" role="menu">

                <li class="nav-item">
                    <a href="<?= BACKEND_URL ?>/dashboard.php"
                       class="nav-link <?= ($activeMenu ?? '')==='dashboard' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-house"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= BACKEND_URL ?>/Stock/addStock.php"
                       class="nav-link <?= ($activeMenu ?? '')==='stock' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-box-seam"></i>
                        <span>Items / Stock</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= BACKEND_URL ?>/payments/list.php"
                       class="nav-link <?= ($activeMenu ?? '')==='slip' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-receipt"></i>
                        <span>Payment Slips</span>
                    </a>
                </li>

            </ul>
        </nav>
    </div>

</aside>
