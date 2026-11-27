/** SNC 2018 Café & Resto | Cashier POS Interactions */
(() => {
  'use strict';

  const menuButtons = document.querySelectorAll('.snc-menu-item');
  const catButtons = document.querySelectorAll('.snc-cat-btn[data-category]');
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
  const cashShortcuts = document.querySelectorAll('[data-cash-amount]');

  if (!cartList || !totalEl || !changeEl || !submitBtn || !csrfInput) return;

  const cart = new Map();
  let isSubmitting = false;
  let pendingCheckout = null;
  const checkoutStatus = document.getElementById('snc-checkout-status');
  const checkoutKey = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
  const scriptSrc = document.currentScript ? document.currentScript.src : '';
  const CLICK_SOUND_URL = scriptSrc
    ? new URL('../sounds/click.wav', scriptSrc).href
    : '../assets/sounds/click.wav';
  let clickAudio = null;

  function playClickSound() {
    try {
      if (!clickAudio) {
        clickAudio = new Audio(CLICK_SOUND_URL);
        clickAudio.preload = 'auto';
      }
      const clip = clickAudio.cloneNode();
      const playPromise = clip.play();
      if (playPromise && typeof playPromise.catch === 'function') {
        playPromise.catch(() => {});
      }
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
      tenderedEl.disabled = !isCash || Boolean(pendingCheckout);
      if (!isCash) {
        tenderedEl.value = total > 0 ? total.toFixed(2) : '';
      }
      const tendered = parseFloat(tenderedEl.value || '0') || 0;
      const change = isCash ? Math.max(0, tendered - total) : 0;
      changeEl.textContent = formatPeso(change);
    }

    submitBtn.disabled = isSubmitting || cart.size === 0 || !method;
    cashShortcuts.forEach((button) => {
      button.disabled = isSubmitting || Boolean(pendingCheckout) || cart.size === 0 || method !== 'cash';
    });
    [orderTypeEl, noteEl, payMethodEl, clearBtn, ...menuButtons, ...document.querySelectorAll('.snc-qty-btn')].forEach(element => {
      if (element) element.disabled = isSubmitting || Boolean(pendingCheckout);
    });
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
        if (isSubmitting || pendingCheckout) return;
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
        if (isSubmitting || pendingCheckout) return;
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

  const recentStatus = document.getElementById('snc-recent-orders-status');
  const orderRows = new Map();
  let recentLoading = false;
  let recentReloadRequested = false;

  function setText(element, value) {
    const text = String(value ?? '');
    if (element.textContent !== text) element.textContent = text;
  }

  function createOrderRow(orderId) {
    const row = document.createElement('tr');
    const cells = Array.from({ length: 6 }, () => row.appendChild(document.createElement('td')));
    const number = cells[0].appendChild(document.createElement('a'));
    number.className = 'snc-order-link';
    number.href = `order.php?order_id=${encodeURIComponent(orderId)}`;
    number.target = '_blank';
    number.rel = 'noopener';
    number.dataset.orderDetails = orderId;
    const time = cells[0].appendChild(document.createElement('time'));
    time.className = 'snc-order-secondary';
    const type = cells[1].appendChild(document.createElement('span'));
    const customer = cells[1].appendChild(document.createElement('span'));
    customer.className = 'snc-order-secondary snc-order-customer';
    const pill = cells[4].appendChild(document.createElement('span'));
    pill.className = 'snc-status-pill';
    const actions = cells[5].appendChild(document.createElement('div'));
    actions.className = 'snc-order-actions';
    const details = actions.appendChild(document.createElement('a'));
    details.href = number.href;
    details.dataset.orderDetails = orderId;
    details.className = 'snc-cat-btn snc-receipt-link snc-details-link';
    details.textContent = 'View Details';
    const link = actions.appendChild(document.createElement('a'));
    link.className = 'snc-cat-btn snc-receipt-link';
    link.href = `receipt.php?order_id=${encodeURIComponent(orderId)}`;
    link.target = '_blank';
    link.rel = 'noopener';
    link.textContent = 'Print Receipt';
    const unavailable = actions.appendChild(document.createElement('span'));
    unavailable.textContent = 'Unavailable';
    return { row, cells, number, time, type, customer, pill, details, link, unavailable };
  }

  async function loadRecentOrders() {
    if (!recentBody) return;
    if (recentLoading) {
      recentReloadRequested = true;
      return;
    }
    recentLoading = true;
    try {
      const res = await fetch('../api/orders.php', {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
        signal: AbortSignal.timeout(10000)
      });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data || !data.ok || !Array.isArray(data.orders)) throw new Error('Orders unavailable');
      if(payMethodEl&&data.payment_methods&&!isSubmitting&&!pendingCheckout){
        const methods=Object.entries(data.payment_methods).filter(([key])=>['cash','gcash','card'].includes(key));
        const previous=payMethodEl.value;const signature=methods.map(([key])=>key).join(',');
        if(signature!==[...payMethodEl.options].map(option=>option.value).join(',')){
          const options=methods.map(([value,text])=>{const option=document.createElement('option');option.value=value;option.textContent=text;return option;});
          payMethodEl.replaceChildren(...options);if(methods.some(([value])=>value===previous))payMethodEl.value=previous;
          else if(previous)window.SncToast?.info('Accepted payment methods changed. Review the selected method before charging.','Payment Settings Updated');
          updatePaymentDisplay();
        }
      }

      if (orderRows.size === 0) recentBody.replaceChildren();
      const currentIds = new Set();
      data.orders.forEach((order, index) => {
        const id = String(order.id);
        currentIds.add(id);
        if (!orderRows.has(id)) orderRows.set(id, createOrderRow(id));
        const entry = orderRows.get(id);
        setText(entry.number, order.order_number);
        entry.details.setAttribute('aria-label', `View details for ${order.order_number}`);
        // Preserve the database wall-clock time; it has no timezone offset.
        const timestamp = String(order.created_at || '');
        const match = /^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/.exec(timestamp);
        setText(entry.time, match ? `${match[1]} · ${match[2]}` : timestamp || 'Time unavailable');
        entry.time.dateTime = match ? timestamp.replace(' ', 'T') : '';
        setText(entry.type, order.order_type === 'takeout' ? 'Takeout' : 'Dine-In');
        setText(entry.customer, String(order.table_or_note || '').trim() || '—');
        setText(entry.cells[2], formatPeso(order.total_amount));
        setText(entry.cells[3], String(order.payment_method || 'cash').toUpperCase());
        const statuses = { voided: 'Voided', pending: 'Pending', preparing: 'Preparing', ready: 'Ready', completed: 'Completed' };
        const displayStatus=order.payment_status==='voided'?'voided':order.kitchen_status;
        setText(entry.pill, statuses[displayStatus] || 'Unknown');
        entry.pill.dataset.status = displayStatus;
        entry.link.hidden = order.payment_status !== 'paid';
        entry.unavailable.hidden = order.payment_status === 'paid';
        entry.link.setAttribute('aria-label', `Print receipt for ${order.order_number}`);
        if (recentBody.children[index] !== entry.row) {
          recentBody.insertBefore(entry.row, recentBody.children[index] || null);
        }
      });
      orderRows.forEach((entry, id) => {
        if (!currentIds.has(id)) {
          entry.row.remove();
          orderRows.delete(id);
        }
      });
      if (data.orders.length === 0) {
        const td = document.createElement('td');
        td.colSpan = 6;
        td.textContent = 'No orders recorded yet.';
        recentBody.appendChild(document.createElement('tr')).appendChild(td);
      }
      setText(recentStatus, 'Auto-refresh every 15s');
    } catch (e) {
      setText(recentStatus, 'Updates unavailable · Retrying');
      if (orderRows.size === 0) {
        const td = document.createElement('td');
        td.colSpan = 6;
        td.textContent = 'Unable to load recent orders. Retrying automatically.';
        recentBody.replaceChildren(document.createElement('tr'));
        recentBody.firstChild.appendChild(td);
      }
    } finally {
      recentLoading = false;
      if (recentReloadRequested) {
        recentReloadRequested = false;
        loadRecentOrders();
      }
    }
  }

  menuButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      if (isSubmitting || pendingCheckout) return;
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
  cashShortcuts.forEach((button) => {
    button.addEventListener('click', () => {
      if (button.disabled || !tenderedEl) return;
      tenderedEl.value = (button.dataset.cashAmount === 'exact'
        ? getTotal() : Number(button.dataset.cashAmount)).toFixed(2);
      playClickSound();
      updatePaymentDisplay();
    });
  });
  if (tenderedEl) tenderedEl.addEventListener('input', updatePaymentDisplay);

  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      if (isSubmitting || pendingCheckout) return;
      playClickSound();
      cart.clear();
      if (noteEl) noteEl.value = '';
      if (tenderedEl) tenderedEl.value = '';
      renderCart();
    });
  }

  submitBtn.addEventListener('click', async () => {
    if (isSubmitting || cart.size === 0) return;

    const total = getTotal();
    const method = payMethodEl ? payMethodEl.value : 'cash';
    const tendered = method === 'cash' ? (parseFloat(tenderedEl.value || '0') || 0) : total;

    if (!pendingCheckout && method === 'cash' && tendered < total) {
      if (window.SncToast) window.SncToast.warning('Cash tendered is less than the total.', 'Check Payment');
      return;
    }

    playClickSound();

    isSubmitting = true;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';
    if (!pendingCheckout) {
      pendingCheckout = {
        request_key: checkoutKey(),
        items: Array.from(cart.values()).map(item => ({menu_item_id: item.id, quantity: item.qty})),
        order_type: orderTypeEl ? orderTypeEl.value : 'dine_in',
        table_or_note: noteEl ? noteEl.value.trim() : '',
        payment_method: method,
        amount_tendered: method === 'cash' ? tenderedEl.value : total.toFixed(2)
      };
    }
    if (checkoutStatus) checkoutStatus.textContent = 'Saving order and checking ingredient stock…';
    updatePaymentDisplay();

    try {
      const res = await fetch('../api/orders.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': csrfInput.value
        },
        body: JSON.stringify(pendingCheckout),
        signal: AbortSignal.timeout(15000)
      });

      const data = await res.json().catch(() => null);
      if (res.ok && data && data.ok) {
        pendingCheckout = null;
        if (checkoutStatus) checkoutStatus.textContent = '';
        const orderNum = data.order ? data.order.order_number : 'Order';
        const voided=data.order?.payment_status==='voided';
        if (window.SncToast) {
          if(voided)window.SncToast.warning(`${orderNum} was recorded and subsequently voided. Review its details.`, 'Order Voided');
          else window.SncToast.success(`${orderNum} sent to kitchen.`, 'Order Recorded');
        }
        cart.clear();
        if (noteEl) noteEl.value = '';
        if (tenderedEl) tenderedEl.value = '';
        renderCart();
        if (data.order && data.order.id) {
          document.querySelector('#snc-payment-saved h2').textContent=voided?'Order Voided':'Payment saved';
          document.getElementById('snc-payment-saved-detail').textContent = voided?`${orderNum} was recorded and subsequently voided. Review order details.`:`${orderNum} · Sent to kitchen · Change ${formatPeso(data.order.change_amount)}`;
          document.getElementById('snc-last-receipt').hidden=voided;
          document.getElementById('snc-last-receipt').href = `receipt.php?order_id=${encodeURIComponent(data.order.id)}`;
          document.getElementById('snc-payment-saved').hidden = false;
        }
        loadRecentOrders();
        return;
      }

      const msg = (data && data.message) ? data.message : 'Unable to submit order.';
      if (res.status >= 400 && res.status < 500 && data) {
        pendingCheckout = null;
        if (checkoutStatus) checkoutStatus.textContent = msg;
      } else if (checkoutStatus) {
        checkoutStatus.textContent = 'Order result unknown. Use Retry Checkout to check the same order before starting another. Keep this page open.';
      }
      if (window.SncToast) window.SncToast.error(msg, 'Order Error');
    } catch (e) {
      if (checkoutStatus) checkoutStatus.textContent = 'Order result unknown. Use Retry Checkout to check the same order before starting another. Keep this page open.';
      if (window.SncToast) window.SncToast.error('Connection interrupted. Retry Checkout will check the same order.', 'Connection Error');
    } finally {
      isSubmitting = false;
      submitBtn.textContent = pendingCheckout ? 'Retry Checkout' : 'Charge & Send to Kitchen';
      updatePaymentDisplay();
    }
  });

  renderCart();
  loadRecentOrders();
  window.setInterval(() => {
    if (!document.hidden) loadRecentOrders();
  }, 15000);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) loadRecentOrders();
  });
})();
