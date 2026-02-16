<?php
$pendingSlipCount = 0;
$pendingSlips     = [];

if (isset($conn) && $conn instanceof mysqli) {
  require_once SERVICES_PATH . '/slipService.php';

  $pendingSlipCount = getPendingSlipCount($conn);
  $pendingSlips     = getPendingSlipNotifications($conn, 10);
}
?>

<nav class="app-header navbar navbar-expand bg-dark sticky-top" data-bs-theme="dark">
  <div class="container-fluid">

    <ul class="navbar-nav">
      <li class="nav-item">
        <button class="nav-link btn text-white" data-lte-toggle="sidebar" role="button">
          <i class="bi bi-list"></i>
        </button>
      </li>
      <li class="nav-item d-none d-md-inline-block">
        <a href="<?= BACKEND_URL ?>/pages/dashboard/dashboard.php" class="nav-link fw-semibold">
          Line-Shop Admin
        </a>
      </li>
    </ul>

    <ul class="navbar-nav ms-auto align-items-center">
      <!-- แจ้งเตือน -->
      <li class="nav-item dropdown position-relative">
        <a class="nav-link position-relative" id="nav-noti-icon" data-bs-toggle="dropdown" href="#">
          <i class="bi bi-bell fs-5"></i>

          <?php if ($pendingSlipCount > 0): ?>
            <!-- badge วงกลมแดง -->
            <span class="badge rounded-pill text-bg-danger navbar-badge"
              style="font-size: 0.65rem; position:absolute; top:4px; right:0;">
              <?= ($pendingSlipCount > 99) ? '99+' : $pendingSlipCount ?>
            </span>
          <?php endif; ?>
        </a>

        <!-- Dropdown แจ้งเตือนแบบ YouTube -->
        <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg"
          id="noti-dropdown"
          style="width: 360px; max-height: 420px; overflow-y: auto;">

          <!-- header -->
          <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-semibold small">
              การแจ้งเตือน
            </span>
            <?php if ($pendingSlipCount > 0): ?>
              <span class="badge text-bg-danger small">
                <?= $pendingSlipCount ?> รายการค้างตรวจ
              </span>
            <?php endif; ?>
          </div>
        </div>
      </li>

      <!-- ชื่อผู้ใช้ + Logout -->
      <li class="nav-item me-2 small text-muted">
        <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
      </li>
      <li class="nav-item">
        <a class="btn btn-outline-danger btn-sm" href="<?= BACKEND_URL ?>/actions/users/logout.php">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </li>
    </ul>

  </div>
</nav>

<script>
  function renderDropdown(items, count) {
    const dropdown = document.getElementById("noti-dropdown");
    if (!dropdown) return;

    let html = '';

    // header
    html += `
      <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">การแจ้งเตือน</span>
        ${count > 0
          ? `<span class="badge text-bg-danger small">${count} รายการค้างตรวจ</span>`
          : ''
        }
      </div>
    `;

    if (!items || items.length === 0) {
      html += `
        <div class="px-3 py-3 small text-muted text-center">
          ยังไม่มีการแจ้งเตือนใหม่
        </div>
      `;
    } else {
      items.forEach(item => {
        const url = "<?= BACKEND_URL ?>/pages/payments/view.php?id=" + encodeURIComponent(item.id);

        html += `
          <a href="${url}"
             class="dropdown-item py-2 px-3 small d-flex gap-2 align-items-start">

            <div class="flex-shrink-0">
              <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                   style="width: 32px; height: 32px;">
                <i class="bi bi-receipt-cutoff"></i>
              </div>
            </div>

            <div class="flex-grow-1">
              <div class="fw-semibold text-truncate">
                สลิปใหม่จาก ${item.customer}
              </div>
              <div class="text-muted">
                ออเดอร์: <span class="fw-semibold">${item.order_code}</span>
              </div>
              <div class="text-danger fw-semibold">
                ฿${item.amount_text}
              </div>
              <div class="text-muted small">
                ส่งเมื่อ ${item.time_text}
              </div>
            </div>
          </a>

          <div class="dropdown-divider my-0"></div>
        `;
      });

      // footer
      html += `
        <div class="px-3 py-2 text-center">
          <a href="<?= BACKEND_URL ?>/pages/payments/list.php" class="small text-decoration-none">
            ดูสลิปทั้งหมด
          </a>
        </div>
      `;
    }

    dropdown.innerHTML = html;
  }

  function updateNotifications() {
    fetch("<?= BACKEND_URL ?>/api/notifications/check.php")
      .then(res => res.json())
      .then(data => {
        if (!data.ok) {
          console.error("API error:", data.error);
          return;
        }

        const count = data.count ?? 0;
        const items = data.items ?? [];

        // ---- อัปเดต badge ----
        const badge = document.getElementById("noti-badge");
        const parent = document.querySelector("#nav-noti-icon");

        if (count > 0) {
          if (!badge) {
            const span = document.createElement("span");
            span.id = "noti-badge";
            span.className = "badge rounded-pill text-bg-danger navbar-badge";
            span.style.cssText = "font-size:0.65rem; position:absolute; top:4px; right:0;";
            span.innerText = count > 99 ? "99+" : count;
            parent.appendChild(span);
          } else {
            badge.innerText = count > 99 ? "99+" : count;
          }
        } else {
          if (badge) badge.remove();
        }

        // ---- อัปเดตเนื้อหา dropdown ----
        renderDropdown(items, count);
      })
      .catch(err => console.error("updateNotifications error:", err));
  }

  // เรียกทุก 3 วินาที
  setInterval(updateNotifications, 3000);
  updateNotifications();
</script>