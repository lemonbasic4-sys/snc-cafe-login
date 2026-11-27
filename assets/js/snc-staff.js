(() => {
  'use strict';
  const csrf = document.getElementById('snc-staff-csrf');
  if (!csrf) return;
  const dialog = document.getElementById('snc-staff-dialog');
  const form = document.getElementById('snc-staff-form');
  const statusDialog = document.getElementById('snc-staff-status-dialog');
  const statusForm = document.getElementById('snc-staff-status-form');
  function passwordTypes(show) {
    form.elements.password.type = form.elements.password_confirmation.type = show ? 'text' : 'password';
  }
  function openStaff(staff) {
    form.reset();passwordTypes(false);
    form.querySelector('[data-staff-message]').textContent = '';
    form.elements.id.value = staff ? staff.id : '0';
    form.elements.revision.value = staff ? staff.staff_revision : '0';
    form.querySelectorAll('[data-staff-password]').forEach(field => {
      field.hidden = Boolean(staff);field.querySelectorAll('input').forEach(input => {input.disabled = Boolean(staff);});
    });
    if (staff) ['full_name','username','role','is_active'].forEach(name => {form.elements[name].value = staff[name];});
    document.getElementById('snc-staff-title').textContent = staff ? 'Edit Staff' : 'Add Staff';
    document.getElementById('snc-staff-subtitle').textContent = staff ? 'Update account details. Role or status changes end existing access.' : 'Create an account for the Staff Login portal.';
    document.getElementById('snc-save-staff').textContent = staff ? 'Save Changes' : 'Create Account';
    dialog.showModal();
  }
  document.getElementById('snc-add-staff')?.addEventListener('click',()=>openStaff(null));
  document.querySelectorAll('[data-edit-staff]').forEach(button=>button.addEventListener('click',()=>openStaff(JSON.parse(button.dataset.editStaff))));
  document.querySelectorAll('[data-status-staff]').forEach(button=>button.addEventListener('click',()=>{
    const staff=JSON.parse(button.dataset.statusStaff);const activate=Number(staff.is_active)===0;
    statusForm.reset();statusForm.querySelector('[data-staff-message]').textContent='';
    statusForm.elements.id.value=staff.id;statusForm.elements.revision.value=staff.staff_revision;statusForm.elements.is_active.value=activate?'1':'0';
    document.getElementById('snc-staff-status-title').textContent=activate?'Activate Staff Account':'Deactivate Staff Account';
    document.getElementById('snc-staff-status-detail').textContent=activate?`Allow ${staff.full_name} to sign in as ${staff.role==='cashier'?'Cashier':'Kitchen'}?`:`${staff.full_name} will lose access on their next request. Their recorded orders and activity will remain.`;
    document.getElementById('snc-confirm-staff-status').textContent=activate?'Activate Account':'Deactivate Account';statusDialog.showModal();
  }));
  document.getElementById('snc-staff-show-password').addEventListener('change',event=>passwordTypes(event.target.checked));
  document.querySelectorAll('[data-staff-close]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog').close()));
  [dialog,statusDialog].forEach(current=>{
    current.addEventListener('close',()=>{form.elements.password.value='';form.elements.password_confirmation.value='';passwordTypes(false);});
    current.addEventListener('cancel',event=>{if(current.querySelector('form').dataset.saving==='true')event.preventDefault();});
  });
  document.querySelectorAll('[data-staff-save]').forEach(current=>current.addEventListener('submit',async event=>{
    event.preventDefault();if(current.dataset.saving==='true')return;
    const message=current.querySelector('[data-staff-message]');message.textContent='';
    if(current===form&&!form.elements.password.disabled&&form.elements.password.value!==form.elements.password_confirmation.value){message.textContent='The passwords do not match.';return;}
    const data=Object.fromEntries(new FormData(current));current.dataset.saving='true';
    const controls=current.closest('dialog').querySelectorAll('button');controls.forEach(button=>{button.disabled=true;});
    try {
      const response=await fetch('../api/staff.php',{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-Token':csrf.value},body:JSON.stringify(data)});
      const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||'Unable to save staff details.');
      window.location.reload();
    }catch(error){message.textContent=error.message||'Connection interrupted. Refresh the staff list to check whether the change was saved before retrying.';}
    finally{delete current.dataset.saving;controls.forEach(button=>{button.disabled=false;});}
  }));
})();
