/* assets/js/shared/cart.core.js */
(function () {
  'use strict';

  const cfg = (window.__CART_CONFIG__ || {});
  const listeners = new Set();

  function emit() {
    for (const fn of listeners) {
      try { fn(getCart()); } catch (_) {}
    }
  }

  function toNumber(n, d = 0) {
    const x = Number(n);
    return Number.isFinite(x) ? x : d;
  }

  function normalizeItem(it) {
    const product_id = toNumber(it.product_id, 0);
    const variant_id = (it.variant_id === null || it.variant_id === undefined || it.variant_id === '' )
      ? null
      : toNumber(it.variant_id, null);

    const product_name = (it.product_name ?? it.name ?? '').toString();
    const variant_name = (it.variant_name ?? '').toString();

    return {
      product_id,
      variant_id,
      product_name,
      variant_name,
      image: (it.image ?? '').toString(),
      price: toNumber(it.price, 0),
      quantity: Math.max(0, toNumber(it.quantity, 0)),
    };
  }

  function makeKey(product_id, variant_id) {
    return `${product_id}:${variant_id === null ? 'null' : String(variant_id)}`;
  }

  let cart = Array.isArray(cfg.initialCart)
    ? cfg.initialCart.map(normalizeItem).filter(x => x.product_id > 0 && x.quantity > 0)
    : [];

  function getCart() {
    // คืนสำเนา ป้องกันถูกแก้ภายนอก
    return cart.map(x => ({ ...x }));
  }

  function setCart(next) {
    cart = Array.isArray(next) ? next.map(normalizeItem).filter(x => x.product_id > 0 && x.quantity > 0) : [];
    emit();
  }

  function getCount() {
    return cart.reduce((s, it) => s + toNumber(it.quantity, 0), 0);
  }

  function getTotal() {
    return cart.reduce((s, it) => s + (toNumber(it.price, 0) * toNumber(it.quantity, 0)), 0);
  }

  function findIndex(product_id, variant_id) {
    const key = makeKey(product_id, variant_id);
    return cart.findIndex(it => makeKey(it.product_id, it.variant_id) === key);
  }

  function addItem(item, qty, maxStock = Infinity) {
    const it = normalizeItem(item);
    const addQty = Math.max(1, toNumber(qty, 1));
    const limit = Number.isFinite(Number(maxStock)) ? Math.max(0, Number(maxStock)) : Infinity;

    const idx = findIndex(it.product_id, it.variant_id);
    if (idx >= 0) {
      const nextQty = cart[idx].quantity + addQty;
      cart[idx].quantity = Math.min(nextQty, limit);
    } else {
      it.quantity = Math.min(addQty, limit);
      cart.push(it);
    }

    cart = cart.filter(x => x.quantity > 0);
    emit();
    return true;
  }

  function removeAt(index) {
    if (index < 0 || index >= cart.length) return;
    cart.splice(index, 1);
    emit();
  }

  async function syncCart() {
    if (!cfg.syncUrl) return { ok: false, error: 'missing_syncUrl' };

    try {
      const res = await fetch(cfg.syncUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ cart: getCart() })
      });

      const json = await res.json().catch(() => ({}));
      return json && typeof json === 'object' ? json : { ok: false };
    } catch (e) {
      return { ok: false, error: 'network_error' };
    }
  }

  function subscribe(fn) {
    if (typeof fn !== 'function') return () => {};
    listeners.add(fn);
    // ยิงค่าปัจจุบันให้ทันที
    try { fn(getCart()); } catch (_) {}
    return () => listeners.delete(fn);
  }

  window.CartCore = {
    getCart,
    setCart,
    addItem,
    removeAt,
    getCount,
    getTotal,
    syncCart,
    subscribe,
    normalizeItem
  };
})();
