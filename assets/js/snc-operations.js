(() => {
  'use strict';
  const form=document.getElementById('snc-operation-filters');if(!form)return;
  const body=document.getElementById('snc-operations-orders');const refresh=document.getElementById('snc-operations-refresh');const updated=document.getElementById('snc-operations-update');const error=document.getElementById('snc-operations-error');const pagination=document.getElementById('snc-operations-pagination');
  let applied={status:form.elements.status.value,type:form.elements.type.value,page:Number(new URL(location.href).searchParams.get('page'))||1};let request;let lastSuccess=error.textContent.trim()?0:Date.now();let receivedAt=Date.now();let unavailable=!lastSuccess;let stopped=false;
  const text=(el,value)=>{if(el.textContent!==String(value))el.textContent=String(value);};
  const node=(tag,value,className)=>{const el=document.createElement(tag);if(value!==undefined)el.textContent=String(value);if(className)el.className=className;return el;};
  function age(seconds){const mins=Math.floor(Math.max(0,seconds)/60);if(mins<1)return 'Just received';if(mins<60)return mins+' min ago';const hours=Math.floor(mins/60);if(hours<24)return hours+' hr '+mins%60+' min ago';return Math.floor(hours/24)+' day '+hours%24+' hr ago';}
  function clock(){
    const elapsed=Math.floor((Date.now()-receivedAt)/1000);body.querySelectorAll('[data-operation-age]').forEach(el=>text(el,age(Number(el.dataset.operationAge)+elapsed)));
    if(stopped)return;if(!lastSuccess){text(updated,'Updates unavailable · Retrying every 10s');return;}const seconds=Math.max(0,Math.floor((Date.now()-lastSuccess)/1000));
    text(updated,(unavailable?'Updates unavailable · Last success ':'Updated ')+(seconds<10?'just now':seconds<60?seconds+'s ago':Math.floor(seconds/60)+' min ago')+' · '+(unavailable?'Retrying every 10s':'Auto-refresh every 10s'));
  }
  function rowFor(order){
    const row=node('tr');row.dataset.operationOrder=order.id;for(let i=0;i<7;i++)row.append(node('td'));return row;
  }
  function render(data){
    receivedAt=Date.now();lastSuccess=Date.now();unavailable=false;
    Object.entries(data.counts).forEach(([key,value])=>{const count=document.querySelector('[data-operation-count="'+key+'"]');if(count)text(count,value);});
    text(document.getElementById('snc-operations-total'),data.total);text(document.getElementById('snc-operations-page'),'Page '+data.page+' of '+data.pages);
    const existing=new Map([...body.querySelectorAll('[data-operation-order]')].map(row=>[row.dataset.operationOrder,row]));const keep=new Set();
    data.orders.forEach((order,index)=>{
      const id=String(order.id);keep.add(id);let row=existing.get(id);if(!row)row=rowFor(order);
      const signature=JSON.stringify(order);if(row.dataset.signature!==signature){
        row.dataset.signature=signature;const cells=row.cells;
        const received=node('small',order.created_at);const elapsed=node('small',age(order.age_seconds));elapsed.dataset.operationAge=order.age_seconds;
        cells[0].replaceChildren(node('strong',order.order_number),node('br'),received,node('br'),elapsed);
        cells[1].replaceChildren(node('span',order.order_type==='takeout'?'Takeout':'Dine-In'),node('br'),node('span',order.table_or_note||'—','snc-record-muted'));
        const items=node('ul',undefined,'snc-operation-items');order.items.slice(0,3).forEach(item=>items.append(node('li',item.quantity+' × '+item.item_name)));cells[2].replaceChildren(items);if(order.items.length>3)cells[2].append(node('small','+'+(order.items.length-3)+' more items · View Details'));
        text(cells[3],order.cashier_name||'Staff');cells[4].replaceChildren(node('span','Paid · '+(order.payment_method?order.payment_method.toUpperCase():'Not recorded')));if(order.void_request_status)cells[4].append(node('br'),node('small','Void request: '+order.void_request_status[0].toUpperCase()+order.void_request_status.slice(1)));
        const badge=node('span',order.kitchen_status[0].toUpperCase()+order.kitchen_status.slice(1),'snc-status-pill');badge.dataset.status=order.kitchen_status;cells[5].replaceChildren(badge);
        let link=cells[6].querySelector('a');if(!link){link=node('a','View Details','snc-menu-button');link.dataset.orderDetails=order.id;link.href='order.php?view=monitor&order_id='+encodeURIComponent(order.id);cells[6].append(link);}
      }
      if(body.children[index]!==row)body.insertBefore(row,body.children[index]||null);
    });
    [...body.children].forEach(row=>{if(!keep.has(row.dataset.operationOrder)){const focused=row.contains(document.activeElement);row.remove();if(focused)refresh.focus();}});
    if(!data.orders.length){const row=node('tr');const cell=node('td','No active orders match these filters.');cell.colSpan=7;row.append(cell);body.append(row);}
    applied.page=data.page;const query=new URLSearchParams(applied);history.replaceState(null,'','?'+query);refresh.href='?'+query;
    pagination.replaceChildren();[[-1,'Previous'],[1,'Next']].forEach(([delta,label])=>{const page=data.page+delta;if(page<1||page>data.pages)return;const link=node('a',label,'snc-menu-button');link.dataset.operationsPage=page;link.href='?'+new URLSearchParams({...applied,page});pagination.append(link);});
    text(error,'');updated.title='Last successful update: '+data.updated_at+' (UTC+08:00)';clock();
  }
  async function load(){
    if(stopped)return;request?.abort();const current=new AbortController();request=current;refresh.setAttribute('aria-busy','true');
    const timeout=setTimeout(()=>current.abort(),10000);
    try{
      const response=await fetch('../api/live_operations.php?'+new URLSearchParams(applied),{cache:'no-store',signal:current.signal});const data=await response.json();if(request!==current)return;
      if(!response.ok||!data.ok){if(response.status===401||response.status===403){stopped=true;text(updated,'Updates stopped · sign in again');}throw new Error(data.message||'Live updates are unavailable.');}
      render(data);
    }catch(e){if(request!==current)return;unavailable=true;text(error,e.name==='AbortError'?'Live updates timed out. Displayed orders may be out of date.':(e.message||'Live updates are unavailable. Displayed orders may be out of date.'));clock();}
    finally{clearTimeout(timeout);if(request===current){request=null;refresh.removeAttribute('aria-busy');}}
  }
  form.addEventListener('submit',event=>{event.preventDefault();applied={status:form.elements.status.value,type:form.elements.type.value,page:1};load();});
  refresh.addEventListener('click',event=>{event.preventDefault();load();});
  document.addEventListener('click',event=>{const reset=event.target.closest('[data-operations-reset]');const link=event.target.closest('[data-operations-page]');if((!reset&&!link)||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;event.preventDefault();if(reset){form.elements.status.value='all';form.elements.type.value='all';applied={status:'all',type:'all',page:1};}else applied.page=Number(link.dataset.operationsPage);load();});
  const dialog=document.getElementById('snc-order-dialog');if(dialog){const button=node('button','Refresh Details','snc-cat-btn');button.type='button';button.addEventListener('click',()=>document.dispatchEvent(new CustomEvent('snc-order-updated')));dialog.querySelector('.snc-order-dialog__actions').prepend(button);}
  setInterval(()=>{if(!document.hidden&&!request)load();},10000);setInterval(()=>{if(!document.hidden)clock();},1000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)load();});clock();
})();
