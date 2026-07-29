(function () {
  'use strict';

  var toneClasses = {
    success: {
      border: 'border-app-success/30',
      icon: ['bg-app-success/10', 'text-app-success'],
      symbol: 'badge-check'
    },
    warning: {
      border: 'border-app-warning/30',
      icon: ['bg-app-warning/10', 'text-app-warning'],
      symbol: 'circle-alert'
    },
    error: {
      border: 'border-app-danger/30',
      icon: ['bg-app-danger/10', 'text-app-danger'],
      symbol: 'circle-x'
    },
    info: {
      border: 'border-app-border',
      icon: ['bg-app-accent/10', 'text-app-accent'],
      symbol: 'badge-info'
    }
  };
  var allBorderClasses = Object.keys(toneClasses).map(function (tone) {
    return toneClasses[tone].border;
  });
  var allIconClasses = Object.keys(toneClasses).reduce(function (classes, tone) {
    return classes.concat(toneClasses[tone].icon);
  }, []);

  function normalizedTone(value) {
    return Object.prototype.hasOwnProperty.call(toneClasses, value) ? value : 'info';
  }

  function dismiss(toast) {
    if (!toast || toast.dataset.toastClosing === 'true') {
      return;
    }

    toast.dataset.toastClosing = 'true';
    toast.classList.add('translate-y-1', 'opacity-0');
    window.setTimeout(function () {
      toast.remove();
    }, 150);
  }

  function startTimer(toast) {
    var duration = Math.max(0, Number(toast.dataset.toastDuration || 0));
    var timer = null;

    function stop() {
      if (timer !== null) {
        window.clearTimeout(timer);
        timer = null;
      }
    }

    function start() {
      stop();
      if (duration > 0) {
        timer = window.setTimeout(function () {
          dismiss(toast);
        }, duration);
      }
    }

    toast.addEventListener('mouseenter', stop);
    toast.addEventListener('mouseleave', start);
    toast.addEventListener('focusin', stop);
    toast.addEventListener('focusout', start);
    start();
  }

  function initialize(toast) {
    var close = toast.querySelector('[data-toast-dismiss]');
    if (close) {
      close.addEventListener('click', function () {
        dismiss(toast);
      });
    }

    startTimer(toast);
  }

  function applyTone(toast, tone) {
    var config = toneClasses[tone];
    var icon = toast.querySelector('[data-toast-icon]');
    var use = icon ? icon.querySelector('use') : null;

    toast.dataset.toastTone = tone;
    toast.setAttribute('role', tone === 'error' ? 'alert' : 'status');
    toast.classList.remove.apply(toast.classList, allBorderClasses);
    toast.classList.add(config.border);

    if (icon) {
      icon.classList.remove.apply(icon.classList, allIconClasses);
      config.icon.forEach(function (className) {
        icon.classList.add(className);
      });
    }

    if (use) {
      use.setAttribute('href', use.getAttribute('href').replace(/#[^#]+$/, '#' + config.symbol));
    }
  }

  function show(detail) {
    var host = document.querySelector('[data-toast-host]');
    var template = host ? host.querySelector('[data-toast-template]') : null;
    if (!host || !template || !detail || !detail.message) {
      return;
    }

    var toast = template.content.firstElementChild.cloneNode(true);
    var title = toast.querySelector('[data-toast-title]');
    var message = toast.querySelector('[data-toast-message]');
    var tone = normalizedTone(detail.tone || detail.type);

    title.textContent = detail.title || '';
    title.hidden = !detail.title;
    title.classList.toggle('hidden', !detail.title);
    message.textContent = detail.message;
    message.classList.toggle('mt-0.5', Boolean(detail.title));
    toast.dataset.toastDuration = String(detail.duration === undefined ? (tone === 'error' ? 0 : 6000) : detail.duration);
    applyTone(toast, tone);

    host.insertBefore(toast, template);
    initialize(toast);

    while (host.querySelectorAll('[data-toast]').length > 5) {
      dismiss(host.querySelector('[data-toast]'));
    }
  }

  document.querySelectorAll('[data-toast]').forEach(initialize);
  document.addEventListener('toast:show', function (event) {
    show(event.detail || {});
  });
}());
