/** SNC 2018 Café & Resto | Kitchen Orders Board */
(() => {
  'use strict';

  const ticketGrid = document.getElementById('snc-ticket-grid');
  const filterTabs = document.querySelectorAll('.snc-kitchen-tab');
  const refreshBtn = document.getElementById('snc-kitchen-refresh');
  const csrfInput = document.getElementById('snc-kitchen-csrf');

  if (!ticketGrid || !csrfInput) return;

  let currentFilter = 'all';
  let latestOrders = [];
  let requestSeq = 0;
  let knownPendingIds = new Set();
  let firstLoad = true;

  const NEXT_ACTION = {
    pending: { next: 'preparing', label: 'Start Preparing' },
    preparing: { next: 'ready', label: 'Mark Ready' },
    ready: { next: 'completed', label: 'Complete Order' }
  };

  function renderTickets() {
    ticketGrid.innerHTML = '';
    const filtered = currentFilter === 'all'
      ? latestOrders
      : latestOrders.filter((o) => o.kitchen_status === currentFilter);

    if (filtered.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'snc-kitchen-empty';
      empty.textContent = currentFilter === 'all'
        ? 'No active kitchen orders right now.'
        : `No ${currentFilter} orders right now.`;
      ticketGrid.appendChild(empty);
      return;
    }

    filtered.forEach((order) => {
      const card = document.createElement('article');
      card.className = `snc-ticket snc-ticket--${order.kitchen_status}`;

      const head = document.createElement('div');
      head.className = 'snc-ticket__head';

      const left = document.createElement('div');
      const num = document.createElement('h2');
      num.className = 'snc-ticket__num';
      num.textContent = order.order_number;

      const meta = document.createElement('p');
      meta.className = 'snc-ticket__meta';
      const typeLabel = order.order_type === 'takeout' ? 'Takeout' : 'Dine-In';
      meta.textContent = order.table_or_note ? `${typeLabel} · ${order.table_or_note}` : typeLabel;

      left.appendChild(num);
      left.appendChild(meta);

      const badge = document.createElement('span');
      badge.className = 'snc-ticket__badge';
      badge.textContent = order.kitchen_status;

      head.appendChild(left);
      head.appendChild(badge);

      const itemsList = document.createElement('ul');
      itemsList.className = 'snc-ticket__items';
      (order.items || []).forEach((item) => {
        const li = document.createElement('li');
        li.className = 'snc-ticket__item';

        const nameSpan = document.createElement('span');
        nameSpan.textContent = item.name;

        const qtySpan = document.createElement('span');
        qtySpan.className = 'snc-ticket__qty';
        qtySpan.textContent = `×${item.quantity}`;

        li.appendChild(nameSpan);
        li.appendChild(qtySpan);
        itemsList.appendChild(li);
      });

      card.appendChild(head);
      card.appendChild(itemsList);

      const action = NEXT_ACTION[order.kitchen_status];
      if (action) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'snc-ticket__btn';
        btn.textContent = action.label;
        btn.addEventListener('click', () => updateStatus(order.id, action.next, btn));
        card.appendChild(btn);
      }

      ticketGrid.appendChild(card);
    });
  }

  async function updateStatus(orderId, nextStatus, btn) {
    btn.disabled = true;
    try {
      const res = await fetch('../api/kitchen_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': csrfInput.value
        },
        body: JSON.stringify({ order_id: orderId, next_status: nextStatus })
      });
      const data = await res.json().catch(() => null);
      if (res.ok && data && data.ok) {
        if (window.SncToast) window.SncToast.success(data.message, 'Kitchen Updated');
        await fetchOrders();
        return;
      }
      if (window.SncToast) {
        window.SncToast.error((data && data.message) || 'Unable to update status.', 'Update Failed');
      }
    } catch (e) {
      if (window.SncToast) window.SncToast.error('Network error updating order.', 'Connection Error');
    } finally {
      btn.disabled = false;
    }
  }

  async function fetchOrders() {
    const seq = ++requestSeq;
    try {
      const res = await fetch('../api/orders.php', { headers: { Accept: 'application/json' } });
      const data = await res.json().catch(() => null);
      if (seq !== requestSeq || !res.ok || !data || !data.ok) return;

      latestOrders = Array.isArray(data.orders) ? data.orders : [];
      const nextPendingIds = new Set(
        latestOrders.filter((o) => o.kitchen_status === 'pending').map((o) => o.id)
      );

      if (!firstLoad) {
        let hasNew = false;
        nextPendingIds.forEach((id) => {
          if (!knownPendingIds.has(id)) hasNew = true;
        });
        if (hasNew && window.SncToast) {
          window.SncToast.info('New order ticket received.', 'Kitchen Queue');
        }
      }

      knownPendingIds = nextPendingIds;
      firstLoad = false;
      renderTickets();
    } catch (e) {
      // Preserve last known tickets on transient network errors.
    }
  }

  filterTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      currentFilter = tab.dataset.status || 'all';
      filterTabs.forEach((t) => t.classList.toggle('is-active', t === tab));
      renderTickets();
    });
  });

  if (refreshBtn) {
    refreshBtn.addEventListener('click', fetchOrders);
  }

  fetchOrders();
  window.setInterval(fetchOrders, 10000);
})();
