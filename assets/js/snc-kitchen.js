/** SNC 2018 Café & Resto | Kitchen Orders Board */
(() => {
  'use strict';

  const ticketGrid = document.getElementById('snc-ticket-grid');
  const filterTabs = document.querySelectorAll('.snc-kitchen-tab');
  const refreshBtn = document.getElementById('snc-kitchen-refresh');
  const updateStatusEl = document.getElementById('snc-kitchen-update-status');
  const csrfInput = document.getElementById('snc-kitchen-csrf');

  if (!ticketGrid || !csrfInput) return;

  let currentFilter = 'all';
  let latestOrders = [];
  let requestSeq = 0;
  let knownPendingIds = new Set();
  let firstLoad = true;
  let ordersReceivedAt = Date.now();
  let lastUpdatedAt = 0;
  let updatesUnavailable = false;

  function updateRefreshStatus() {
    if (!updateStatusEl) return;
    const seconds = Math.floor(Math.max(0, Date.now() - lastUpdatedAt) / 1000);
    const age = seconds < 10 ? 'just now' : seconds < 60 ? `${seconds}s ago` : `${Math.floor(seconds / 60)} min ago`;
    const text = updatesUnavailable
      ? `Updates unavailable · ${lastUpdatedAt ? `Last updated ${age} · ` : ''}Retrying every 10s`
      : lastUpdatedAt ? `Updated ${age} · Auto-refresh every 10s` : 'Loading queue…';
    if (updateStatusEl.textContent !== text) updateStatusEl.textContent = text;
    updateStatusEl.dataset.unavailable = String(updatesUnavailable);
    updateStatusEl.title = lastUpdatedAt ? `Last successful update: ${new Date(lastUpdatedAt).toLocaleString()}` : 'No successful update yet';
  }

  function formatAge(seconds) {
    const minutes = Math.floor(Math.max(0, seconds) / 60);
    if (minutes < 1) return 'Just received';
    if (minutes < 60) return `${minutes} min ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} hr ${minutes % 60} min ago`;
    return `${Math.floor(hours / 24)} day ${hours % 24} hr ago`;
  }

  function updateTicketAges() {
    const elapsed = Math.max(0, (Date.now() - ordersReceivedAt) / 1000);
    ticketGrid.querySelectorAll('.snc-ticket__age').forEach((time) => {
      const age = Number(time.dataset.ageSeconds);
      const label = formatAge(age + elapsed);
      time.textContent = `${time.dataset.receivedLabel} · Order age: ${label}`;
    });
  }

  function updateFilterCounts() {
    // Counts cover the loaded active queue, independent of the selected filter.
    filterTabs.forEach((tab) => {
      const status = tab.dataset.status;
      const count = status === 'all' ? latestOrders.length
        : latestOrders.filter((order) => order.kitchen_status === status).length;
      tab.querySelector('.snc-kitchen-count').textContent = `(${count})`;
    });
  }

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

      const received = /^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})/.exec(String(order.created_at || ''));
      const ageSeconds = Number(order.age_seconds);
      let receivedTime = null;
      if (received && Number.isFinite(ageSeconds) && order.age_seconds !== null) {
        const time = document.createElement('time');
        time.className = 'snc-ticket__age';
        time.dateTime = String(order.created_at).replace(' ', 'T');
        time.title = `Received ${order.created_at}`;
        const hour = Number(received[2]);
        time.dataset.receivedLabel = `${hour % 12 || 12}:${received[3]} ${hour >= 12 ? 'PM' : 'AM'}`;
        time.dataset.ageSeconds = String(ageSeconds);
        receivedTime = time;
      }

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
        if (Number(item.quantity) > 1) qtySpan.classList.add('snc-ticket__qty--multiple');
        qtySpan.textContent = `×${item.quantity}`;

        li.appendChild(nameSpan);
        li.appendChild(qtySpan);
        itemsList.appendChild(li);
      });

      card.appendChild(head);
      if (receivedTime) card.appendChild(receivedTime);
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
    updateTicketAges();
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
    if (refreshBtn) refreshBtn.disabled = true;
    try {
      const res = await fetch('../api/orders.php', {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
        signal: AbortSignal.timeout(8000)
      });
      const data = await res.json().catch(() => null);
      if (seq !== requestSeq) return;
      if (!res.ok || !data || !data.ok || !Array.isArray(data.orders)) throw new Error('Queue unavailable');

      latestOrders = Array.isArray(data.orders) ? data.orders : [];
      ordersReceivedAt = Date.now();
      updateFilterCounts();
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
      lastUpdatedAt = Date.now();
      updatesUnavailable = false;
      updateRefreshStatus();
    } catch (e) {
      if (seq !== requestSeq) return;
      updatesUnavailable = true;
      updateRefreshStatus();
      // Keep existing tickets on screen; avoid showing a false empty queue.
      if (firstLoad) {
        const empty = document.createElement('div');
        empty.className = 'snc-kitchen-empty';
        empty.textContent = 'Unable to load kitchen tickets. Retrying automatically, or select Refresh.';
        ticketGrid.replaceChildren(empty);
      }
    } finally {
      if (seq === requestSeq && refreshBtn) refreshBtn.disabled = false;
    }
  }

  filterTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      currentFilter = tab.dataset.status || 'all';
      filterTabs.forEach((t) => {
        t.classList.toggle('is-active', t === tab);
        t.setAttribute('aria-pressed', String(t === tab));
      });
      renderTickets();
    });
  });

  if (refreshBtn) {
    refreshBtn.addEventListener('click', fetchOrders);
  }

  fetchOrders();
  window.setInterval(fetchOrders, 10000);
  window.setInterval(updateTicketAges, 30000);
  window.setInterval(updateRefreshStatus, 5000);
})();
