/** Inventory forms retain their request keys on retries to prevent double stock changes. */
(() => {
  'use strict';
  const csrf = document.getElementById('snc-inventory-csrf');
  if (!csrf) return;
  const ingredientDialog = document.getElementById('snc-ingredient-dialog');
  const movementDialog = document.getElementById('snc-movement-dialog');
  const ingredientForm = document.getElementById('snc-ingredient-form');
  const movementForm = document.getElementById('snc-movement-form');
  const requestKey = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), (byte) => byte.toString(16).padStart(2, '0')).join('');
  let movementUnit = 'g';
  let movementBalance = 0;
  const thousandths = (value) => {
    if (!/^\d{1,7}(?:\.\d{1,3})?$/.test(value)) return null;
    const [whole, fraction = ''] = value.split('.');
    return Number(whole) * 1000 + Number(fraction.padEnd(3, '0'));
  };
  const formatStock = (value) => `${(value / 1000).toLocaleString('en-PH', { maximumFractionDigits: 3 })} ${movementUnit}`;
  function balancePreview() {
    const quantity = movementForm.elements.quantity;
    const preview = document.getElementById('snc-stock-preview');
    quantity.setCustomValidity('');
    preview.parentElement.removeAttribute('data-invalid');
    const entered = thousandths(quantity.value);
    if (entered === null || entered <= 0 || (movementUnit === 'pcs' && entered % 1000)) {
      preview.textContent = quantity.value === '' ? 'Enter a quantity to preview the balance.' : 'Enter a valid quantity to preview the balance.';
      return;
    }
    const amount = entered * (['kg','L'].includes(movementForm.elements.entry_unit.value) ? 1000 : 1);
    const removing = ['usage','waste','correction_out'].includes(movementForm.elements.movement_type.value);
    const after = movementBalance + (removing ? -amount : amount);
    const equation = `${formatStock(movementBalance)} ${removing ? '−' : '+'} ${formatStock(amount)} = ${formatStock(after)}`;
    const issue = after < 0 ? 'Not enough stock for this adjustment.' : (after > 9999999999 || amount > 9999999999) ? 'This adjustment exceeds the stock limit.' : '';
    preview.textContent = equation + (issue ? ` — ${issue}` : '');
    if (issue) {
      quantity.setCustomValidity(issue);
      preview.parentElement.dataset.invalid = 'true';
    }
  }
  function ingredientSteps() {
    const unit = ingredientForm.elements.base_unit.value;
    const step = unit === 'pcs' ? '1' : '0.001';
    ['opening_stock','low_stock_threshold'].forEach((name) => { ingredientForm.elements[name].step = step; });
    document.getElementById('snc-threshold-label').textContent = `Low-stock threshold (${unit})`;
  }
  function openIngredient(item) {
    ingredientForm.reset();
    ingredientForm.querySelector('[data-form-message]').textContent = '';
    ingredientForm.elements.request_key.value = requestKey();
    ingredientForm.elements.id.value = item ? item.id : '0';
    ingredientForm.elements.base_unit.disabled = Boolean(item);
    ingredientForm.querySelectorAll('[data-opening-field]').forEach((field) => {
      field.hidden = Boolean(item);
      field.querySelector('input').disabled = Boolean(item);
    });
    if (item) ['name','base_unit','is_active','low_stock_threshold'].forEach((name) => { ingredientForm.elements[name].value = item[name]; });
    if (item) ingredientForm.elements.low_stock_threshold.value = String(Number(item.low_stock_threshold));
    document.getElementById('snc-ingredient-title').textContent = item ? 'Edit Ingredient' : 'Add Ingredient';
    document.getElementById('snc-ingredient-subtitle').textContent = item ? 'Update ingredient details and stock alerts.' : 'Choose a base unit for stock and future recipes.';
    document.getElementById('snc-unit-hint').textContent = item ? `Stock and thresholds use ${item.base_unit}. The base unit is fixed; use Adjust Stock to change stock.` : 'Opening stock and threshold use the selected base unit.';
    ingredientSteps(); ingredientDialog.showModal();
  }
  function movementHint() {
    const unit = movementForm.elements.entry_unit.value;
    const type = movementForm.elements.movement_type.value;
    movementForm.elements.quantity.step = movementUnit === 'pcs' ? '1' : '0.001';
    movementForm.elements.quantity.min = movementUnit === 'pcs' ? '1' : '0.001';
    const conversion = unit === 'kg' ? '1 kg = 1,000 g. ' : unit === 'L' ? '1 L = 1,000 ml. ' : '';
    document.getElementById('snc-movement-hint').textContent = conversion + (['usage','waste','correction_out'].includes(type) ? 'This removes stock. The balance cannot go below zero.' : 'This adds stock.');
    balancePreview();
  }
  document.getElementById('snc-add-ingredient')?.addEventListener('click', () => openIngredient(null));
  document.querySelectorAll('[data-edit-ingredient]').forEach((button) => button.addEventListener('click', () => openIngredient(JSON.parse(button.dataset.editIngredient))));
  document.querySelectorAll('[data-stock-movement]').forEach((button) => button.addEventListener('click', () => {
    const item = JSON.parse(button.dataset.stockMovement);
    movementForm.reset(); movementForm.querySelector('[data-form-message]').textContent = '';
    movementForm.elements.id.value = item.id; movementForm.elements.request_key.value = requestKey(); movementUnit = item.base_unit;
    movementBalance = thousandths(item.current_stock);
    document.getElementById('snc-movement-ingredient').textContent = `${item.name} · Current stock: ${Number(item.current_stock).toLocaleString('en-PH', { maximumFractionDigits: 3 })} ${item.base_unit}`;
    const select = movementForm.elements.entry_unit;
    select.replaceChildren(new Option(item.base_unit, item.base_unit));
    if (item.base_unit === 'g') select.appendChild(new Option('kg', 'kg'));
    if (item.base_unit === 'ml') select.appendChild(new Option('L', 'L'));
    movementHint(); movementDialog.showModal();
  }));
  ingredientForm.elements.base_unit.addEventListener('change', ingredientSteps);
  movementForm.elements.entry_unit.addEventListener('change', movementHint);
  movementForm.elements.movement_type.addEventListener('change', movementHint);
  movementForm.elements.quantity.addEventListener('input', balancePreview);
  document.querySelectorAll('[data-inventory-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
  document.querySelectorAll('[data-inventory-save]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (form.dataset.saving === 'true') return;
    const error = form.querySelector('[data-form-message]'); error.textContent = '';
    const submit = form.querySelector('[type="submit"]');
    const data = Object.fromEntries(new FormData(form));
    form.dataset.saving = 'true'; submit.disabled = true;
    // Keep a submitted form open until its outcome is known.
    const dialog = form.closest('dialog');
    dialog.querySelectorAll('[data-inventory-close]').forEach((button) => { button.disabled = true; });
    try {
      const response = await fetch('../api/inventory.php', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': csrf.value }, body: JSON.stringify(data) });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to save inventory.');
      window.location.reload();
    } catch (e) {
      error.textContent = e.message || 'Connection error. Try again with the same details to avoid a duplicate movement.';
    } finally {
      form.dataset.saving = 'false'; submit.disabled = false;
      dialog.querySelectorAll('[data-inventory-close]').forEach((button) => { button.disabled = false; });
    }
  }));
  [ingredientDialog,movementDialog].forEach((dialog) => dialog.addEventListener('cancel', (event) => {
    if (dialog.querySelector('[data-inventory-save]').dataset.saving === 'true') event.preventDefault();
  }));
})();
