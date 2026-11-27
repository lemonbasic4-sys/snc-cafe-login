(() => {
  'use strict';
  let closed=[];let printing=false;
  window.addEventListener('beforeprint',()=>{if(printing)return;printing=true;closed=[...document.querySelectorAll('.snc-dashboard-chart-data:not([open])')];closed.forEach(el=>{el.open=true;});});
  window.addEventListener('afterprint',()=>{closed.forEach(el=>{el.open=false;});closed=[];printing=false;});
  document.getElementById('snc-print-report')?.addEventListener('click',()=>window.print());
})();
