/**
 * SiPrakerin — Dark Mode Toggle
 * Simpan preferensi di localStorage, apply tanpa flash.
 */

(function () {
  // Terapkan tema sesegera mungkin (anti-flash)
  var saved = localStorage.getItem('siprakerin_theme');
  if (saved === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  }
})();

function toggleDarkMode() {
  var html    = document.documentElement;
  var isDark  = html.getAttribute('data-theme') === 'dark';
  var newTheme = isDark ? 'light' : 'dark';

  html.setAttribute('data-theme', newTheme);
  localStorage.setItem('siprakerin_theme', newTheme);

  // Update semua tombol toggle di halaman ini
  document.querySelectorAll('.dm-toggle').forEach(function (btn) {
    btn.title = newTheme === 'dark' ? 'Mode Terang' : 'Mode Gelap';
    btn.innerHTML = newTheme === 'dark' ? '☀️' : '🌙';
  });
}

// Sinkronkan icon tombol saat halaman load
document.addEventListener('DOMContentLoaded', function () {
  var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  document.querySelectorAll('.dm-toggle').forEach(function (btn) {
    btn.title   = isDark ? 'Mode Terang' : 'Mode Gelap';
    btn.innerHTML = isDark ? '☀️' : '🌙';
  });
});
