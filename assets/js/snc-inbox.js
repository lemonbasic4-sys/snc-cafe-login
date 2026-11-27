/* Lightweight per-user unread counts; notification history stays server-side. */
(() => {
  'use strict';
  const links=document.querySelectorAll('[data-notification-link]');if(!links.length)return;
  const endpoint=new URL('../../api/notifications.php',document.currentScript.src);let running=false;let stopped=false;
  async function refresh(){
    if(running||stopped||document.hidden)return;running=true;
    try{
      const response=await fetch(endpoint,{cache:'no-store',headers:{Accept:'application/json'},signal:AbortSignal.timeout(10000)});
      if(response.status===401||response.status===403){stopped=true;return;}
      const data=await response.json();if(!response.ok||!data.ok)throw new Error('Unavailable');
      links.forEach(link=>{link.setAttribute('aria-label','Notifications, '+data.unread+' unread');link.removeAttribute('title');const badge=link.querySelector('[data-notification-count]');badge.hidden=data.unread===0;badge.textContent=data.unread>99?'99+':String(data.unread);});
      try{const key='snc-inbox-latest:'+data.viewer;const old=sessionStorage.getItem(key);if(old!==null&&data.latest>Number(old)&&data.unread>0)window.SncToast?.info('You have new notifications. Open Notifications to view them.','New Notification');sessionStorage.setItem(key,String(data.latest));}catch{}
    }catch{
      links.forEach(link=>{link.title='Notification count temporarily unavailable';link.setAttribute('aria-label','Notifications, count unavailable');});
    }finally{running=false;}
  }
  refresh();window.setInterval(refresh,30000);document.addEventListener('visibilitychange',refresh);window.addEventListener('focus',refresh);document.addEventListener('snc-order-updated',refresh);
})();
