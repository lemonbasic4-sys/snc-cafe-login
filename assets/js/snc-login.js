/** SNC 2018 Café & Resto | Login V8.5 | Frontend interactions only. */
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

  let logoFlip = null;
  function flipDreamcatcher() {
    if (logoFlip) logoFlip.cancel();
    // Animate the actual image, not the login card or surrounding layout.
    // Keep a brief flip under reduced motion instead of silently skipping it.
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const duration = reducedMotion ? 260 : 1100;
    const keyframes = [
      { transform: 'rotateY(0deg)', offset: 0 },
      { transform: 'rotateY(90deg)', offset: .25 },
      { transform: 'rotateY(180deg)', offset: .5 },
      { transform: 'rotateY(270deg)', offset: .75 },
      { transform: 'rotateY(360deg)', offset: 1 }
    ];

    if (typeof emblem.animate === 'function') {
      logoFlip = emblem.animate(keyframes, { duration, easing: 'ease-in-out', iterations: 1 });
      logoFlip.onfinish = () => { logoFlip = null; };
      logoFlip.oncancel = () => { logoFlip = null; };
      return;
    }

    // Fallback for browsers without the Web Animations API.
    emblem.classList.remove('is-flipping');
    void emblem.offsetWidth;
    emblem.classList.toggle('is-flipping-reduced', reducedMotion);
    emblem.classList.add('is-flipping');
    const finishFallback = () => {
      emblem.classList.remove('is-flipping', 'is-flipping-reduced');
      logoFlip = null;
    };
    emblem.addEventListener('animationend', finishFallback, { once: true });
    logoFlip = {
      cancel: () => {
        emblem.removeEventListener('animationend', finishFallback);
        finishFallback();
      }
    };
  }

  function setPortal(nextRole) {
    if (!['admin', 'staff'].includes(nextRole) || login.dataset.role === nextRole) return;
    applyPortal(nextRole);
    flipDreamcatcher();
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
