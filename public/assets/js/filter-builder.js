(function () {
  'use strict';

  document.querySelectorAll('[data-filter-builder]').forEach(function (builder) {
    var form = builder.querySelector('[data-filter-builder-form]');
    var close = builder.querySelector('[data-filter-builder-close]');

    if (!form) {
      return;
    }

    builder.querySelectorAll('[data-filter-builder-clear]').forEach(function (button) {
      button.addEventListener('click', function () {
        var field = button.closest('[data-filter-builder-field]');
        if (!field) {
          return;
        }

        field.querySelectorAll('input, select').forEach(function (control) {
          if (control instanceof HTMLSelectElement) {
            control.selectedIndex = 0;
          } else {
            control.value = '';
          }
          control.dispatchEvent(new Event('change', { bubbles: true }));
        });

        button.hidden = true;
        builder.dispatchEvent(new CustomEvent('filter-builder:change', {
          bubbles: true,
          detail: { field: field.dataset.filterBuilderField }
        }));
      });
    });

    if (close) {
      close.addEventListener('click', function () {
        builder.open = false;
      });
    }

    builder.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        builder.open = false;
        var trigger = builder.querySelector('[data-filter-builder-trigger]');
        if (trigger) {
          trigger.focus();
        }
      }
    });

    form.addEventListener('submit', function () {
      builder.dispatchEvent(new CustomEvent('filter-builder:apply', {
        bubbles: true,
        detail: { form: form }
      }));
    });
  });
}());
