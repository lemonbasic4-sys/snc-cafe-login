/** Recipe quantities always use the ingredient's fixed base unit. */
(() => {
  'use strict';
  const form = document.getElementById('snc-recipe-form');
  if (!form) return;
  const lines = document.getElementById('snc-recipe-lines');
  const add = document.getElementById('snc-add-recipe-ingredient');
  const save = document.getElementById('snc-save-recipe');
  function refresh() {
    const rows = [...lines.querySelectorAll('[data-recipe-row]')];
    document.getElementById('snc-recipe-count').textContent = rows.length;
    document.getElementById('snc-recipe-empty').hidden = rows.length > 0;
    const ids = rows.map(row => row.querySelector('select').value);
    rows.forEach((row, index) => {
      const select = row.querySelector('select');
      const option = select.selectedOptions[0];
      const unit = option?.dataset.unit;
      row.querySelector('[data-recipe-unit]').textContent = unit || '—';
      row.querySelector('[data-remove-ingredient]').setAttribute('aria-label', `Remove ${select.value ? option.textContent : `ingredient row ${index + 1}`}`);
      row.querySelector('[data-ingredient-warning]').hidden = option?.dataset.active !== '0';
      const quantity = row.querySelector('input');
      quantity.step = unit === 'pcs' ? '1' : '0.001';
      quantity.min = unit === 'pcs' ? '1' : '0.001';
      select.setCustomValidity(select.value && ids.filter(id => id === select.value).length > 1 ? 'This ingredient is already in the recipe. Combine its quantities into one row.' : '');
    });
    add.disabled = rows.length >= 100 || !document.getElementById('snc-recipe-row-template').content.querySelector('option[data-unit]');
    save.textContent = rows.length || form.elements.stock_tracking.value === 'recipe' ? 'Save Recipe' : 'Save Empty Recipe';
  }
  add.addEventListener('click', () => {
    lines.append(document.getElementById('snc-recipe-row-template').content.cloneNode(true));
    refresh(); lines.lastElementChild.querySelector('select').focus();
  });
  lines.addEventListener('click', event => {
    const button = event.target.closest('[data-remove-ingredient]');
    if (!button) return;
    button.closest('[data-recipe-row]').remove(); refresh(); add.focus();
  });
  lines.addEventListener('change', refresh);
  form.elements.stock_tracking.addEventListener('change', refresh);
  form.addEventListener('submit', event => {
    if (form.dataset.saving) { event.preventDefault(); return; }
    form.dataset.saving = 'true'; save.disabled = true; add.disabled = true;
    document.getElementById('snc-recipe-feedback').textContent = 'Saving recipe…';
  });
  window.addEventListener('pageshow', () => { delete form.dataset.saving; save.disabled = false; refresh(); });
  refresh();
})();
