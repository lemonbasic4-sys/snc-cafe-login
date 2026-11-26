/** SNC 2018 Café & Resto | Cashier POS Interactions */
(() => {
  'use strict';

  const menuButtons = document.querySelectorAll('.snc-menu-item');
  const catButtons = document.querySelectorAll('.snc-cat-btn');
  const cartList = document.getElementById('snc-cart-list');
  const totalEl = document.getElementById('snc-pos-total');
  const changeEl = document.getElementById('snc-pos-change');
  const orderTypeEl = document.getElementById('snc-order-type');
  const noteEl = document.getElementById('snc-order-note');
  const payMethodEl = document.getElementById('snc-pay-method');
  const tenderedEl = document.getElementById('snc-amount-tendered');
  const submitBtn = document.getElementById('snc-pos-submit');
  const clearBtn = document.getElementById('snc-pos-clear');
  const recentBody = document.getElementById('snc-recent-orders-body');
  const csrfInput = document.getElementById('snc-pos-csrf');

  if (!cartList || !totalEl || !changeEl || !submitBtn || !csrfInput) return;

  const cart = new Map();
  let audioCtx = null;

  function playClickSound() {
    try {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      if (!audioCtx) audioCtx = new Ctx();
      if (audioCtx.state === 'suspended') audioCtx.resume();

      const now = audioCtx.currentTime;
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();

      osc.type = 'triangle';
      osc.frequency.setValueAtTime(620, now);
      osc.frequency.exponentialRampToValueAtTime(220, now + 0.032);

      gain.gain.setValueAtTime(0.30, now);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04);

      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.start(now);
      osc.stop(now + 0.042);
    } catch (e) {
      // Ignore audio errors if blocked or unsupported.
    }
  }

  function formatPeso(value) {
    return '₱' + Number(value).toFixed(2);
  }

  function getTotal() {
    let sum = 0;
    cart.forEach((item) => {
      sum += item.price * item.qty;
    });
    return Math.round(sum * 100) / 100;
  }

  function updatePaymentDisplay() {
    const total = getTotal();
    totalEl.textContent = formatPeso(total);

    const method = payMethodEl ? payMethodEl.value : 'cash';
    if (tenderedEl) {
      const isCash = method === 'cash';
      tenderedEl.disabled = !isCash;
      if (!isCash) {
        tenderedEl.value = total > 0 ? total.toFixed(2) : '';
      }
      const tendered = parseFloat(tenderedEl.value || '0') || 0;
      const change = isCash ? Math.max(0, tendered - total) : 0;
      changeEl.textContent = formatPeso(change);
    }

    submitBtn.disabled = cart.size === 0;
  }

  function renderCart() {
    cartList.innerHTML = '';
    if (cart.size === 0) {
      const empty = document.createElement('li');
      empty.className = 'snc-cart-empty';
      empty.textContent = 'Select menu items on the left to build an order.';
      cartList.appendChild(empty);
      updatePaymentDisplay();
      return;
    }

    cart.forEach((item, id) => {
      const li = document.createElement('li');
      li.className = 'snc-cart-row';

      const info = document.createElement('div');
      const name = document.createElement('p');
      name.className = 'snc-cart-row__name';
      name.textContent = item.name;
      const unit = document.createElement('p');
      unit.className = 'snc-cart-row__unit';
      unit.textContent = `${formatPeso(item.price)} each`;
      info.appendChild(name);
      info.appendChild(unit);

      const ctrl = document.createElement('div');
      ctrl.className = 'snc-qty-ctrl';

      const decBtn = document.createElement('button');
      decBtn.type = 'button';
      decBtn.className = 'snc-qty-btn';
      decBtn.setAttribute('aria-label', `Decrease ${item.name}`);
      decBtn.textContent = '−';
      decBtn.addEventListener('click', () => {
        playClickSound();
        if (item.qty <= 1) {
          cart.delete(id);
        } else {
          item.qty -= 1;
        }
        renderCart();
      });

      const qtySpan = document.createElement('span');
      qtySpan.className = 'snc-qty-val';
      qtySpan.textContent = String(item.qty);

      const incBtn = document.createElement('button');
      incBtn.type = 'button';
      incBtn.className = 'snc-qty-btn';
      incBtn.setAttribute('aria-label', `Increase ${item.name}`);
      incBtn.textContent = '+';
      incBtn.addEventListener('click', () => {
        playClickSound();
        if (item.qty < 99) {
          item.qty += 1;
          renderCart();
        }
      });

      ctrl.appendChild(decBtn);
      ctrl.appendChild(qtySpan);
      ctrl.appendChild(incBtn);

      const lineTotal = document.createElement('span');
      lineTotal.className = 'snc-cart-row__total';
      lineTotal.textContent = formatPeso(item.price * item.qty);

      li.appendChild(info);
      li.appendChild(ctrl);
      li.appendChild(lineTotal);
      cartList.appendChild(li);
    });

    updatePaymentDisplay();
  }

  async function loadRecentOrders() {
    if (!recentBody) return;
    try {
      const res = await fetch('../api/orders.php', { headers: { Accept: 'application/json' } });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data || !data.ok) return;

      recentBody.innerHTML = '';
      if (!data.orders || data.orders.length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 5;
        td.textContent = 'No orders recorded yet.';
        tr.appendChild(td);
        recentBody.appendChild(tr);
        return;
      }

      data.orders.forEach((order) => {
        const tr = document.createElement('tr');
        const cells = [
          order.order_number,
          order.order_type === 'takeout' ? 'Takeout' : 'Dine-In',
          formatPeso(order.total_amount),
          String(order.payment_method || 'cash').toUpperCase(),
          null
        ];
        cells.forEach((val, idx) => {
          const td = document.createElement('td');
          if (idx === 4) {
            const pill = document.createElement('span');
            pill.className = 'snc-status-pill';
            pill.textContent = order.kitchen_status;
            td.appendChild(pill);
          } else {
            td.textContent = val;
          }
          tr.appendChild(td);
        });
        recentBody.appendChild(tr);
      });
    } catch (e) {
      // Keep existing table content if network fails.
    }
  }

  menuButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      playClickSound();
      const id = Number(btn.dataset.id);
      const name = btn.dataset.name || '';
      const price = parseFloat(btn.dataset.price || '0');
      if (!id || !name) return;

      if (cart.has(id)) {
        const existing = cart.get(id);
        if (existing.qty < 99) existing.qty += 1;
      } else {
        cart.set(id, { id, name, price, qty: 1 });
      }
      renderCart();
    });
  });

  catButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      playClickSound();
      const cat = btn.dataset.category || 'all';
      catButtons.forEach((b) => b.classList.toggle('is-active', b === btn));
      menuButtons.forEach((item) => {
        const matches = cat === 'all' || item.dataset.category === cat;
        item.classList.toggle('is-hidden', !matches);
      });
    });
  });

  if (payMethodEl) payMethodEl.addEventListener('change', updatePaymentDisplay);
  if (tenderedEl) tenderedEl.addEventListener('input', updatePaymentDisplay);

  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      playClickSound();
      cart.clear();
      if (noteEl) noteEl.value = '';
      if (tenderedEl) tenderedEl.value = '';
      renderCart();
    });
  }

  submitBtn.addEventListener('click', async () => {
    if (cart.size === 0) return;

    const total = getTotal();
    const method = payMethodEl ? payMethodEl.value : 'cash';
    const tendered = method === 'cash' ? (parseFloat(tenderedEl.value || '0') || 0) : total;

    if (method === 'cash' && tendered < total) {
      if (window.SncToast) window.SncToast.warning('Cash tendered is less than the total.', 'Check Payment');
      return;
    }

    playClickSound();

    submitBtn.disabled = true;
    const prevText = submitBtn.textContent;
    submitBtn.textContent = 'Sending...';

    try {
      const items = Array.from(cart.values()).map((item) => ({
        menu_item_id: item.id,
        quantity: item.qty
      }));

      const res = await fetch('../api/orders.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': csrfInput.value
        },
        body: JSON.stringify({
          items,
          order_type: orderTypeEl ? orderTypeEl.value : 'dine_in',
          table_or_note: noteEl ? noteEl.value.trim() : '',
          payment_method: method,
          amount_tendered: tendered
        })
      });

      const data = await res.json().catch(() => null);
      if (res.ok && data && data.ok) {
        const orderNum = data.order ? data.order.order_number : 'Order';
        if (window.SncToast) {
          window.SncToast.success(`${orderNum} sent to kitchen.`, 'Order Recorded');
        }
        cart.clear();
        if (noteEl) noteEl.value = '';
        if (tenderedEl) tenderedEl.value = '';
        renderCart();
        loadRecentOrders();
        return;
      }

      const msg = (data && data.message) ? data.message : 'Unable to submit order.';
      if (window.SncToast) window.SncToast.error(msg, 'Order Error');
    } catch (e) {
      if (window.SncToast) window.SncToast.error('Network error while saving order.', 'Connection Error');
    } finally {
      submitBtn.textContent = prevText;
      updatePaymentDisplay();
    }
  });

  renderCart();
  loadRecentOrders();
})();
