(function() {
  'use strict';

  var STORAGE_KEY = 'fennec-theme';
  var html = document.documentElement;

  function getPreferredTheme() {
    var saved = localStorage.getItem(STORAGE_KEY);
    if (saved) return saved;
    return 'light';
  }

  function setTheme(theme) {
    if (theme === 'dark') {
      html.setAttribute('data-bs-theme', 'dark');
    } else {
      html.removeAttribute('data-bs-theme');
    }
    localStorage.setItem(STORAGE_KEY, theme);
  }

  function updateActiveLabel(theme) {
    var labels = document.querySelectorAll('.theme-toggle-label');
    labels.forEach(function(el) { el.classList.remove('active'); });
    var active = document.querySelector('.theme-toggle-label[data-theme="' + theme + '"]');
    if (active) active.classList.add('active');
    var icon = document.querySelector('.theme-toggle-icon i');
    if (icon) {
      icon.className = theme === 'dark' ? 'fa-regular fa-moon' : 'fa-regular fa-sun';
    }
  }

  function createToggle() {
    var container = document.querySelector('.navbar-detached .navbar-nav.navbar-right');
    if (!container) {
      container = document.querySelector('#header .nav.navbar-nav.navbar-right');
    }
    if (!container) return;

    var li = document.createElement('li');
    li.className = 'icon dropdown';

    var currentTheme = getPreferredTheme();
    var iconClass = currentTheme === 'dark' ? 'fa-regular fa-moon' : 'fa-regular fa-sun';

    li.innerHTML =
      '<a href="#" class="dropdown-toggle theme-toggle-icon" data-toggle="dropdown" title="Switch theme">' +
        '<i class="' + iconClass + '" style="font-size:1.2rem;line-height:1"></i>' +
      '</a>' +
      '<ul class="dropdown-menu dropdown-menu-right theme-dropdown">' +
        '<li><a href="#" class="theme-toggle-label' + (currentTheme === 'light' ? ' active' : '') + '" data-theme="light"><i class="fa-regular fa-sun"></i> Light</a></li>' +
        '<li><a href="#" class="theme-toggle-label' + (currentTheme === 'dark' ? ' active' : '') + '" data-theme="dark"><i class="fa-regular fa-moon"></i> Dark</a></li>' +
        '<li><a href="#" class="theme-toggle-label' + (currentTheme === 'system' ? ' active' : '') + '" data-theme="system"><i class="fa-regular fa-desktop"></i> System</a></li>' +
      '</ul>';

    container.prepend(li);

    li.addEventListener('click', function(e) {
      var target = e.target.closest('.theme-toggle-label');
      if (!target) return;
      e.preventDefault();
      var theme = target.getAttribute('data-theme');
      if (theme === 'system') {
        localStorage.removeItem(STORAGE_KEY);
        var os = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        setTheme(os);
        updateActiveLabel('system');
        updateIcon(os);
      } else {
        setTheme(theme);
        updateActiveLabel(theme);
        updateIcon(theme);
      }
    });
  }

  function updateIcon(theme) {
    var icon = document.querySelector('.theme-toggle-icon i');
    if (!icon) return;
    icon.className = theme === 'dark' ? 'fa-regular fa-moon' : 'fa-regular fa-sun';
  }

  // Init immediately (FOUC prevention)
  setTheme(getPreferredTheme());

  // Init UI on DOM ready
  function initThemeToggle() {
    createToggle();
    var t = getPreferredTheme();
    updateIcon(t);
    updateActiveLabel(t);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeToggle);
  } else {
    initThemeToggle();
  }

  // Listen for OS changes (only if user hasn't set a preference)
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
    if (!localStorage.getItem(STORAGE_KEY)) {
      setTheme(e.matches ? 'dark' : 'light');
      updateIcon(e.matches ? 'dark' : 'light');
      updateActiveLabel('system');
    }
  });
})();
