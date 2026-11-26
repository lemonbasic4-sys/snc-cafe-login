/** SNC 2018 Café & Resto | Approved Collapsible Sidebar Navigation */
(() => {
  'use strict';

  const shell = document.getElementById('snc-shell');
  const toggleBtn = document.getElementById('snc-sidebar-toggle');
  const backdrop = document.getElementById('snc-sidebar-backdrop');
  const sidebar = document.getElementById('snc-sidebar');
  if (!shell || !toggleBtn || !backdrop || !sidebar) return;

  const STORAGE_KEY = 'snc_owner_sidebar_open';
  const mobileMedia = window.matchMedia('(max-width: 960px)');

  function isMobile() {
    return mobileMedia.matches;
  }

  function syncAria() {
    const expanded = isMobile()
      ? shell.classList.contains('is-sidebar-mobile-open')
      : !shell.classList.contains('is-sidebar-closed');
    toggleBtn.setAttribute('aria-expanded', String(expanded));
  }

  function applyDesktopSavedState() {
    if (isMobile()) {
      shell.classList.remove('is-sidebar-closed', 'is-sidebar-mobile-open');
    } else {
      shell.classList.remove('is-sidebar-mobile-open');
      const saved = window.localStorage.getItem(STORAGE_KEY);
      shell.classList.toggle('is-sidebar-closed', saved === '0');
    }
    syncAria();
  }

  function toggleSidebar() {
    if (isMobile()) {
      shell.classList.toggle('is-sidebar-mobile-open');
    } else {
      const willClose = !shell.classList.contains('is-sidebar-closed');
      shell.classList.toggle('is-sidebar-closed', willClose);
      window.localStorage.setItem(STORAGE_KEY, willClose ? '0' : '1');
    }
    syncAria();
  }

  function closeMobileSidebar() {
    if (!isMobile() || !shell.classList.contains('is-sidebar-mobile-open')) return;
    shell.classList.remove('is-sidebar-mobile-open');
    syncAria();
    toggleBtn.focus();
  }

  toggleBtn.addEventListener('click', toggleSidebar);
  backdrop.addEventListener('click', closeMobileSidebar);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMobileSidebar();
    }
  });

  if (typeof mobileMedia.addEventListener === 'function') {
    mobileMedia.addEventListener('change', applyDesktopSavedState);
  } else {
    mobileMedia.addListener(applyDesktopSavedState);
  }

  sidebar.querySelectorAll('.snc-nav-link.is-planned').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      const label = link.dataset.sncModule || 'This module';
      if (window.SncToast) {
        window.SncToast.info(`${label} is planned and not yet connected.`, 'Planned Module');
      }
    });
  });

  applyDesktopSavedState();
})();
