/* Enhance server-rendered approval reviews; normal links/forms remain the fallback. */
(() => {
  'use strict';
  let dialog;
  let trigger;
  let loading;
  function step(value){
    const review=dialog.querySelector('#snc-approval-review');
    review.querySelectorAll('[data-approval-step]').forEach(el=>{el.hidden=el.dataset.approvalStep!==value;});
    review.querySelectorAll('[data-approval-show]').forEach(el=>{el.hidden=value!=='details';});
    review.querySelectorAll('[data-approval-confirm]').forEach(el=>{el.hidden=el.dataset.approvalConfirm!==value;});
    const back=review.querySelector('[data-approval-back]');if(back)back.hidden=value==='details';
    review.querySelector('#snc-approval-title').textContent=value==='approved'?'Approve Void':value==='rejected'?'Reject Request':'Review Void Request';
    const focus=value==='rejected'?review.querySelector('textarea'):value==='details'?review.querySelector('[data-approval-close]'):review.querySelector('[data-approval-back]');
    focus?.focus();review.querySelector('.snc-approval-body').scrollTop=0;
  }
  function open(review,link){
    if(typeof HTMLDialogElement==='undefined')return false;
    dialog?.remove();trigger=link;
    dialog=document.createElement('dialog');dialog.className='snc-approval-dialog';dialog.setAttribute('aria-labelledby','snc-approval-title');dialog.append(review);document.body.append(dialog);
    review.classList.add('is-modal');const footer=review.querySelector('.snc-approval-footer');if(footer)footer.hidden=false;
    dialog.addEventListener('click',event=>{
      if(event.target.closest('[data-approval-close]')){event.preventDefault();if(!dialog.querySelector('[data-approval-decision][data-saving]'))dialog.close();}
      const next=event.target.closest('[data-approval-show]');if(next)step(next.dataset.approvalShow);
      if(event.target.closest('[data-approval-back]'))step('details');
    });
    dialog.addEventListener('close',()=>{const url=new URL(location.href);url.searchParams.delete('request_id');history.replaceState(null,'',url);trigger?.focus();dialog.remove();});
    dialog.addEventListener('cancel',event=>{if(dialog.querySelector('[data-approval-decision][data-saving]'))event.preventDefault();});
    dialog.showModal();step(review.dataset.reviewStep||'details');
    const url=new URL(review.dataset.reviewUrl,location.href);history.replaceState(null,'',url);return true;
  }
  document.addEventListener('click',async event=>{
    const link=event.target.closest('a[data-approval-review]');if(!link||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||typeof HTMLDialogElement==='undefined')return;
    event.preventDefault();loading?.abort();loading=new AbortController();const request=loading;link.setAttribute('aria-busy','true');
    try{
      const response=await fetch(link.href,{cache:'no-store',signal:request.signal});const html=await response.text();if(loading!==request)return;
      const review=new DOMParser().parseFromString(html,'text/html').querySelector('#snc-approval-review');
      if(!response.ok||!review)throw new Error('Review unavailable');open(document.importNode(review,true),link);
    }catch(error){if(error.name!=='AbortError')location.assign(link.href);}
    finally{link.removeAttribute('aria-busy');}
  });
  document.addEventListener('submit',event=>{
    const form=event.target;if(!form.matches('[data-approval-decision]')||!form.closest('.snc-approval-dialog'))return;
    const review=form.closest('#snc-approval-review');review.querySelectorAll('button,[data-approval-close]').forEach(el=>{if(el.tagName==='BUTTON')el.disabled=true;else el.setAttribute('aria-disabled','true');});
  });
  const initial=document.getElementById('snc-approval-review');if(initial)open(initial,document.querySelector(`a[data-approval-review][href="${CSS.escape(initial.dataset.reviewUrl)}"]`));
})();
