/* Cashier void requests preserve the current cart and require owner review. */
(() => {
  'use strict';
  const node=(tag,text,className)=>{const el=document.createElement(tag);if(text!==undefined)el.textContent=String(text);if(className)el.className=className;return el;};
  window.SncVoidRequest={
    create(data){
      const panel=node('section',undefined,'snc-void-panel snc-request-status');const request=data.void_request;
      if(!request)return panel;
      const title=node('div',undefined,'snc-request-status__head');
      title.append(node('h3','Void Request '+request.status[0].toUpperCase()+request.status.slice(1)));
      const refresh=node('button','Refresh Status','snc-cat-btn');refresh.type='button';refresh.addEventListener('click',()=>document.dispatchEvent(new CustomEvent('snc-order-updated')));title.append(refresh);panel.append(title);
      panel.append(node('p','Reason: '+request.reason));
      if(request.reviewed_at)panel.append(node('p',request.decision_reason),node('p',request.reviewer_name+' · '+request.reviewed_at,'snc-record-muted'));
      else panel.append(node('p','Awaiting owner approval. The order remains active.','snc-record-muted'));
      return panel;
    },
    createForm(data){
      const form=node('form',undefined,'snc-request-form');form.dataset.voidRequest='';form.id='snc-order-request-form';
      const key=Array.from(crypto.getRandomValues(new Uint8Array(16)),x=>x.toString(16).padStart(2,'0')).join('');
      Object.entries({action:'request',order_id:data.order.id,request_key:key,csrf_token:data.request_csrf}).forEach(([name,value])=>{const field=node('input');field.type='hidden';field.name=name;field.value=value;form.append(field);});
      const order=node('div',undefined,'snc-request-order');order.append(node('h3',data.order.order_number),node('strong','₱'+Number(data.order.total_amount).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})));form.append(order);
      form.append(node('p','Requires owner approval. The order remains active until approved.','snc-void-help'));
      const label=node('label','Reason');const input=node('textarea');input.className='snc-input';input.name='reason';input.required=true;input.minLength=3;input.maxLength=240;input.rows=3;input.placeholder='Explain why this order should be voided';label.append(input);form.append(label);
      const message=node('p',undefined,'snc-void-message');message.dataset.requestMessage='';message.setAttribute('role','status');form.append(message);
      return form;
    }
  };
  document.addEventListener('submit',async event=>{
    const form=event.target;if(!form.matches('[data-void-request]'))return;event.preventDefault();if(form.dataset.saving)return;
    const inDialog=Boolean(form.closest('dialog'));const data=Object.fromEntries(new FormData(form));const message=form.querySelector('[data-request-message]');form.dataset.saving='true';message.textContent='Sending request…';
    const controls=[...form.querySelectorAll('button,input,textarea'),...(form.closest('dialog')?.querySelectorAll('[data-request-control]')||[])];controls.forEach(el=>{el.disabled=true;});
    try{
      const response=await fetch('../api/void_request.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':data.csrf_token},body:JSON.stringify(data),signal:AbortSignal.timeout(15000)});const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||'Unable to send the request.');
      message.textContent=result.message;document.dispatchEvent(new CustomEvent('snc-order-updated'));
      if(!inDialog)window.location.reload();
    }catch(error){message.textContent=error.name==='TimeoutError'||error.name==='AbortError'?'The result is unknown. Retry the same request or reopen the details to check its status.':error.message||'Unable to send the request. Retry or reopen the details.';}
    finally{delete form.dataset.saving;controls.forEach(el=>{el.disabled=false;});}
  });
  document.addEventListener('cancel',event=>{if(event.target.querySelector('[data-void-request][data-saving]'))event.preventDefault();},true);
  document.addEventListener('submit',event=>{if(event.target.getAttribute('method')==='dialog'&&event.target.closest('dialog')?.querySelector('[data-void-request][data-saving]'))event.preventDefault();});
  document.addEventListener('submit',event=>{if(event.target.matches('[data-approval-decision]')){if(event.target.dataset.saving){event.preventDefault();return;}event.target.dataset.saving='true';event.target.querySelectorAll('button').forEach(el=>{el.disabled=true;});}});
})();
