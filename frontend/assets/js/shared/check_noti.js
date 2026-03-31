/* assets/js/shared/check_noti.js */
(function () {
  "use strict";

  const cfg = window.__NOTI_CONFIG__ || {};
  const CHECK_URL = cfg.checkUrl || "/api/buyer/check_buyer_notifications.php";
  const MARK_READ_URL =
    cfg.markReadUrl || "/api/buyer/mark_buyer_notifications_read.php";
  const POLL_INTERVAL = Number(cfg.pollInterval) || 10000;

  let _timer = null;

  function updateBadgeCounts(data) {
    try {
      const notiBadge = document.getElementById("notiBadge");
      if (notiBadge) {
        if (data && data.count > 0) {
          notiBadge.textContent = data.count;
          notiBadge.classList.remove("d-none");
        } else {
          notiBadge.classList.add("d-none");
        }
      }

      const orderBadge = document.getElementById("orderBadge");
      if (orderBadge && data && typeof data.order_count !== "undefined") {
        if (data.order_count > 0) {
          orderBadge.textContent = String(data.order_count);
          orderBadge.classList.remove("d-none");
        } else {
            orderBadge.textContent = "";
          orderBadge.classList.add("d-none");
        }
      }
    } catch (e) {
      // ignore DOM errors
    }
  }

  async function checkNotifications() {
    try {
      const res = await fetch(CHECK_URL, { credentials: "same-origin" });
      const json = await res.json().catch(() => ({}));
      if (json && typeof json === "object") {
        updateBadgeCounts(json);
        return json;
      }
    } catch (e) {
      console.error("checkNotifications error:", e);
    }
    return null;
  }

  async function markAllRead() {
    try {
      await fetch(MARK_READ_URL, {
        method: "POST",
        credentials: "same-origin",
      });
    } catch (e) {
      console.error("mark read error", e);
    }
  }

  function startPolling() {
    if (_timer) return;
    checkNotifications();
    _timer = setInterval(checkNotifications, POLL_INTERVAL);
  }

  function stopPolling() {
    if (!_timer) return;
    clearInterval(_timer);
    _timer = null;
  }

  // Auto-start if the config is present (the partial sets window.__NOTI_CONFIG__ before script)
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", startPolling);
  } else {
    startPolling();
  }

  window.BuyerNoti = {
    checkNotifications,
    startPolling,
    stopPolling,
    markAllRead,
  };
})();
