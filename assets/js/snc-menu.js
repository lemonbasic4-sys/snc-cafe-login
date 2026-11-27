/** Owner menu management: external scripts only; prices and permissions are validated server-side. */
(() => {
  'use strict';
  const csrf = document.getElementById('snc-menu-csrf');
  if (!csrf) return;
  const itemDialog = document.getElementById('snc-item-dialog');
  const categoryDialog = document.getElementById('snc-category-dialog');
  const itemForm = document.getElementById('snc-item-form');
  const title = document.getElementById('snc-item-title');
  let categorySaving = false;
  function node(tag, text, className) {
    const element = document.createElement(tag);
    if (text !== undefined) element.textContent = String(text);
    if (className) element.className = className;
    return element;
  }
  function categoryRow(category) {
    const form = node('form', undefined, 'snc-category-row snc-category-row--existing');
    form.dataset.menuSave = '';
    [['action', 'rename_category'], ['id', category.id]].forEach(([name, value]) => {
      const input = node('input');
      input.type = 'hidden'; input.name = name; input.value = value;
      form.appendChild(input);
    });
    const name = node('input');
    name.name = 'name'; name.maxLength = 60; name.required = true;
    const count = node('span', undefined, 'snc-menu-count');
    const button = node('button', 'Rename', 'snc-menu-button');
    button.type = 'submit';
    const error = node('span', undefined, 'snc-menu-error');
    error.dataset.formMessage = ''; error.setAttribute('role', 'status');
    form.append(name, count, button, error);
    return form;
  }
  function syncCategories(result) {
    const categories = result.categories;
    const changed = categories.find((entry) => String(entry.id) === String(result.category_id));
    const list = categoryDialog.querySelector('.snc-category-list');
    const rows = new Map(Array.from(list.querySelectorAll('form')).map((form) => [form.elements.id.value, form]));
    const addForm = categoryDialog.querySelector('.snc-category-row--new');
    list.querySelector('.snc-menu-hint')?.remove();
    categories.forEach((category, index) => {
      let row = rows.get(String(category.id));
      const isNew = !row;
      if (isNew) row = categoryRow(category);
      if (isNew || String(category.id) === String(result.category_id)) {
        row.elements.name.value = category.name;
        row.elements.name.defaultValue = category.name;
      }
      row.elements.name.setAttribute('aria-label', `Category name: ${category.name}`);
      const count = row.querySelector('.snc-menu-count');
      count.textContent = category.item_count;
      count.setAttribute('aria-label', `${category.item_count} items in ${category.name}`);
      row.querySelector('[type="submit"]').setAttribute('aria-label', `Rename ${category.name}`);
      // The first child is the shared column header.
      if (list.children[index + 1] !== row) list.insertBefore(row, list.children[index + 1] || null);
    });
    document.getElementById('snc-category-total').textContent = `${categories.length} total`;
    const filter = document.querySelector('.snc-menu-filters select[name="category"]');
    const selectedFilter = changed && filter.value === result.old_name ? changed.name : filter.value;
    const selectedItem = itemForm.elements.category_id.value;
    filter.replaceChildren(new Option('All Categories', ''));
    itemForm.elements.category_id.replaceChildren(new Option('Choose a category', ''));
    categories.forEach((category) => {
      filter.appendChild(new Option(category.name, category.name));
      const option = new Option(category.name, String(category.id));
      option.dataset.categoryName = category.name;
      itemForm.elements.category_id.appendChild(option);
    });
    filter.value = selectedFilter;
    itemForm.elements.category_id.value = selectedItem;
    if (changed && result.old_name !== null) {
      document.querySelectorAll('[data-edit-item]').forEach((button) => {
        const item = JSON.parse(button.dataset.editItem);
        if (item.category !== result.old_name) return;
        item.category = changed.name;
        button.dataset.editItem = JSON.stringify(item);
        button.closest('tr').children[1].textContent = changed.name;
      });
      const url = new URL(window.location.href);
      if (url.searchParams.get('category') === result.old_name) {
        url.searchParams.set('category', changed.name);
        window.history.replaceState(null, '', url);
      }
    }
    if (categories.length) itemForm.querySelector('.snc-menu-hint')?.remove();
    if (result.old_name === null) {
      addForm.elements.name.value = '';
      if (categoryDialog.open) addForm.elements.name.focus();
    }
    document.getElementById('snc-category-feedback').textContent = result.message;
  }
  document.getElementById('snc-add-item')?.addEventListener('click', () => {
    itemForm.reset();
    itemForm.elements.id.value = '0';
    title.textContent = 'Add Item';
    itemForm.querySelector('[data-form-message]').textContent = '';
    itemDialog.showModal();
  });
  document.getElementById('snc-manage-categories')?.addEventListener('click', () => categoryDialog.showModal());
  document.querySelectorAll('[data-edit-item]').forEach((button) => button.addEventListener('click', () => {
    const item = JSON.parse(button.dataset.editItem);
    itemForm.reset();
    ['id', 'name', 'price', 'is_available'].forEach((name) => { itemForm.elements[name].value = item[name]; });
    const option = Array.from(itemForm.elements.category_id.options).find((entry) => entry.dataset.categoryName === item.category);
    itemForm.elements.category_id.value = option ? option.value : '';
    title.textContent = 'Edit Item';
    itemForm.querySelector('[data-form-message]').textContent = '';
    itemDialog.showModal();
  }));
  document.querySelectorAll('[data-close-dialog]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.matches('[data-menu-save]')) return;
    event.preventDefault();
    if (form.dataset.saving === 'true') return;
    const isCategory = categoryDialog.contains(form);
    if (isCategory && categorySaving) return;
    const message = form.querySelector('[data-form-message]');
    message.textContent = '';
    const submit = form.querySelector('[type="submit"]');
    const data = Object.fromEntries(new FormData(form));
    form.dataset.saving = 'true';
    submit.disabled = true;
    if (isCategory) {
      categorySaving = true;
      categoryDialog.querySelectorAll('[type="submit"]').forEach((button) => { button.disabled = true; });
      document.getElementById('snc-category-feedback').textContent = '';
    }
    try {
      const response = await fetch('../api/menu.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf.value, Accept: 'application/json' }, body: JSON.stringify(data) });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to save changes.');
      if (isCategory) {
        syncCategories(result);
        return;
      }
      window.location.reload();
    } catch (error) {
      message.textContent = error.message || 'Unable to save changes. Please try again.';
    } finally {
      form.dataset.saving = 'false';
      submit.disabled = false;
      if (isCategory) {
        categorySaving = false;
        categoryDialog.querySelectorAll('[type="submit"]').forEach((button) => { button.disabled = false; });
      }
    }
  });
})();
