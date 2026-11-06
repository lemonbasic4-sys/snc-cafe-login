/** SNC 2018 Café & Resto | Login V8.4 | Frontend interactions only. */
(() => {
  'use strict';
  const login = document.querySelector('.snc-login');
  const form = document.getElementById('snc-login-form');
  const emblem = document.querySelector('.snc-emblem');
  const roleButtons = document.querySelectorAll('[data-snc-role]');
  const portalLabel = document.getElementById('snc-portal-label');
  const portalValue = document.getElementById('snc-portal-value');
  const subtitle = document.getElementById('snc-form-subtitle');
  const password = document.getElementById('snc-password');
  const toggle = document.getElementById('snc-password-toggle');
  const status = document.getElementById('snc-form-status');
  if (!login || !form || !emblem || !portalLabel || !portalValue || !subtitle || !password || !toggle || !status) return;

  function applyPortal(nextRole) {
    if (!['admin', 'staff'].includes(nextRole) || login.dataset.role === nextRole) return;
    login.dataset.role = nextRole;
    portalValue.value = nextRole;
    portalLabel.textContent = nextRole === 'admin' ? 'ADMIN PORTAL' : 'STAFF PORTAL';
    subtitle.textContent = nextRole === 'admin' ? 'Sign in to your management account.' : 'Sign in to your staff account.';
    roleButtons.forEach(button => {
      const active = button.dataset.sncRole === nextRole;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', String(active));
    });

    form.reset();
    portalValue.value = nextRole;
    password.type = 'password';
    toggle.textContent = 'Show';
    toggle.setAttribute('aria-label', 'Show password');
    toggle.setAttribute('aria-pressed', 'false');
    status.textContent = '';
  }

  function setPortal(nextRole) {
    if (!['admin', 'staff'].includes(nextRole) || login.dataset.role === nextRole) return;
    applyPortal(nextRole);
    emblem.classList.remove('is-flipping');
    void emblem.offsetWidth;
    emblem.classList.add('is-flipping');
  }

  login.dataset.role = 'admin';
  roleButtons.forEach(button => button.addEventListener('click', () => setPortal(button.dataset.sncRole)));
  toggle.addEventListener('click', () => {
    const isVisible = password.type === 'password';
    password.type = isVisible ? 'text' : 'password';
    toggle.textContent = isVisible ? 'Hide' : 'Show';
    toggle.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
    toggle.setAttribute('aria-pressed', String(isVisible));
  });
  form.addEventListener('submit', event => {
    event.preventDefault();
    status.textContent = 'Sign-in is unavailable until secure authentication is configured.';
  });
})();
