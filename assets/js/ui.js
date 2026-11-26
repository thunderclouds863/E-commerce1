/* AS Berkah E-Commerce — Lightweight UI Interactions */
(function () {
  // Mobile Navigation Drawer
  const navToggle = document.querySelector('[data-nav-toggle]');
  const navLinks = document.querySelector('[data-nav-links]');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      const isOpen = navLinks.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
    });
    document.addEventListener('click', function (e) {
      if (!navLinks.contains(e.target) && !navToggle.contains(e.target)) {
        navLinks.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Password Visibility Toggle
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = btn.getAttribute('data-toggle-password');
      const input = document.getElementById(targetId);
      if (!input) return;
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      btn.setAttribute('aria-pressed', String(!showing));
    });
  });

  // Live Product Search & Sort (Customer Storefront)
  const searchInput = document.querySelector('[data-product-search]');
  const sortSelect = document.querySelector('[data-product-sort]');
  const productGrid = document.querySelector('[data-product-grid]');
  const countEl = document.querySelector('[data-product-count]');
  const emptySearchEl = document.querySelector('[data-search-empty]');

  if (productGrid) {
    const cards = Array.from(productGrid.querySelectorAll('[data-product-card]'));

    function updateCatalog() {
      const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
      const sort = sortSelect ? sortSelect.value : 'default';

      const sorted = cards.slice().sort(function (a, b) {
        if (sort === 'price-asc') return Number(a.dataset.price) - Number(b.dataset.price);
        if (sort === 'price-desc') return Number(b.dataset.price) - Number(a.dataset.price);
        if (sort === 'name-asc') return (a.dataset.name || '').localeCompare(b.dataset.name || '');
        return Number(a.dataset.index) - Number(b.dataset.index);
      });

      let visible = 0;
      sorted.forEach(function (card) {
        const hay = ((card.dataset.name || '') + ' ' + (card.dataset.desc || '')).toLowerCase();
        const match = !query || hay.includes(query);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
        productGrid.appendChild(card);
      });

      if (countEl) {
        countEl.textContent = visible + (visible === 1 ? ' product' : ' products');
      }
      if (emptySearchEl) {
        emptySearchEl.style.display = visible === 0 ? 'block' : 'none';
      }
    }

    if (searchInput) searchInput.addEventListener('input', updateCatalog);
    if (sortSelect) sortSelect.addEventListener('change', updateCatalog);
  }

  // Modal Backdrop & Escape Key Closing
  document.querySelectorAll('.ui-modal').forEach(function (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) modal.classList.remove('active');
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.ui-modal.active').forEach(function (m) {
        m.classList.remove('active');
      });
    }
  });

  // Auto-dismiss transient toasts
  document.querySelectorAll('[data-toast]').forEach(function (toast) {
    setTimeout(function () {
      toast.style.transition = 'opacity 300ms ease, transform 300ms ease';
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-8px)';
      setTimeout(function () { toast.remove(); }, 320);
    }, 4000);
  });

  // Copy to clipboard helper (Payment Code)
  document.querySelectorAll('[data-copy-text]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const text = btn.getAttribute('data-copy-text');
      if (!text || !navigator.clipboard) return;
      navigator.clipboard.writeText(text).then(function () {
        const orig = btn.textContent;
        btn.textContent = 'Copied';
        setTimeout(function () { btn.textContent = orig; }, 1800);
      });
    });
  });
})();
