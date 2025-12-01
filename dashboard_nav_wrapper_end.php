      </div>
    </div>
    <!-- /#page-content-wrapper -->
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
  <script>
    // Ensure Bootstrap modals are appended to document.body when shown
    // This prevents fixed elements (sidebar) from overlapping the modal/backdrop
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.modal').forEach(function (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
          // move modal to body so it's on top of other layout elements
          if (modalEl.parentNode !== document.body) document.body.appendChild(modalEl);
          // small timeout to allow Bootstrap to insert backdrop, then adjust z-index
          setTimeout(function () {
            document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.style.zIndex = 2000; });
            modalEl.style.zIndex = 3000;
          }, 0);
        });
      });
      
      // Keep submenu open when clicking items within it
      const currentPage = window.location.pathname.split('/').pop();
      const inventoryPages = ['inventory.php', 'add_item.php', 'categories.php'];
      
      // Auto-expand Inventory submenu if on any inventory page WITHOUT animation
      if (inventoryPages.includes(currentPage)) {
        const inventorySub = document.getElementById('inventorySub');
        if (inventorySub) {
          inventorySub.classList.add('show');
        }
      }
      
      // Store submenu state in sessionStorage
      const inventoryToggle = document.querySelector('[data-bs-toggle="collapse"][href="#inventorySub"]');
      const inventorySub = document.getElementById('inventorySub');
      
      // Restore submenu state from sessionStorage
      if (sessionStorage.getItem('inventorySubOpen') === 'true') {
        inventorySub.classList.add('show');
      }
      
      // Save state when submenu is toggled
      if (inventoryToggle) {
        inventoryToggle.addEventListener('click', function() {
          setTimeout(() => {
            sessionStorage.setItem('inventorySubOpen', inventorySub.classList.contains('show'));
          }, 350);
        });
      }
      
      // Close other submenus when clicking main nav items (except Inventory parent)
      const mainNavItems = document.querySelectorAll('.list-group-item-action:not([data-bs-toggle="collapse"])');
      mainNavItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
          // Don't close if clicking a sub-item
          if (!item.classList.contains('ps-4')) {
            // Close all collapse submenus and clear sessionStorage
            document.querySelectorAll('.collapse.show').forEach(function(collapse) {
              const bsCollapse = bootstrap.Collapse.getInstance(collapse);
              if (bsCollapse) bsCollapse.hide();
            });
            sessionStorage.removeItem('inventorySubOpen');
          }
        });
      });
    });
  </script>
</body>
</html>
