<nav class="bottom-nav">
    <a href="Buyer.php" class="nav-item <?= ($activeMenu ?? '') === 'main' ? 'active' : '' ?>" id="nav-home">
        <i class="bi bi-house-door"></i>
        <span>หน้าแรก</span>
    </a>

    <a href="order-history.php" class="nav-item <?= ($activeMenu ?? '') === 'orders' ? 'active' : '' ?>" id="nav-orders">
        <span class="icon-wrap position-relative d-inline-block">
            <i class="bi bi-receipt mb-3"></i>
            <span id="orderBadge" class="noti-badge badge rounded-pill bg-danger text-white d-none">0</span>
            <span>ออเดอร์</span>
        </span>
        

    </a>

    <a href="notifications.php" class="nav-item <?= ($activeMenu ?? '') === 'notifications' ? 'active' : '' ?>" id="nav-noti">
        <span class="icon-wrap position-relative d-inline-block">
            <i class="bi bi-bell mb-3"></i>
            <span id="notiBadge" class="noti-badge badge rounded-pill bg-danger text-white d-none">0</span>
            <span>แจ้งเตือน</span>
        </span>
        

    </a>

    <a href="profile.php" class="nav-item <?= ($activeMenu ?? '') === 'profile' ? 'active' : '' ?>" id="nav-me">
        <i class="bi bi-person"></i>
        <span>ฉัน</span>
    </a>
</nav>

<script>
  // Config for shared notification checker
  window.__NOTI_CONFIG__ = window.__NOTI_CONFIG__ || {};
  window.__NOTI_CONFIG__.checkUrl = window.__NOTI_CONFIG__.checkUrl || '<?= FRONTEND_URL ?>/api/buyer/check_buyer_notifications.php';
  window.__NOTI_CONFIG__.markReadUrl = window.__NOTI_CONFIG__.markReadUrl || '<?= FRONTEND_URL ?>/api/buyer/mark_buyer_notifications_read.php';
  window.__NOTI_CONFIG__.pollInterval = window.__NOTI_CONFIG__.pollInterval || 10000;
</script>
<script src="<?= FRONTEND_URL ?>/assets/js/shared/check_noti.js"></script>

