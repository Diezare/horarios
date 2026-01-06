// app/assets/js/script.js

// =============================
// PROTEÇÃO CSRF GLOBAL
// =============================

// Função global para pegar CSRF token do header
function getCSRFToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// Intercepta TODOS os fetch automaticamente
const originalFetch = window.fetch;
window.fetch = function (...args) {
  if (args[1] && args[1].method && args[1].method.toUpperCase() === 'POST') {
    // Adiciona header CSRF automaticamente
    args[1].headers = {
      ...args[1].headers,
      'X-CSRF-Token': getCSRFToken()
    };
  }
  return originalFetch.apply(this, args);
};

// =============================
// UTIL: garantir SweetAlert2 local (offline)
// =============================
function ensureSwalLocal() {
  if (window.Swal && Swal.fire) return Promise.resolve();
  const swPath = document.documentElement.dataset.swPath; // definido no <html data-sw-path="...">
  return new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = swPath + '/sweetalert2.all.min.js';
    s.defer = true;
    s.onload = () => (window.Swal && Swal.fire) ? resolve() : reject();
    s.onerror = reject;
    document.head.appendChild(s);
  });
}

// =============================
// CONFIRMAR LOGOUT (única, 100% offline)
// =============================
function confirmarLogout(urlLogout) {
  ensureSwalLocal()
    .then(() => {
      return Swal.fire({
        title: 'Tem certeza que deseja sair?',
        text: 'Você será desconectado do sistema.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, sair',
        cancelButtonText: 'Cancelar'
      });
    })
    .then((res) => {
      if (res && res.isConfirmed) window.location.href = urlLogout;
    })
    .catch(() => {
      if (window.confirm('Tem certeza que deseja sair?')) {
        window.location.href = urlLogout;
      }
    });
}

// =============================
// CÓDIGO DA APLICAÇÃO
// =============================
document.addEventListener('DOMContentLoaded', function () {
  // =============================
  // ELEMENTOS BASE
  // =============================
  const sidebar = document.getElementById('sidebar');
  const toggleSidebarBtn = document.querySelector('nav .toggle-sidebar');
  const allSideDivider = document.querySelectorAll('#sidebar .divider');
  const allDropdown = document.querySelectorAll('#sidebar .side-dropdown');
  const profile = document.querySelector('nav .profile');
  const imgProfile = profile ? profile.querySelector('img') : null;
  const dropdownProfile = profile ? profile.querySelector('.profile-link') : null;
  const allMenu = document.querySelectorAll('main .content-data .head .menu');

  // =============================
  // FUNÇÕES UTILITÁRIAS
  // =============================
  const setDividers = (textModeDash) => {
    allSideDivider.forEach((item) => {
      item.textContent = textModeDash ? '-' : item.dataset.text;
    });
  };

  const closeAllDropdowns = () => {
    allDropdown.forEach((ul) => {
      const a = ul.previousElementSibling;
      if (a) a.classList.remove('active');
      ul.classList.remove('show');
    });
  };

  const closeAllDropdownsExcept = (currentUl) => {
    allDropdown.forEach((ul) => {
      if (ul !== currentUl) {
        const a = ul.previousElementSibling;
        if (a) a.classList.remove('active');
        ul.classList.remove('show');
      }
    });
  };

  // =============================
  // SIDEBAR - COLAPSE / EXPANDE
  // =============================
  if (sidebar) {
    if (sidebar.classList.contains('hide')) {
      setDividers(true);
      closeAllDropdowns();
    } else {
      setDividers(false);
    }

    if (toggleSidebarBtn) {
      toggleSidebarBtn.addEventListener('click', function () {
        sidebar.classList.toggle('hide');
        const isHidden = sidebar.classList.contains('hide');
        setDividers(isHidden);
        if (isHidden) closeAllDropdowns();
      });
    }

    sidebar.addEventListener('mouseleave', function () {
      if (sidebar.classList.contains('hide')) {
        closeAllDropdowns();
        setDividers(true);
      }
    });
    sidebar.addEventListener('mouseenter', function () {
      if (sidebar.classList.contains('hide')) {
        setDividers(false);
      }
    });
  }

  // ==========================================
  // SIDEBAR - ACCORDION
  // ==========================================
  document.querySelectorAll('#sidebar .has-dropdown').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      const dropdown = trigger.nextElementSibling;
      if (!dropdown) return;

      const isOpen = dropdown.classList.contains('show');
      closeAllDropdownsExcept(dropdown);

      if (isOpen) {
        dropdown.classList.remove('show');
        trigger.classList.remove('active');
      } else {
        dropdown.classList.add('show');
        trigger.classList.add('active');
      }
    });
  });

  // =============================
  // PROFILE - MENU DO AVATAR
  // =============================
  if (imgProfile && dropdownProfile) {
    imgProfile.addEventListener('click', function (e) {
      e.stopPropagation();
      dropdownProfile.classList.toggle('show');
    });

    document.addEventListener('click', function (e) {
      if (!profile.contains(e.target)) {
        dropdownProfile.classList.remove('show');
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') dropdownProfile.classList.remove('show');
    });
  }

  // =============================
  // MENUS DE AÇÃO NOS CARDS
  // =============================
  allMenu.forEach((item) => {
    const icon = item.querySelector('.icon');
    const menuLink = item.querySelector('.menu-link');
    if (!icon || !menuLink) return;

    icon.addEventListener('click', function (e) {
      e.stopPropagation();
      document
        .querySelectorAll('main .content-data .head .menu .menu-link.show')
        .forEach((ml) => {
          if (ml !== menuLink) ml.classList.remove('show');
        });
      menuLink.classList.toggle('show');
    });

    document.addEventListener('click', function (e) {
      if (!item.contains(e.target)) menuLink.classList.remove('show');
    });
  });

  // =============================
  // PROGRESS BAR (cards)
  // =============================
  document.querySelectorAll('main .card .progress').forEach((item) => {
    item.style.setProperty('--value', item.dataset.value);
  });

  // =============================
  // LOGOUT COM CONFIRMAÇÃO
  // =============================
  document.addEventListener('click', function (e) {
    const a = e.target.closest('a[data-logout-url]');
    if (!a) return;
    e.preventDefault();
    const url = a.getAttribute('data-logout-url');
    if (!url) return;
    confirmarLogout(url);
  });
});