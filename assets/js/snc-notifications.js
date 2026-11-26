/** SNC 2018 Café & Resto | System-Wide Notification Toasts */
(() => {
  'use strict';

  const DEFAULT_DURATIONS = {
    success: 3800,
    info: 4000,
    warning: 4800,
    error: 5500
  };

  const DEFAULT_TITLES = {
    success: 'Success',
    info: 'Notice',
    warning: 'Warning',
    error: 'Error'
  };

  const ICONS = {
    success: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.4 2.4 4.8-4.8"/></svg>',
    error: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg>',
    warning: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2.5 19.5h19L12 3z"/><path d="M12 10v4M12 17.5h.01"/></svg>',
    info: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8.5h.01"/></svg>'
  };

  const CLOSE_ICON = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>';

  const scriptSrc = document.currentScript ? document.currentScript.src : '';
  const NOTICE_SOUND_URL = scriptSrc
    ? new URL('../sounds/notice_notif.wav', scriptSrc).href
    : 'assets/sounds/notice_notif.wav';
  const SUCCESS_SOUND_URL = scriptSrc
    ? new URL('../sounds/new_notif.wav', scriptSrc).href
    : 'assets/sounds/new_notif.wav';

  let region = null;
  let toastSeq = 0;
  const audioCache = {};
  let alertAudioCtx = null;

  function playAlertTone(type) {
    try {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      if (!alertAudioCtx) alertAudioCtx = new Ctx();
      if (alertAudioCtx.state === 'suspended') alertAudioCtx.resume();

      const now = alertAudioCtx.currentTime;
      const osc = alertAudioCtx.createOscillator();
      const gain = alertAudioCtx.createGain();

      osc.type = 'triangle';
      if (type === 'error') {
        osc.frequency.setValueAtTime(360, now);
        osc.frequency.setValueAtTime(250, now + 0.09);
      } else {
        osc.frequency.setValueAtTime(520, now);
        osc.frequency.setValueAtTime(390, now + 0.09);
      }

      gain.gain.setValueAtTime(0.32, now);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);

      osc.connect(gain);
      gain.connect(alertAudioCtx.destination);
      osc.start(now);
      osc.stop(now + 0.26);
    } catch (e) {
      // Ignore audio errors.
    }
  }

  function playSound(type) {
    if (type === 'warning' || type === 'error') {
      playAlertTone(type);
      return;
    }

    const url = type === 'success' ? SUCCESS_SOUND_URL : NOTICE_SOUND_URL;
    try {
      if (!audioCache[url]) {
        const audio = new Audio(url);
        audio.preload = 'auto';
        audioCache[url] = audio;
      }
      const clip = audioCache[url];
      clip.currentTime = 0;
      const playPromise = clip.play();
      if (playPromise && typeof playPromise.catch === 'function') {
        playPromise.catch(() => {
          // Ignore browser autoplay block when no prior user gesture exists.
        });
      }
    } catch (e) {
      // Ignore audio playback errors.
    }
  }

  function ensureRegion() {
    if (region && document.body.contains(region)) return region;
    region = document.getElementById('snc-toast-region');
    if (!region) {
      region = document.createElement('div');
      region.id = 'snc-toast-region';
      region.className = 'snc-toast-region';
      region.setAttribute('aria-live', 'polite');
      region.setAttribute('aria-relevant', 'additions removals');
      document.body.appendChild(region);
    }
    return region;
  }

  function dismiss(toastEl) {
    if (!toastEl || toastEl.dataset.leaving === '1') return;
    toastEl.dataset.leaving = '1';
    toastEl.classList.remove('is-visible');
    toastEl.classList.add('is-leaving');

    const removeEl = () => {
      if (toastEl.parentNode) toastEl.parentNode.removeChild(toastEl);
    };

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion) {
      window.setTimeout(removeEl, 160);
      return;
    }

    toastEl.addEventListener('transitionend', removeEl, { once: true });
    window.setTimeout(removeEl, 420);
  }

  function trimOverflow(container) {
    const isMobile = window.matchMedia('(max-width: 650px)').matches;
    const maxVisible = isMobile ? 2 : 4;
    const active = Array.from(container.querySelectorAll('.snc-toast:not([data-leaving="1"])'));
    while (active.length > maxVisible) {
      const oldest = active.shift();
      dismiss(oldest);
    }
  }

  function show(options) {
    const opts = typeof options === 'string' ? { message: options } : (options || {});
    const type = ['success', 'error', 'warning', 'info'].includes(opts.type) ? opts.type : 'info';
    const message = String(opts.message || '').trim();
    if (!message) return null;

    const title = opts.title !== undefined ? String(opts.title).trim() : DEFAULT_TITLES[type];
    const duration = typeof opts.duration === 'number' ? opts.duration : DEFAULT_DURATIONS[type];

    const container = ensureRegion();
    const toast = document.createElement('div');
    toast.className = `snc-toast snc-toast--${type}`;
    toast.id = `snc-toast-${++toastSeq}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

    const iconWrap = document.createElement('span');
    iconWrap.className = 'snc-toast__icon';
    iconWrap.innerHTML = ICONS[type];

    const body = document.createElement('div');
    body.className = 'snc-toast__body';

    if (title) {
      const titleEl = document.createElement('p');
      titleEl.className = 'snc-toast__title';
      titleEl.textContent = title;
      body.appendChild(titleEl);
    }

    const msgEl = document.createElement('p');
    msgEl.className = 'snc-toast__message';
    msgEl.textContent = message;
    body.appendChild(msgEl);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'snc-toast__close';
    closeBtn.setAttribute('aria-label', 'Dismiss notification');
    closeBtn.innerHTML = CLOSE_ICON;
    closeBtn.addEventListener('click', () => dismiss(toast));

    toast.appendChild(iconWrap);
    toast.appendChild(body);
    toast.appendChild(closeBtn);
    container.appendChild(toast);

    trimOverflow(container);

    if (opts.sound !== false) {
      playSound(type);
    }

    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        toast.classList.add('is-visible');
      });
    });

    let timerId = null;
    let remaining = duration;
    let startedAt = Date.now();

    const startTimer = () => {
      if (remaining <= 0) return;
      startedAt = Date.now();
      timerId = window.setTimeout(() => dismiss(toast), remaining);
    };

    const pauseTimer = () => {
      if (timerId !== null) {
        window.clearTimeout(timerId);
        timerId = null;
        remaining = Math.max(0, remaining - (Date.now() - startedAt));
      }
    };

    if (duration > 0) {
      startTimer();
      toast.addEventListener('mouseenter', pauseTimer);
      toast.addEventListener('mouseleave', startTimer);
      toast.addEventListener('focusin', pauseTimer);
      toast.addEventListener('focusout', startTimer);
    }

    return toast;
  }

  function makeHelper(type) {
    return (message, titleOrOpts) => {
      const extra = typeof titleOrOpts === 'string' ? { title: titleOrOpts } : (titleOrOpts || {});
      return show({ ...extra, type, message });
    };
  }

  function clear() {
    if (!region) return;
    region.querySelectorAll('.snc-toast').forEach(dismiss);
  }

  window.SncToast = {
    show,
    success: makeHelper('success'),
    error: makeHelper('error'),
    warning: makeHelper('warning'),
    info: makeHelper('info'),
    dismiss,
    clear
  };

  document.addEventListener('DOMContentLoaded', () => {
    const rawFlash = document.body ? document.body.getAttribute('data-snc-flash') : '';
    if (!rawFlash) return;
    try {
      const flash = JSON.parse(rawFlash);
      if (flash && flash.message) {
        show(flash);
      }
    } catch (e) {
      // Ignore malformed flash payload.
    }
  });
})();
