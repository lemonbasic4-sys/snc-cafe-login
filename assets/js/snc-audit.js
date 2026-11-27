(() => {
  'use strict';
  const dialog=document.getElementById('snc-audit-dialog');if(!dialog)return;
  const body=document.getElementById('snc-audit-detail-body');let request=0;let controller;
  const node=(tag,text,className)=>{const element=document.createElement(tag);if(text!==undefined)element.textContent=String(text);if(className)element.className=className;return element;};
  function show(record) {
    body.replaceChildren(node('h3',record.activity));
    const facts=node('dl',undefined,'snc-audit-facts');
    [['Record',record.source.toUpperCase()+'-'+record.event_id],['When',record.occurred_at+' · UTC+08:00'],['User / attempted account',record.actor],['Affected record',record.target_name]].forEach(([label,value])=>{const group=node('div');group.append(node('dt',label),node('dd',value));facts.append(group);});body.append(facts);
    const wrap=node('div',undefined,'snc-table-wrap');const table=node('table',undefined,'snc-table snc-audit-changes');const head=node('thead');const header=node('tr');['Field','Before','After / recorded details'].forEach(label=>header.append(node('th',label)));head.append(header);const rows=node('tbody');
    record.changes.forEach(change=>{const row=node('tr');row.append(node('td',change.field),node('td',change.before),node('td',change.after));rows.append(row);});table.append(head,rows);wrap.append(table);body.append(wrap);
    if(record.order_id){const link=node('a','View Order','snc-menu-button');link.href='order.php?order_id='+encodeURIComponent(record.order_id);body.append(link);}
  }
  document.querySelectorAll('[data-audit-details]').forEach(link=>link.addEventListener('click',async event=>{
    if(event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
    event.preventDefault();const token=++request;controller?.abort();controller=new AbortController();body.replaceChildren(node('p','Loading audit details…'));dialog.showModal();
    try {
      const response=await fetch('../api/audit_details.php?source='+encodeURIComponent(link.dataset.source)+'&event_id='+encodeURIComponent(link.dataset.eventId),{headers:{Accept:'application/json'},cache:'no-store',signal:controller.signal});
      const result=await response.json();if(token!==request)return;if(!response.ok||!result.ok)throw new Error(result.message||'Unable to load audit details.');show(result.record);
    }catch(error){if(token!==request)return;const fallback=node('a','Open record page','snc-menu-button');fallback.href=link.href;body.replaceChildren(node('p',error.message||'Audit details are unavailable.','snc-menu-error'),fallback);}
  }));
  function close(){request++;controller?.abort();dialog.close();}
  document.getElementById('snc-audit-close').addEventListener('click',close);
  dialog.addEventListener('cancel',()=>{request++;controller?.abort();});
})();
