/** Shared order details; voiding is exposed only by owner-authorized responses. */
(() => {
  'use strict';
  const dialog = document.getElementById('snc-order-dialog');
  if (!dialog || typeof dialog.showModal !== 'function') return;
  const status = document.getElementById('snc-order-dialog-status');
  const content = document.getElementById('snc-order-dialog-content');
  const print = document.getElementById('snc-order-dialog-print');
  const retry = document.getElementById('snc-order-dialog-retry');
  const requestButton = document.getElementById('snc-order-dialog-request');
  const backButton = document.getElementById('snc-order-dialog-back');
  const sendButton = document.getElementById('snc-order-dialog-send-request');
  const title = document.getElementById('snc-order-dialog-title');
  const body = dialog.querySelector('.snc-order-dialog__body');
  let detailsView;
  let requestForm;
  let detailsScroll = 0;
  let canPrint = false;
  let controller;
  let currentId;
  let trigger;
  const money = (value) => '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const label = (value) => String(value || '').replace(/^./, (char) => char.toUpperCase());
  function element(tag, text, className) {
    const node = document.createElement(tag);
    if (text !== undefined) node.textContent = String(text);
    if (className) node.className = className;
    return node;
  }
  function fact(list, name, value, className) {
    const row = element('div', undefined, className);
    row.append(element('dt', name), element('dd', value));
    list.appendChild(row);
  }
  function render(data) {
    const order = data.order;
    const payment = data.payment;
    const head = element('div', undefined, 'snc-pos-head');
    const voided=order.payment_status==='voided';
    const badge = element('span', voided?'Voided':label(order.kitchen_status), 'snc-status-pill');
    badge.dataset.status = voided?'voided':order.kitchen_status;
    head.append(element('h3', order.order_number), badge);
    const facts = element('dl', undefined, 'snc-order-facts');
    fact(facts, 'Ordered at', order.created_at);
    fact(facts, 'Cashier', order.cashier_name || 'Staff');
    fact(facts, 'Order type', order.order_type === 'takeout' ? 'Takeout' : 'Dine-In');
    fact(facts, 'Table / Customer', order.table_or_note || '—');
    fact(facts, 'Payment status', label(order.payment_status));
    if (order.completed_at) fact(facts, 'Completed at', order.completed_at);
    const scroll = element('div', undefined, 'snc-orders-scroll');
    scroll.tabIndex = 0;
    scroll.setAttribute('role', 'region');
    scroll.setAttribute('aria-label', 'Order items');
    const table = element('table', undefined, 'snc-orders-table snc-order-items');
    const thead = element('thead');
    const titles = element('tr');
    ['Item', 'Qty', 'Unit Price', 'Amount'].forEach((title) => {
      const th = element('th', title);
      th.scope = 'col';
      titles.appendChild(th);
    });
    thead.appendChild(titles);
    const tbody = element('tbody');
    data.items.forEach((item) => {
      const row = element('tr');
      [item.item_name, item.quantity, money(item.unit_price), money(item.line_total)].forEach((value) => row.appendChild(element('td', value)));
      tbody.appendChild(row);
    });
    table.append(thead, tbody);
    scroll.appendChild(table);
    const amounts = element('dl', undefined, 'snc-order-amounts');
    fact(amounts, 'Subtotal', money(order.subtotal));
    fact(amounts, 'Discount', money(order.discount_amount));
    fact(amounts, 'Total', money(order.total_amount), 'snc-summary-line--total');
    const summary = element('div', undefined, 'snc-order-dialog__summary');
    const totalsSection = element('section');
    totalsSection.setAttribute('aria-label', 'Order totals');
    totalsSection.append(element('h3', 'Order totals'), amounts);
    if (payment) {
      const paymentSection = element('section');
      paymentSection.setAttribute('aria-label', 'Payment details');
      const paymentFacts = element('dl', undefined, 'snc-order-amounts');
      fact(paymentFacts, 'Method', String(payment.method).toUpperCase());
      fact(paymentFacts, 'Paid at', payment.paid_at);
      fact(paymentFacts, 'Amount tendered', money(payment.amount_tendered));
      fact(paymentFacts, 'Change', money(payment.change_amount));
      paymentSection.append(element('h3', 'Payment details'), paymentFacts);
      summary.appendChild(paymentSection);
    }
    summary.appendChild(totalsSection);
    content.replaceChildren(head, facts, scroll, summary);
    if(voided){
      const notice=element('section',undefined,'snc-void-notice');notice.append(element('h3','Order Voided'));
      if(data.void){notice.append(element('p',data.void.actor_name+' · '+data.void.voided_at),element('p','Reason: '+data.void.reason),element('p','Kitchen status at void: '+label(data.void.kitchen_status)));}
      else notice.append(element('p','Void details are not available for this older record.'));
      notice.append(element('p','Voiding does not refund payment or return ingredients to stock.'));content.append(notice);
    }
    if(window.SncVoidRequest&&data.void_request)content.append(window.SncVoidRequest.create(data));
    if(data.can_void&&window.SncVoidOrder)content.append(window.SncVoidOrder.create(order,data.void_csrf));
    print.href = `receipt.php?order_id=${encodeURIComponent(order.id)}`;
    print.hidden = dialog.dataset.printDisabled === 'true' || !payment || order.payment_status !== 'paid';
    canPrint=!print.hidden;
    detailsView=element('div');detailsView.append(...content.childNodes);content.append(detailsView);
    requestForm=null;
    title.textContent='Order Details';backButton.hidden=true;sendButton.hidden=true;
    requestButton.hidden=!(data.can_request_void&&window.SncVoidRequest);
    requestButton.textContent=data.void_request?'Request Void Again':'Request Void';
    if(!requestButton.hidden){requestForm=window.SncVoidRequest.createForm(data);requestForm.hidden=true;content.append(requestForm);}
    body.scrollTop=0;
  }
  requestButton.addEventListener('click',()=>{
    if(!requestForm)return;
    detailsScroll=body.scrollTop;detailsView.hidden=true;requestForm.hidden=false;
    title.textContent='Request Void';requestButton.hidden=true;print.hidden=true;backButton.hidden=false;sendButton.hidden=false;
    dialog.classList.add('is-requesting-void');body.scrollTop=0;requestForm.querySelector('[name="reason"]').focus();
  });
  backButton.addEventListener('click',()=>{
    if(requestForm?.dataset.saving)return;
    requestForm.hidden=true;detailsView.hidden=false;title.textContent='Order Details';
    backButton.hidden=true;sendButton.hidden=true;requestButton.hidden=false;
    print.hidden=!canPrint;
    dialog.classList.remove('is-requesting-void');body.scrollTop=detailsScroll;requestButton.focus();
  });
  async function load(id) {
    if (controller) controller.abort();
    const request = new AbortController();
    controller = request;
    const timeout = window.setTimeout(() => request.abort(), 10000);
    status.hidden = false;
    status.textContent = 'Loading order details…';
    content.hidden = true;
    print.hidden = true;
    retry.hidden = true;
    requestButton.hidden=true;backButton.hidden=true;sendButton.hidden=true;
    title.textContent='Order Details';dialog.classList.remove('is-requesting-void');requestForm=null;
    content.replaceChildren();
    try {
      const response = await fetch(`../api/order_details.php?order_id=${encodeURIComponent(id)}`, {
        headers: { Accept: 'application/json' }, cache: 'no-store', signal: request.signal
      });
      const data = await response.json();
      if (controller !== request || !dialog.open) return;
      if (!response.ok || !data.ok) throw new Error(data.message || 'Unable to load order details.');
      render(data);
      content.hidden = false;
      status.hidden = true;
    } catch (error) {
      if (controller !== request || !dialog.open) return;
      status.textContent = error.name === 'AbortError' ? 'The request timed out. Please try again.' : error.message || 'Unable to load order details.';
      retry.hidden = false;
    } finally {
      window.clearTimeout(timeout);
    }
  }
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-order-details]');
    if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    trigger = link;
    currentId = link.dataset.orderDetails;
    if (!dialog.open) dialog.showModal();
    load(currentId);
  });
  document.addEventListener('snc-order-updated',()=>{if(dialog.open)load(currentId);});
  retry.addEventListener('click', () => load(currentId));
  dialog.addEventListener('close', () => {
    if (controller) controller.abort();
    controller = null;
    if (trigger && trigger.isConnected) trigger.focus();
  });
})();
