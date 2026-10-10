/**
 * JavaScript Aplikasi Utama - Perpustakaan Digital SMKN 2 Sumbawa Besar
 * Manajemen Tema, Sidebar, Toast, Konfirmasi Modal & Render QR Offline
 */

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initSidebar();
  initQrCodeRenderers();
  initGlobalSearch();
});

/* =========================================
   1. MANAJEMEN TEMA (LIGHT / DARK MODE)
   ========================================= */
function initTheme() {
  const savedTheme = localStorage.getItem('perpus_theme') || 'light';
  applyTheme(savedTheme);

  const toggleBtn = document.getElementById('themeToggleBtn');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      applyTheme(newTheme);
      localStorage.setItem('perpus_theme', newTheme);
    });
  }
}

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  const icon = document.getElementById('themeIcon');
  const text = document.getElementById('themeText');
  if (icon && text) {
    if (theme === 'dark') {
      icon.className = 'bi bi-moon-stars-fill text-warning';
      text.textContent = 'Dark';
    } else {
      icon.className = 'bi bi-sun-fill text-warning';
      text.textContent = 'Light';
    }
  }

  // Trigger event agar chart.js menyesuaikan warna
  window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
}

/* =========================================
   2. SIDEBAR TOGGLE (DESKTOP & MOBILE)
   ========================================= */
function initSidebar() {
  const toggleBtn = document.getElementById('sidebarToggleBtn');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      if (window.innerWidth <= 992) {
        document.body.classList.toggle('sidebar-mobile-open');
      } else {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
      }
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', () => {
      document.body.classList.remove('sidebar-mobile-open');
    });
  }

  // Restore collapsed state on desktop
  if (window.innerWidth > 992 && localStorage.getItem('sidebar_collapsed') === '1') {
    document.body.classList.add('sidebar-collapsed');
  }
}

/* =========================================
   3. RENDER QR CODE OFFLINE (qrcode.min.js)
   ========================================= */
function initQrCodeRenderers() {
  const containers = document.querySelectorAll('.qr-container');
  containers.forEach(el => {
    const text = el.getAttribute('data-qr-text');
    const size = parseInt(el.getAttribute('data-qr-size') || '120', 10);
    if (text && typeof QRCode !== 'undefined') {
      el.innerHTML = '';
      new QRCode(el, {
        text: text,
        width: size,
        height: size,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
    }
  });
}

/* =========================================
   4. TOAST NOTIFICATION UTILITY
   ========================================= */
function showToast(type, message) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container-custom';
    document.body.appendChild(container);
  }

  const bgClass = {
    'success': 'bg-success text-white',
    'danger':  'bg-danger text-white',
    'warning': 'bg-warning text-dark',
    'info':    'bg-info text-white'
  }[type] || 'bg-primary text-white';

  const iconClass = {
    'success': 'bi-check-circle-fill',
    'danger':  'bi-exclamation-triangle-fill',
    'warning': 'bi-exclamation-circle-fill',
    'info':    'bi-info-circle-fill'
  }[type] || 'bi-bell-fill';

  const toastEl = document.createElement('div');
  toastEl.className = `toast align-items-center ${bgClass} border-0 show shadow-lg`;
  toastEl.setAttribute('role', 'alert');
  toastEl.style.minWidth = '280px';
  toastEl.innerHTML = `
    <div class="d-flex">
      <div class="toast-body d-flex align-items-center gap-2">
        <i class="bi ${iconClass} fs-5"></i>
        <span>${message}</span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;

  container.appendChild(toastEl);

  setTimeout(() => {
    toastEl.classList.remove('show');
    setTimeout(() => toastEl.remove(), 300);
  }, 4000);
}

/* =========================================
   5. CONFIRMATION DIALOG HELPER
   ========================================= */
function confirmAction(message, onConfirm) {
  if (confirm(message)) {
    if (typeof onConfirm === 'function') {
      onConfirm();
    }
    return true;
  }
  return false;
}

/* =========================================
   6. GLOBAL SEARCH NAVBAR
   ========================================= */
function initGlobalSearch() {
  const searchInput = document.getElementById('globalSearchInput');
  if (!searchInput) return;

  let dropdown = document.getElementById('globalSearchDropdown');
  if (!dropdown) {
    dropdown = document.createElement('div');
    dropdown.id = 'globalSearchDropdown';
    dropdown.className = 'position-absolute bg-surface border rounded-3 shadow-lg p-2 mt-1';
    dropdown.style.display = 'none';
    dropdown.style.width = '350px';
    dropdown.style.zIndex = '1050';
    dropdown.style.maxHeight = '360px';
    dropdown.style.overflowY = 'auto';
    searchInput.parentNode.appendChild(dropdown);
  }

  let timer = null;
  searchInput.addEventListener('input', (e) => {
    clearTimeout(timer);
    const q = e.target.value.trim();
    if (q.length < 2) {
      dropdown.style.display = 'none';
      return;
    }

    timer = setTimeout(() => {
      fetch(`${window.BASE_URL || ''}/api/search_global.php?q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(data => {
          if (!data.results || data.results.length === 0) {
            dropdown.innerHTML = '<div class="p-2 text-muted small text-center">Tidak ada hasil ditemukan</div>';
            dropdown.style.display = 'block';
            return;
          }

          let html = '';
          data.results.forEach(item => {
            html += `
              <a href="${item.url}" class="d-flex align-items-center gap-2 p-2 text-decoration-none text-main border-bottom small hover-bg rounded">
                <i class="bi ${item.icon} text-primary fs-5"></i>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">${item.title}</span>
                  <span class="text-muted" style="font-size:11px;">${item.category} &bull; ${item.subtitle}</span>
                </div>
              </a>
            `;
          });
          dropdown.innerHTML = html;
          dropdown.style.display = 'block';
        })
        .catch(err => {
          console.error("Global search error:", err);
        });
    }, 300);
  });

  document.addEventListener('click', (e) => {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.style.display = 'none';
    }
  });
}
