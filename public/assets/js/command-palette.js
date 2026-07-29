(function () {
  'use strict';

  var palettes = [];

  function normalized(value) {
    return String(value || '').trim().toLowerCase();
  }

  function createPalette(dialog) {
    var input = dialog.querySelector('[data-command-palette-query]');
    var items = Array.prototype.slice.call(dialog.querySelectorAll('[data-command-palette-item]'));
    var groups = Array.prototype.slice.call(dialog.querySelectorAll('[data-command-palette-group]'));
    var empty = dialog.querySelector('[data-command-palette-empty]');
    var status = dialog.querySelector('[data-command-palette-status]');
    var close = dialog.querySelector('[data-command-palette-close]');
    var visibleItems = items.slice();
    var activeIndex = -1;

    if (!input || !empty || !status) {
      return null;
    }

    function setActive(index) {
      items.forEach(function (item) {
        item.setAttribute('aria-selected', 'false');
        item.classList.remove('bg-app-surface-2');
      });

      if (visibleItems.length === 0) {
        activeIndex = -1;
        return;
      }

      activeIndex = (index + visibleItems.length) % visibleItems.length;
      var item = visibleItems[activeIndex];
      item.setAttribute('aria-selected', 'true');
      item.classList.add('bg-app-surface-2');
      item.scrollIntoView({ block: 'nearest' });
    }

    function filter() {
      var query = normalized(input.value);

      visibleItems = items.filter(function (item) {
        var match = query === '' || normalized(item.dataset.commandPaletteSearch).indexOf(query) !== -1;
        item.hidden = !match;
        return match;
      });

      groups.forEach(function (group) {
        group.hidden = group.querySelectorAll('[data-command-palette-item]:not([hidden])').length === 0;
      });

      empty.hidden = visibleItems.length !== 0;
      status.textContent = visibleItems.length === 1
        ? '1 command available'
        : visibleItems.length + ' commands available';
      setActive(visibleItems.length > 0 ? 0 : -1);
    }

    function openPalette() {
      if (!dialog.open) {
        dialog.showModal();
      }

      filter();
      window.requestAnimationFrame(function () {
        input.focus();
        input.select();
      });
      dialog.dispatchEvent(new CustomEvent('command-palette:open', { bubbles: true }));
    }

    function closePalette() {
      if (dialog.open) {
        dialog.close();
      }
    }

    input.addEventListener('input', filter);
    input.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        setActive(activeIndex + 1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        setActive(activeIndex - 1);
      } else if (event.key === 'Enter' && activeIndex >= 0) {
        event.preventDefault();
        visibleItems[activeIndex].click();
      } else if (event.key === 'Escape') {
        event.preventDefault();
        closePalette();
      }
    });

    items.forEach(function (item) {
      item.addEventListener('mouseenter', function () {
        setActive(visibleItems.indexOf(item));
      });
      item.addEventListener('click', function () {
        dialog.dispatchEvent(new CustomEvent('command-palette:select', {
          bubbles: true,
          detail: {
            label: item.textContent.trim(),
            url: item.getAttribute('href')
          }
        }));
        closePalette();
      });
    });

    if (close) {
      close.addEventListener('click', closePalette);
    }

    dialog.addEventListener('click', function (event) {
      if (event.target === dialog) {
        closePalette();
      }
    });

    dialog.addEventListener('close', function () {
      input.value = '';
      filter();
    });

    filter();

    return {
      id: dialog.id,
      open: openPalette
    };
  }

  document.querySelectorAll('[data-command-palette]').forEach(function (dialog) {
    var palette = createPalette(dialog);
    if (palette) {
      palettes.push(palette);
    }
  });

  document.querySelectorAll('[data-command-palette-open]').forEach(function (trigger) {
    trigger.addEventListener('click', function () {
      var id = trigger.dataset.commandPaletteOpen;
      var palette = palettes.find(function (candidate) {
        return candidate.id === id;
      });
      if (palette) {
        palette.open();
      }
    });
  });

  document.addEventListener('keydown', function (event) {
    if ((event.metaKey || event.ctrlKey) && normalized(event.key) === 'k' && palettes.length > 0) {
      event.preventDefault();
      palettes[0].open();
    }
  });
}());
