/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * Global Application Framework & UI Utilities
 */

// ============================================================================
// Toast Notification Engine
// ============================================================================
function showToast(message, type = 'success', duration = 3500) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'toast-item flex items-center p-4 rounded-xl text-sm font-medium shadow-xl border animate-slide-in';

  let iconSvg = '';
  let colorClasses = '';

  switch (type) {
    case 'success':
      colorClasses = 'bg-white text-slate-800 border-emerald-200 border-l-4 border-l-emerald-600';
      iconSvg = `<div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mr-3 shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      </div>`;
      break;
    case 'error':
      colorClasses = 'bg-white text-slate-800 border-red-200 border-l-4 border-l-red-600';
      iconSvg = `<div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3 shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </div>`;
      break;
    case 'warning':
      colorClasses = 'bg-white text-slate-800 border-amber-200 border-l-4 border-l-amber-500';
      iconSvg = `<div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mr-3 shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>`;
      break;
    default:
      colorClasses = 'bg-white text-slate-800 border-blue-200 border-l-4 border-l-blue-600';
      iconSvg = `<div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3 shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>`;
      break;
  }

  toast.className += ` ${colorClasses}`;
  toast.innerHTML = `
    ${iconSvg}
    <div class="flex-1 pr-2">${message}</div>
    <button type="button" class="text-slate-400 hover:text-slate-600 focus:outline-none" onclick="this.parentElement.remove()">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'slideOutRight 0.3s forwards';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ============================================================================
// Modal Dialog Management
// ============================================================================
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  document.body.classList.add('overflow-hidden');

  // Focus trap or first input
  const firstInput = modal.querySelector('input, select, textarea, button:not([data-close])');
  if (firstInput) firstInput.focus();
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.body.classList.remove('overflow-hidden');
}

// Global modal dismiss listeners
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const openModals = document.querySelectorAll('.fixed:not(.hidden)');
    openModals.forEach(m => {
      if (m.id && m.id !== 'toast-container') closeModal(m.id);
    });
  }
});

// Confirmation Dialog Prompt
function confirmAction({ title, message, confirmText = 'Confirm', confirmClass = 'bg-red-600 hover:bg-red-700 text-white', onConfirm }) {
  let modal = document.getElementById('global-confirm-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'global-confirm-modal';
    modal.className = 'fixed inset-0 z-50 hidden items-center justify-center p-4 modal-backdrop animate-fade-in';
    modal.innerHTML = `
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 transform transition-all">
        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h3 id="confirm-modal-title" class="text-lg font-bold text-slate-900 mb-2"></h3>
        <p id="confirm-modal-message" class="text-sm text-slate-600 mb-6"></p>
        <div class="flex justify-end gap-3">
          <button type="button" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition" onclick="closeModal('global-confirm-modal')">Cancel</button>
          <button id="confirm-modal-action-btn" type="button" class="px-4 py-2 text-sm font-semibold rounded-lg shadow-sm transition"></button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  }

  document.getElementById('confirm-modal-title').textContent = title;
  document.getElementById('confirm-modal-message').textContent = message;
  const actionBtn = document.getElementById('confirm-modal-action-btn');
  actionBtn.textContent = confirmText;
  actionBtn.className = `px-4 py-2 text-sm font-semibold rounded-lg shadow-sm transition ${confirmClass}`;

  actionBtn.onclick = () => {
    closeModal('global-confirm-modal');
    if (typeof onConfirm === 'function') onConfirm();
  };

  openModal('global-confirm-modal');
}

// ============================================================================
// Layout & Navigation Initialization
// ============================================================================
const AppLayout = {
  init() {
    this.setupAuthCheck();
    this.setupSidebarToggle();
    this.setupUserDropdown();
    this.highlightActiveNav();
    this.loadActiveSessionInfo();
  },

  setupAuthCheck() {
    // If on verify.html or login.html, do not enforce auth redirect
    const path = window.location.pathname;
    if (path.includes('verify.html') || path.includes('login.html') || path.endsWith('/index.html') || path.endsWith('/onlinecourse/') || path.endsWith('/onlinecourse')) {
      return;
    }

    const user = api.auth.getCurrentUser();
    if (!user) {
      // Auto-initialize demo guest session if not explicitly logged out
      const defaultUser = LocalStorageDB.getDB().userProfile;
      localStorage.setItem(API_CONFIG.AUTH_KEY, JSON.stringify({
        token: "demo_guest_token",
        user: defaultUser,
        expiresAt: Date.now() + 86400000
      }));
    }

    // Populate user profile info in topbar if elements exist
    const userDisplay = api.auth.getCurrentUser();
    if (userDisplay) {
      const nameElements = document.querySelectorAll('.user-display-name');
      const titleElements = document.querySelectorAll('.user-display-title');
      nameElements.forEach(el => el.textContent = userDisplay.name);
      titleElements.forEach(el => el.textContent = userDisplay.title || userDisplay.role);
    }
  },

  setupSidebarToggle() {
    const toggleBtn = document.getElementById('sidebar-toggle-btn');
    const mobileCloseBtn = document.getElementById('sidebar-close-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (toggleBtn && sidebar) {
      toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        if (overlay) overlay.classList.toggle('hidden');
      });
    }

    if (mobileCloseBtn && sidebar) {
      mobileCloseBtn.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
      });
    }

    if (overlay && sidebar) {
      overlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
      });
    }
  },

  setupUserDropdown() {
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown-menu');

    if (userMenuBtn && userDropdown) {
      userMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
      });

      document.addEventListener('click', (e) => {
        if (!userDropdown.contains(e.target) && e.target !== userMenuBtn) {
          userDropdown.classList.add('hidden');
        }
      });
    }
  },

  highlightActiveNav() {
    const currentPath = window.location.pathname;
    const links = document.querySelectorAll('.nav-link');
    links.forEach(link => {
      const href = link.getAttribute('href');
      if (href && (currentPath.endsWith(href) || (href !== 'dashboard.html' && currentPath.includes(href)))) {
        link.classList.remove('text-slate-300', 'hover:bg-slate-800', 'hover:text-white');
        link.classList.add('bg-blue-900', 'text-white', 'font-semibold', 'shadow-sm', 'border-l-4', 'border-amber-400');
      }
    });
  },

  async loadActiveSessionInfo() {
    const sessionIndicator = document.getElementById('active-session-indicator');
    if (sessionIndicator) {
      const sessions = await api.sessions.getAll();
      const semesters = await api.semesters.getAll();
      const activeSess = sessions.find(s => s.isActive) || sessions[0];
      const activeSem = semesters.find(s => s.isActive) || semesters[0];
      sessionIndicator.innerHTML = `
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Active Session: ${activeSess ? activeSess.name : '2024/2025'} &bull; ${activeSem ? activeSem.name : 'Second Semester'}
        </span>
      `;
    }
  },

  logout() {
    confirmAction({
      title: "Confirm Staff Logout",
      message: "Are you sure you want to end your administrative session for Kano State Polytechnic Academic Transcript System?",
      confirmText: "Logout",
      confirmClass: "bg-red-600 hover:bg-red-700 text-white",
      onConfirm: async () => {
        await api.auth.logout();
        showToast("Logged out successfully", "info");
        setTimeout(() => {
          // Go to login page relative to current folder
          const path = window.location.pathname;
          if (path.includes('/pages/')) {
            window.location.href = '../login.html';
          } else {
            window.location.href = 'login.html';
          }
        }, 500);
      }
    });
  }
};

// UI Badges helper
const UIHelpers = {
  getGradeBadge(grade) {
    const map = {
      'A': 'bg-emerald-100 text-emerald-800 border-emerald-300',
      'AB': 'bg-teal-100 text-teal-800 border-teal-300',
      'B': 'bg-blue-100 text-blue-800 border-blue-300',
      'BC': 'bg-cyan-100 text-cyan-800 border-cyan-300',
      'C': 'bg-amber-100 text-amber-800 border-amber-300',
      'CD': 'bg-orange-100 text-orange-800 border-orange-300',
      'D': 'bg-purple-100 text-purple-800 border-purple-300',
      'E': 'bg-rose-100 text-rose-800 border-rose-300',
      'F': 'bg-red-100 text-red-800 border-red-300'
    };
    const cls = map[grade] || 'bg-slate-100 text-slate-800 border-slate-300';
    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border ${cls}">${grade}</span>`;
  },

  getStatusBadge(status) {
    switch (status) {
      case 'Approved':
      case 'Active':
      case 'Valid':
      case 'Graduated':
      case 'VERIFIED':
        return `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>${status}
        </span>`;
      case 'Draft':
      case 'Upcoming':
        return `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
          <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>${status}
        </span>`;
      case 'Revoked':
      case 'Closed':
      case 'Inactive':
        return `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
          <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>${status}
        </span>`;
      default:
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">${status}</span>`;
    }
  }
};

window.showToast = showToast;
window.openModal = openModal;
window.closeModal = closeModal;
window.confirmAction = confirmAction;
window.AppLayout = AppLayout;
window.UIHelpers = UIHelpers;

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  AppLayout.init();
});
