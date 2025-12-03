/* ========= DARK / LIGHT THEME HANDLING ========= */

(function () {
  const STORAGE_KEY = 'motiv-theme';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
  }

  function getPreferredTheme() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'light' || stored === 'dark') return stored;

    return window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark'
      : 'light';
  }

  function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    applyTheme(next);
    localStorage.setItem(STORAGE_KEY, next);
    updateToggleIcon(next);
  }

  function updateToggleIcon(theme) {
    const btn = document.getElementById('customer-theme-toggle');
    if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
  }

  document.addEventListener('DOMContentLoaded', () => {
    const initial = getPreferredTheme();
    applyTheme(initial);
    updateToggleIcon(initial);

    const toggleBtn = document.getElementById('customer-theme-toggle');
    if (toggleBtn) toggleBtn.addEventListener('click', toggleTheme);
  });
})();
