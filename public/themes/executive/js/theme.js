(function () {
  var shell = document.querySelector('[data-theme-shell]');
  var sidebar = document.querySelector('[data-theme-sidebar]');
  var sidebarToggle = document.querySelectorAll('[data-theme-sidebar-toggle]');
  var sidebarClose = document.querySelectorAll('[data-theme-sidebar-close]');
  var dropdowns = document.querySelectorAll('[data-theme-dropdown]');
  var search = document.querySelector('[data-theme-search]');

  function setSidebar(open) {
    if (!shell || !sidebar) {
      return;
    }

    shell.dataset.themeSidebarOpen = open ? 'true' : 'false';
    sidebar.classList.toggle('translate-x-0', open);
    sidebar.classList.toggle('-translate-x-full', !open);
  }

  sidebarToggle.forEach(function (button) {
    button.addEventListener('click', function () {
      var isOpen = shell && shell.dataset.themeSidebarOpen === 'true';
      setSidebar(!isOpen);
    });
  });

  sidebarClose.forEach(function (button) {
    button.addEventListener('click', function () {
      setSidebar(false);
    });
  });

  dropdowns.forEach(function (dropdown) {
    var toggle = dropdown.querySelector('[data-theme-dropdown-toggle]');
    var menu = dropdown.querySelector('[data-theme-dropdown-menu]');

    if (!toggle || !menu) {
      return;
    }

    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      menu.hidden = expanded;
    });
  });

  if (search) {
    var input = search.querySelector('[data-theme-search-input]');
    var panel = search.querySelector('[data-theme-search-panel]');
    var items = search.querySelectorAll('[data-theme-search-item]');
    var empty = search.querySelector('[data-theme-search-empty]');

    if (input && panel) {
      input.addEventListener('focus', function () {
        panel.hidden = false;
      });

      input.addEventListener('input', function () {
        var query = input.value.trim().toLowerCase();
        var visibleCount = 0;

        items.forEach(function (item) {
          var label = (item.getAttribute('data-search-label') || '').toLowerCase();
          var section = (item.getAttribute('data-search-section') || '').toLowerCase();
          var visible = query === '' || label.indexOf(query) !== -1 || section.indexOf(query) !== -1;

          item.hidden = !visible;

          if (visible) {
            visibleCount += 1;
          }
        });

        if (empty) {
          empty.hidden = visibleCount !== 0;
        }

        panel.hidden = false;
      });

      document.addEventListener('click', function (event) {
        if (!search.contains(event.target)) {
          panel.hidden = true;
        }
      });
    }
  }
})();
