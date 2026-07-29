(function () {
  'use strict';

  var searches = Array.prototype.slice.call(document.querySelectorAll('[data-search-everywhere]'));

  searches.forEach(function (search) {
    var form = search.querySelector('[data-search-everywhere-form]');
    var input = search.querySelector('[data-search-everywhere-query]');
    var scope = search.querySelector('[data-search-everywhere-scope]');

    if (!form || !input) {
      return;
    }

    input.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') {
        return;
      }

      if (input.value !== '') {
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
      } else {
        input.blur();
      }
    });

    if (scope) {
      scope.addEventListener('change', function () {
        if (input.value.trim() !== '') {
          form.requestSubmit();
        }
      });
    }

    form.addEventListener('submit', function () {
      search.dispatchEvent(new CustomEvent('search-everywhere:submit', {
        bubbles: true,
        detail: {
          query: input.value.trim(),
          scope: scope ? scope.value : 'all'
        }
      }));
    });
  });

  document.addEventListener('keydown', function (event) {
    var target = event.target;
    var isEditable = target instanceof HTMLElement
      && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName));

    if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey || isEditable || searches.length === 0) {
      return;
    }

    var input = searches[0].querySelector('[data-search-everywhere-query]');
    if (input) {
      event.preventDefault();
      input.focus();
      input.select();
    }
  });
}());
