(function () {
  'use strict';

  if (!window.CartCore) return;

  const cfg = (window.__CART_CONFIG__ || {});

  function formatMoney(n) {
    const x = Number(n) || 0;
    return x.toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function updateBadge() {
    const badge = document.getElementById('cartCountBadge');
    if (!badge) return;

    const count = CartCore.getCount();
    badge.textContent = String(count);
    badge.style.display = count > 0 ? 'inline-block' : 'none';
  }

  function renderModal() {
    const container = document.getElementById('cartItemsContainer');
    const totalEl = document.getElementById('cartTotal');
    if (!container || !totalEl) return;

    const cart = CartCore.getCart();
    container.innerHTML = '';

    if (cart.length === 0) {
      container.innerHTML = `<div class="text-center text-muted py-3">ตะกร้าว่าง</div>`;
      totalEl.textContent = `0 บาท`;
      return;
    }

    cart.forEach((it, index) => {
      const displayName = it.variant_name ? `${it.product_name} (${it.variant_name})` : it.product_name;

      const row = document.createElement('div');
      row.className = 'd-flex align-items-center gap-2 mb-3 p-2 cart-row';
      row.innerHTML = `
        <img src="${it.image || ''}" style="width:56px;height:56px;object-fit:cover;border-radius:12px;" alt="">
        <div class="flex-grow-1">
          <div class="small fw-semibold text-truncate">${escapeHtml(displayName)}</div>
          <div class="small text-muted">${formatMoney(it.price)} บาท × ${it.quantity}</div>
        </div>
        <button class="btn btn-sm btn-outline-danger rounded-3" data-remove-index="${index}">
          <i class="bi bi-trash"></i>
        </button>
      `;
      container.appendChild(row);
    });

    container.querySelectorAll('[data-remove-index]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const idx = Number(btn.getAttribute('data-remove-index'));
        CartCore.removeAt(idx);
        await CartCore.syncCart();
      });
    });

    totalEl.textContent = `${formatMoney(CartCore.getTotal())} บาท`;
  }

  function escapeHtml(s) {
    return String(s ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function bindGoPayment() {
    const btn = document.getElementById('goPaymentBtn');
    if (!btn) return;

    btn.addEventListener('click', () => {
      const cart = CartCore.getCart();
      if (!cart.length) return;

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = cfg.paymentUrl || 'payment.php';

      const mode = document.createElement('input');
      mode.type = 'hidden';
      mode.name = 'mode';
      mode.value = 'cart';
      form.appendChild(mode);

      cart.forEach((it, i) => {
        const base = `items[${i}]`;
        form.appendChild(hidden(`${base}[product_id]`, it.product_id));
        form.appendChild(hidden(`${base}[variant_id]`, it.variant_id === null ? '' : it.variant_id));
        form.appendChild(hidden(`${base}[quantity]`, it.quantity));
      });

      document.body.appendChild(form);
      form.submit();
    });
  }

  function hidden(name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = String(value ?? '');
    return input;
  }

  function bindOpenModal() {
    const modalEl = document.getElementById('cartModal');
    if (!modalEl || !window.bootstrap) return;

    const modal = new bootstrap.Modal(modalEl);

    const cartIcon = document.getElementById('cartIcon');
    if (cartIcon) {
      cartIcon.addEventListener('click', (e) => {
        e.preventDefault();
        renderModal();
        modal.show();
      });
    }

    modalEl.addEventListener('show.bs.modal', () => renderModal());
  }

  document.addEventListener('DOMContentLoaded', () => {
    CartCore.subscribe(() => {
      updateBadge();
      renderModal();
    });

    updateBadge();
    bindGoPayment();
    bindOpenModal();
    renderModal();
  });
})();
