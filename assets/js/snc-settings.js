(() => {
  'use strict';
  const form=document.getElementById('snc-settings-form');if(!form)return;
  form.querySelectorAll('[data-preview]').forEach(input=>input.addEventListener('input',()=>{document.querySelector('[data-receipt-preview="'+input.dataset.preview+'"]').textContent=input.value;}));
  form.querySelector('[data-settings-width]').addEventListener('change',event=>{document.querySelector('[data-settings-paper]').dataset.width=event.target.value;});
})();
