/** SNC 2018 | Thermal receipt preview. Printing never updates order or payment state. */
(() => {
  'use strict';
  const widthSelect = document.getElementById('snc-paper-width');
  const printButton = document.getElementById('snc-print-receipt');
  if (!widthSelect || !printButton) return;

  function applyWidth(value) {
    widthSelect.value = value === '58' ? '58' : '80';
    document.body.classList.toggle('snc-receipt-page--58', widthSelect.value === '58');
  }

  applyWidth(widthSelect.value);
  widthSelect.addEventListener('change', () => {
    applyWidth(widthSelect.value);
    // Preview changes are local to this page; the owner controls the saved default.
  });
  printButton.addEventListener('click', () => window.print());
})();
