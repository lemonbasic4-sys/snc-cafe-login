/** Owner-only confirmation shared by modal and standalone order details. */
(() => {
  'use strict';
  function node(tag,text,className){const result=document.createElement(tag);if(text!==undefined)result.textContent=String(text);if(className)result.className=className;return result;}
  const key=()=>Array.from(crypto.getRandomValues(new Uint8Array(16)),byte=>byte.toString(16).padStart(2,'0')).join('');
  window.SncVoidOrder={create(order,csrf){
    const panel=node('section',undefined,'snc-void-panel');const trigger=node('button','Void Order','snc-cat-btn snc-void-trigger');trigger.type='button';
    const form=node('form',undefined,'snc-void-form');form.dataset.voidOrder='';form.hidden=true;
    [['order_id',order.id],['kitchen_status',order.kitchen_status],['request_key',key()],['csrf_token',csrf]].forEach(([name,value])=>{const input=node('input');input.type='hidden';input.name=name;input.value=value;form.append(input);});
    form.append(node('h3','Void '+order.order_number+'?'),node('p','This removes the order from paid sales and the active kitchen queue. Payment is not refunded and deducted ingredients are not returned to stock. This action cannot be undone here.','snc-void-help'));
    const label=node('label','Reason');const input=node('input');input.name='reason';input.className='snc-input';input.required=true;input.minLength=3;input.maxLength=240;input.autocomplete='off';label.append(input);form.append(label);
    const message=node('p',undefined,'snc-void-message');message.dataset.voidMessage='';message.setAttribute('role','status');form.append(message);
    const actions=node('div',undefined,'snc-void-actions');const cancel=node('button','Cancel','snc-cat-btn');cancel.type='button';const confirm=node('button','Confirm Void','snc-cat-btn snc-void-trigger');confirm.type='submit';actions.append(cancel,confirm);form.append(actions);panel.append(trigger,form);
    trigger.addEventListener('click',()=>{trigger.hidden=true;form.hidden=false;input.focus();});cancel.addEventListener('click',()=>{if(form.dataset.saving)return;form.hidden=true;trigger.hidden=false;trigger.focus();});return panel;
  }};
  document.addEventListener('submit',async event=>{
    const form=event.target;
    if(form.getAttribute('method')==='dialog'&&form.closest('dialog')?.querySelector('[data-void-order][data-saving="true"]')){event.preventDefault();return;}
    if(!form.matches('[data-void-order]'))return;event.preventDefault();if(form.dataset.saving)return;
    const data=Object.fromEntries(new FormData(form));const message=form.querySelector('[data-void-message]');message.textContent='Voiding order…';form.dataset.saving='true';
    const controls=form.querySelectorAll('button,input');controls.forEach(control=>{control.disabled=true;});
    try{
      const response=await fetch('../api/void_order.php',{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-Token':data.csrf_token},body:JSON.stringify(data),signal:AbortSignal.timeout(15000)});
      const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||'Unable to confirm the void.');window.location.reload();
    }catch(error){message.textContent=error.name==='TimeoutError'||error.name==='AbortError'?'The result is unknown. Keep the same reason and retry, or refresh the order details to check whether it was voided.':error.message||'Connection interrupted. Retry with the same reason or refresh to check the result.';}
    finally{delete form.dataset.saving;controls.forEach(control=>{control.disabled=false;});}
  });
  document.addEventListener('cancel',event=>{if(event.target.querySelector('[data-void-order][data-saving="true"]'))event.preventDefault();},true);
})();
