// Sidebar is fixed and always visible in this layout — keep JS minimal.
document.addEventListener('DOMContentLoaded', function () {
  // Ensure the page content aligns exactly to the rendered sidebar width
  // (handles cases where CSS percentage vs. computed pixels cause visible gaps).
  var sidebar = document.getElementById('sidebar-wrapper');
  var content = document.getElementById('page-content-wrapper');
  function syncLayout() {
    if (!sidebar || !content) return;
    var w = sidebar.getBoundingClientRect().width;
    content.style.marginLeft = w + 'px';
    content.style.width = 'calc(100% - ' + w + 'px)';
  }
  syncLayout();
  window.addEventListener('resize', function () { window.requestAnimationFrame(syncLayout); });
});
