(function () {
  var shell = document.querySelector('[data-theme-shell]');
  var sidebar = document.querySelector('[data-theme-sidebar]');
  var sidebarToggles = document.querySelectorAll('[data-theme-sidebar-toggle]');
  var sidebarClosers = document.querySelectorAll('[data-theme-sidebar-close]');
  var dropdowns = document.querySelectorAll('[data-theme-dropdown]');

  function setSidebar(open) {
    if (!shell || !sidebar) {
      return;
    }

    shell.dataset.themeSidebarOpen = open ? 'true' : 'false';
    sidebar.classList.toggle('is-open', open);
  }

  sidebarToggles.forEach(function (button) {
    button.addEventListener('click', function () {
      var isOpen = shell && shell.dataset.themeSidebarOpen === 'true';
      setSidebar(!isOpen);
    });
  });

  sidebarClosers.forEach(function (button) {
    button.addEventListener('click', function () {
      setSidebar(false);
    });
  });

  dropdowns.forEach(function (dropdown) {
    var toggle = dropdown.querySelector('[data-theme-dropdown-toggle]');
    var menu = dropdown.querySelector('.theme-dropdown__menu');

    if (!toggle || !menu) {
      return;
    }

    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      menu.hidden = expanded;
    });
  });
})();
