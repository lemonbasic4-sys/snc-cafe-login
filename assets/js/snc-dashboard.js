/** SNC 2018 Café & Resto | Owner Dashboard V5.1 Interactions */
(() => {
  'use strict';

  const periodButtons = document.querySelectorAll('.snc-period-btn');
  const netSalesEl = document.getElementById('snc-kpi-net-sales');
  const ordersEl = document.getElementById('snc-kpi-orders');
  const aovEl = document.getElementById('snc-kpi-aov');
  const attentionEl = document.getElementById('snc-kpi-attention');
  const chartSubtitleEl = document.getElementById('snc-chart-subtitle');
  const chartComparisonEl = document.getElementById('snc-chart-comparison');
  const bestSellersList = document.getElementById('snc-bestsellers-list');
  const svgBars = document.querySelectorAll('.snc-svg-bar');
  const svgLabels = document.querySelectorAll('.snc-svg-label');
  const svgValues = document.querySelectorAll('.snc-svg-val');

  if (!periodButtons.length || !netSalesEl || !ordersEl || !aovEl || !attentionEl) return;

  // Demo Data baseline (labeled Demo Data in UI until POS/Orders backend is connected)
  const PERIOD_DATA = {
    today: {
      netSales: '₱18,450.00',
      orders: '64',
      aov: '₱288.28',
      attention: '2',
      comparison: 'Demo Data · +8.4% vs. yesterday',
      subtitle: 'Hourly revenue performance (Today)',
      series: [
        { label: '9A', value: 1800, text: '₱1.8k' },
        { label: '11A', value: 3200, text: '₱3.2k' },
        { label: '1P', value: 4400, text: '₱4.4k' },
        { label: '3P', value: 2600, text: '₱2.6k' },
        { label: '5P', value: 3550, text: '₱3.6k' },
        { label: '7P', value: 2100, text: '₱2.1k' },
        { label: '9P', value: 800, text: '₱0.8k' }
      ],
      bestSellers: [
        { name: 'Iced Spanish Latte', meta: '24 sold · Beverages', revenue: '₱3,840.00' },
        { name: 'Bistre Cold Brew', meta: '18 sold · Beverages', revenue: '₱2,880.00' },
        { name: 'Truffle Cream Pasta', meta: '11 sold · Kitchen', revenue: '₱3,520.00' }
      ]
    },
    week: {
      netSales: '₱124,890.00',
      orders: '412',
      aov: '₱303.13',
      attention: '2',
      comparison: 'Demo Data · +5.1% vs. prior week',
      subtitle: 'Daily revenue performance (This Week)',
      series: [
        { label: 'Mon', value: 14200, text: '₱14k' },
        { label: 'Tue', value: 15800, text: '₱16k' },
        { label: 'Wed', value: 17100, text: '₱17k' },
        { label: 'Thu', value: 16400, text: '₱16k' },
        { label: 'Fri', value: 22900, text: '₱23k' },
        { label: 'Sat', value: 24100, text: '₱24k' },
        { label: 'Sun', value: 14390, text: '₱14k' }
      ],
      bestSellers: [
        { name: 'Iced Spanish Latte', meta: '148 sold · Beverages', revenue: '₱23,680.00' },
        { name: 'Truffle Cream Pasta', meta: '74 sold · Kitchen', revenue: '₱23,680.00' },
        { name: 'Caramel Macchiato', meta: '112 sold · Beverages', revenue: '₱17,920.00' }
      ]
    },
    month: {
      netSales: '₱498,320.00',
      orders: '1,648',
      aov: '₱302.38',
      attention: '2',
      comparison: 'No comparison available',
      subtitle: 'Weekly revenue performance (This Month)',
      series: [
        { label: 'W1', value: 112000, text: '₱112k' },
        { label: 'W2', value: 126500, text: '₱127k' },
        { label: 'W3', value: 134800, text: '₱135k' },
        { label: 'W4', value: 125020, text: '₱125k' },
        { label: '—', value: 0, text: '₱0' },
        { label: '—', value: 0, text: '₱0' },
        { label: '—', value: 0, text: '₱0' }
      ],
      bestSellers: [
        { name: 'Iced Spanish Latte', meta: '592 sold · Beverages', revenue: '₱94,720.00' },
        { name: 'Truffle Cream Pasta', meta: '286 sold · Kitchen', revenue: '₱91,520.00' },
        { name: 'Bistre Cold Brew', meta: '418 sold · Beverages', revenue: '₱66,880.00' }
      ]
    }
  };

  function updateChart(series) {
    const maxVal = Math.max(1, ...series.map((item) => item.value));
    const maxBarHeight = 128;
    const baseLineY = 152;

    series.forEach((item, index) => {
      const bar = svgBars[index];
      const label = svgLabels[index];
      const val = svgValues[index];
      if (!bar || !label || !val) return;

      const height = item.value > 0 ? Math.max(8, Math.round((item.value / maxVal) * maxBarHeight)) : 4;
      const y = baseLineY - height;

      bar.setAttribute('y', String(y));
      bar.setAttribute('height', String(height));
      label.textContent = item.label;
      val.textContent = item.text;
    });
  }

  function updateBestSellers(items) {
    if (!bestSellersList) return;
    bestSellersList.innerHTML = '';
    items.forEach((item) => {
      const li = document.createElement('li');
      li.className = 'snc-list-item';

      const main = document.createElement('div');
      main.className = 'snc-list-item__main';

      const title = document.createElement('p');
      title.className = 'snc-list-item__title';
      title.textContent = item.name;

      const desc = document.createElement('p');
      desc.className = 'snc-list-item__desc';
      desc.textContent = item.meta;

      const metric = document.createElement('span');
      metric.className = 'snc-list-item__metric';
      metric.textContent = item.revenue;

      main.appendChild(title);
      main.appendChild(desc);
      li.appendChild(main);
      li.appendChild(metric);
      bestSellersList.appendChild(li);
    });
  }

  function applyPeriod(periodKey) {
    const data = PERIOD_DATA[periodKey] || PERIOD_DATA.today;
    periodButtons.forEach((btn) => {
      const active = btn.dataset.period === periodKey;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-pressed', String(active));
    });

    netSalesEl.textContent = data.netSales;
    ordersEl.textContent = data.orders;
    aovEl.textContent = data.aov;
    attentionEl.textContent = data.attention;
    if (chartSubtitleEl) chartSubtitleEl.textContent = data.subtitle;
    if (chartComparisonEl) chartComparisonEl.textContent = data.comparison;

    updateChart(data.series);
    updateBestSellers(data.bestSellers);
  }

  periodButtons.forEach((btn) => {
    btn.addEventListener('click', () => applyPeriod(btn.dataset.period));
  });

  applyPeriod('today');
})();
