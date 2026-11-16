/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * Staff Authentication Handler
 */

document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.getElementById('login-form');
  const emailInput = document.getElementById('username-or-email');
  const passwordInput = document.getElementById('password-input');
  const rememberCheckbox = document.getElementById('remember-me');
  const togglePasswordBtn = document.getElementById('toggle-password-btn');
  const loginSubmitBtn = document.getElementById('login-submit-btn');
  const loginErrorAlert = document.getElementById('login-error-alert');
  const loginErrorMessage = document.getElementById('login-error-message');
  const demoFillBtn = document.getElementById('demo-credentials-btn');

  // Toggle password visibility
  if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener('click', () => {
      const isPassword = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
      togglePasswordBtn.innerHTML = isPassword
        ? `<svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>`
        : `<svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
    });
  }

  // One-click demo credentials fill
  if (demoFillBtn) {
    demoFillBtn.addEventListener('click', () => {
      if (emailInput) emailInput.value = 'admin@kanopoly.edu.ng';
      if (passwordInput) passwordInput.value = 'admin123';
      if (loginErrorAlert) loginErrorAlert.classList.add('hidden');
      showToast("Demo staff credentials loaded!", "info");
    });
  }

  // Handle Login submission
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const identifier = emailInput.value.trim();
      const password = passwordInput.value;
      const remember = rememberCheckbox ? rememberCheckbox.checked : false;

      // Basic client validation
      if (!identifier) {
        showError("Please enter your departmental username or email address.");
        emailInput.focus();
        return;
      }
      if (!password) {
        showError("Please enter your departmental password.");
        passwordInput.focus();
        return;
      }

      // Set Loading State
      loginSubmitBtn.disabled = true;
      loginSubmitBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Authenticating Staff...
      `;

      try {
        await api.auth.login(identifier, password, remember);
        showToast("Authentication successful! Welcome to Transcript Portal.", "success");
        setTimeout(() => {
          window.location.href = 'pages/dashboard.html';
        }, 600);
      } catch (err) {
        loginSubmitBtn.disabled = false;
        loginSubmitBtn.innerHTML = `Sign In to Portal`;
        showError(err.message || "Authentication failed. Check your staff credentials.");
      }
    });
  }

  function showError(msg) {
    if (loginErrorAlert && loginErrorMessage) {
      loginErrorMessage.textContent = msg;
      loginErrorAlert.classList.remove('hidden');
    } else {
      showToast(msg, 'error');
    }
  }

  // Forgot password modal
  window.handleForgotPassword = function() {
    confirmAction({
      title: "Departmental Password Reset",
      message: "To reset administrative staff credentials, please submit a signed memorandum to the Information Communication Technology (ICT) Directorate or contact the HOD Computer Science at hod.cs@kanopoly.edu.ng.",
      confirmText: "Understood",
      confirmClass: "bg-blue-900 hover:bg-blue-800 text-white",
      onConfirm: () => {}
    });
  };
});
