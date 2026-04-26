<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Horizon Chat · Join the conversation</title>
  <style>
    :root {
      --bg-start: #f2fbf5;
      --bg-end: #dcefe3;
      --panel: rgba(255, 255, 255, 0.92);
      --panel-soft: rgba(243, 251, 247, 0.96);
      --panel-border: rgba(20, 87, 83, 0.08);
      --accent: #12b9a3;
      --accent-strong: #0b7f72;
      --accent-soft: rgba(18, 185, 163, 0.16);
      --muted: #4b6b72;
      --text-dark: #0f2f32;
      --text-light: #28504d;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    }

    body {
      min-height: 100vh;
      background: linear-gradient(145deg, var(--bg-start) 0%, var(--bg-end) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
    }

    body::before {
      content: '';
      position: absolute;
      width: 200%;
      height: 200%;
      top: -50%;
      left: -50%;
      background: radial-gradient(circle at 30% 50%, rgba(18, 185, 163, 0.16), transparent 70%);
      animation: drift 30s infinite alternate;
      pointer-events: none;
    }

    @keyframes drift {
      0% { transform: translate(0, 0) rotate(0deg); }
      100% { transform: translate(5%, 3%) rotate(2deg); }
    }

    .auth-wrapper {
      width: 100%;
      max-width: 500px;
      background: var(--panel);
      backdrop-filter: blur(18px);
      border-radius: 2.5rem;
      border: 1px solid var(--panel-border);
      box-shadow: 0 30px 60px -24px rgba(15, 23, 42, 0.22);
      overflow: hidden;
      transition: all 0.3s ease;
      z-index: 2;
      position: relative;
    }

    .brand-logo {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      margin: 1rem 1.5rem 0;
      padding: 0.5rem 0.85rem;
      border-radius: 999px;
      background: rgba(18, 185, 163, 0.12);
      border: 1px solid rgba(18, 185, 163, 0.18);
      color: var(--accent-strong);
      font-weight: 700;
      font-size: 0.95rem;
      width: fit-content;
      box-shadow: 0 10px 25px -20px rgba(18, 185, 163, 0.35);
    }

    .brand-logo .logo-mark {
      font-size: 1.2rem;
    }

    .tab-header {
      display: flex;
      background: rgba(18, 185, 163, 0.1);
      border-bottom: 1px solid rgba(18, 185, 163, 0.18);
      gap: 0.2rem;
      padding: 0.3rem 0.3rem 0;
      margin-top: 0.75rem;
    }

    .tab-btn {
      flex: 1;
      text-align: center;
      padding: 1rem 0;
      font-size: 1.05rem;
      font-weight: 600;
      background: transparent;
      border: none;
      cursor: pointer;
      color: var(--muted);
      border-radius: 1.5rem 1.5rem 0 0;
      transition: all 0.2s;
      letter-spacing: -0.2px;
      backdrop-filter: blur(4px);
    }

    .tab-btn.active {
      color: var(--accent-strong);
      background: rgba(18, 185, 163, 0.14);
      border-bottom: 2px solid var(--accent-strong);
    }

    .tab-btn:hover:not(.active) {
      color: var(--text-dark);
      background: rgba(18, 185, 163, 0.06);
    }

    .forms-container {
      padding: 2rem 2rem 2.2rem;
    }

    .auth-form {
      display: none;
      flex-direction: column;
      gap: 1.3rem;
      animation: fadeSlide 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    .auth-form.active-form {
      display: flex;
    }

    @keyframes fadeSlide {
      from {
        opacity: 0;
        transform: translateY(12px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .auth-form h2 {
      font-size: 1.9rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
      text-align: center;
      letter-spacing: -0.5px;
    }

    .input-group {
      position: relative;
      width: 100%;
    }

    .input-group i {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--accent-strong);
      font-style: normal;
      font-weight: 500;
      font-size: 1.1rem;
      pointer-events: none;
    }

    .input-group input {
      width: 100%;
      padding: 0.85rem 1rem 0.85rem 2.8rem;
      font-size: 0.95rem;
      border: 1.5px solid rgba(18, 185, 163, 0.35);
      border-radius: 1.2rem;
      background: #f7fbf8;
      transition: all 0.2s;
      outline: none;
      color: var(--text-dark);
      font-weight: 500;
    }

    .input-group input:focus {
      border-color: var(--accent-strong);
      box-shadow: 0 0 0 3px rgba(18, 185, 163, 0.2);
      background: white;
    }

    .input-group input::placeholder {
      color: var(--muted);
      font-weight: 400;
    }

    .password-wrapper {
      position: relative;
    }

    .toggle-password {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      user-select: none;
      color: var(--accent-strong);
      font-size: 1.15rem;
      background: transparent;
      padding-left: 6px;
      transition: color 0.2s;
    }

    .toggle-password:hover {
      color: var(--accent);
    }

    .btn-submit {
      background: linear-gradient(95deg, var(--accent-strong), var(--accent));
      color: white;
      border: none;
      padding: 0.85rem;
      font-size: 1rem;
      font-weight: 600;
      border-radius: 1.4rem;
      cursor: pointer;
      transition: 0.25s;
      margin-top: 0.4rem;
      box-shadow: 0 8px 20px -6px rgba(18, 185, 163, 0.4);
      letter-spacing: 0.3px;
    }

    .btn-submit:hover {
      background: linear-gradient(95deg, #0c6a61, var(--accent-strong));
      transform: scale(0.98);
      box-shadow: 0 4px 12px -2px rgba(18, 185, 163, 0.45);
    }

    .footer-note strong {
      color: var(--accent-strong);
      cursor: pointer;
      transition: 0.15s;
    }

    .footer-note strong:hover {
      text-decoration: underline;
      color: var(--accent);
    }

    .btn-submit:disabled {
      background: #4b5563;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
      opacity: 0.7;
    }

    .error-msg, .success-msg {
      padding: 0.7rem;
      border-radius: 1rem;
      font-size: 0.85rem;
      text-align: center;
      display: none;
      backdrop-filter: blur(8px);
      font-weight: 500;
    }

    .error-msg {
      background: rgba(220, 38, 38, 0.2);
      color: #fecaca;
      border: 1px solid rgba(220, 38, 38, 0.5);
    }

    .success-msg {
      background: rgba(34, 197, 94, 0.2);
      color: #bbf7d0;
      border: 1px solid rgba(34, 197, 94, 0.5);
    }

    .field-hint {
      color: var(--muted);
      font-size: 0.7rem;
      margin-top: 0.3rem;
      margin-left: 0.8rem;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .footer-note {
      text-align: center;
      font-size: 0.8rem;
      color: var(--muted);
      margin-top: 1rem;
    }

    .footer-note strong {
      color: var(--accent-strong);
      cursor: pointer;
      transition: 0.15s;
    }

    .footer-note strong:hover {
      text-decoration: underline;
      color: var(--accent);
    }

    @media (max-width: 520px) {
      .auth-wrapper {
        max-width: 100%;
        border-radius: 1.8rem;
      }
      .forms-container {
        padding: 1.6rem;
      }
      .tab-btn {
        padding: 0.8rem 0;
        font-size: 0.95rem;
      }
    }

    .loading-spinner {
      display: inline-block;
      width: 14px;
      height: 14px;
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 0.6s linear infinite;
      margin-right: 8px;
      vertical-align: middle;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<div class="auth-wrapper">  <div class="brand-logo">
    <span class="logo-mark"></span>
    <span class="logo-text">Findex</span>
  </div>  <div class="tab-header">
    <button class="tab-btn active" data-form="login"> Login</button>
    <button class="tab-btn" data-form="register"> Sign up</button>
  </div>

  <div class="forms-container">
    <!-- LOGIN FORM -->
    <form id="loginForm" class="auth-form active-form">
      <h2>Welcome back</h2>
      <div class="input-group">
        <i>📧</i>
        <input type="email" name="email" id="loginEmail" placeholder="your@email.com" autocomplete="email" required>
      </div>
      <div class="input-group password-wrapper">
        <i>🔒</i>
        <input type="password" name="password" id="loginPassword" placeholder="Password" autocomplete="current-password" required>
        <span class="toggle-password" data-target="loginPassword"></span>
      </div>
      <div id="loginError" class="error-msg"></div>
      <button type="submit" class="btn-submit">Log in →</button>
      <div class="footer-note">Don't have an account? <strong id="switchToRegister">Create account</strong></div>
    </form>

    <!-- REGISTER FORM -->
    <form id="registerForm" class="auth-form">
      <h2>Join the circle</h2>
      <div class="input-group">
        <i>👤</i>
        <input type="text" name="name" id="regName" placeholder="Full name" autocomplete="name" required>
      </div>
      <div class="input-group">
        <i>📧</i>
        <input type="email" name="email" id="regEmail" placeholder="Email address" autocomplete="email" required>
      </div>
      <div class="input-group password-wrapper">
        <i>🔒</i>
        <input type="password" name="password" id="regPassword" placeholder="Password (min 6 chars)" autocomplete="new-password" required>
        <span class="toggle-password" data-target="regPassword"></span>
      </div>
      <div class="input-group password-wrapper">
        <i>✓</i>
        <input type="password" id="confirmPassword" placeholder="Confirm password" autocomplete="off" required>
        <span class="toggle-password" data-target="confirmPassword"></span>
      </div>
      <div id="registerError" class="error-msg"></div>
      <div id="registerSuccess" class="success-msg"></div>
      <button type="submit" class="btn-submit">Sign up →</button>
      <div class="footer-note">Already have an account? <strong id="switchToLogin">Log in here</strong></div>
    </form>
  </div>
</div>

<script>
  (function() {
    // DOM elements
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const tabBtns = document.querySelectorAll('.tab-btn');
    const switchToRegister = document.getElementById('switchToRegister');
    const switchToLogin = document.getElementById('switchToLogin');
    const loginErrorDiv = document.getElementById('loginError');
    const registerErrorDiv = document.getElementById('registerError');
    const registerSuccessDiv = document.getElementById('registerSuccess');

    // Helper: show error message
    function showError(element, message, duration = 4500) {
      if (!element) return;
      element.textContent = message;
      element.style.display = 'block';
      setTimeout(() => {
        if (element) element.style.display = 'none';
      }, duration);
    }

    function showSuccessMessage(msg, duration = 3000) {
      if (registerSuccessDiv) {
        registerSuccessDiv.textContent = msg;
        registerSuccessDiv.style.display = 'block';
        setTimeout(() => {
          if (registerSuccessDiv) registerSuccessDiv.style.display = 'none';
        }, duration);
      }
    }

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
      icon.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (input) {
          const isPassword = input.type === 'password';
          input.type = isPassword ? 'text' : 'password';
        
        }
      });
    });

    // Tab switching
    function setActiveForm(formType) {
      tabBtns.forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-form') === formType);
      });
      
      if (formType === 'login') {
        loginForm.classList.add('active-form');
        registerForm.classList.remove('active-form');
        registerErrorDiv.style.display = 'none';
        registerSuccessDiv.style.display = 'none';
        loginErrorDiv.style.display = 'none';
      } else {
        registerForm.classList.add('active-form');
        loginForm.classList.remove('active-form');
        loginErrorDiv.style.display = 'none';
        registerErrorDiv.style.display = 'none';
      }
    }

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => setActiveForm(btn.getAttribute('data-form')));
    });
    switchToRegister?.addEventListener('click', () => setActiveForm('register'));
    switchToLogin?.addEventListener('click', () => setActiveForm('login'));

    // Validation functions
    function validateRegisterClient() {
      const name = document.getElementById('regName').value.trim();
      const email = document.getElementById('regEmail').value.trim();
      const password = document.getElementById('regPassword').value;
      const confirm = document.getElementById('confirmPassword').value;

      if (name.length < 2) {
        showError(registerErrorDiv, '❌ Name must be at least 2 characters');
        return false;
      }
      const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showError(registerErrorDiv, '❌ Please enter a valid email address');
        return false;
      }
      if (password.length < 6) {
        showError(registerErrorDiv, '❌ Password must be at least 6 characters');
        return false;
      }
      if (password !== confirm) {
        showError(registerErrorDiv, '❌ Passwords do not match');
        return false;
      }
      return true;
    }

    function validateLoginClient() {
      const email = document.getElementById('loginEmail').value.trim();
      const pwd = document.getElementById('loginPassword').value;
      if (!email) {
        showError(loginErrorDiv, '📧 Please enter your email address');
        return false;
      }
      if (!pwd) {
        showError(loginErrorDiv, '🔐 Please enter your password');
        return false;
      }
      return true;
    }

    // Helper to set button loading state
    function setButtonLoading(button, isLoading, originalText) {
      if (isLoading) {
        button.disabled = true;
        button.innerHTML = '<span class="loading-spinner"></span> Processing...';
      } else {
        button.disabled = false;
        button.innerHTML = originalText;
      }
    }

    // LOGIN HANDLER - Fixed version
loginForm.addEventListener('submit', async function(e) {
  e.preventDefault();

  if (!validateLoginClient()) return;

  const submitBtn = this.querySelector('.btn-submit');
  const originalText = submitBtn.innerHTML;
  setButtonLoading(submitBtn, true, originalText);

  const formData = new FormData(this);

  try {
    const response = await fetch('/Findex/login.php', {
      method: 'POST',
      body: formData
    });

    const text = await response.text();

    if (text.trim() === "success") {
      window.location.href = "/Findex/dashboard.php";
    } else {
      showError(loginErrorDiv, text);
      setButtonLoading(submitBtn, false, originalText);
    }

  } catch (error) {
    console.error('Login error:', error);
    showError(loginErrorDiv, 'Network error. Please try again.');
    setButtonLoading(submitBtn, false, originalText);
  }
});

    // REGISTER HANDLER - Fixed version
registerForm.addEventListener('submit', async function(e) {
  e.preventDefault();

  if (!validateRegisterClient()) return;

  const submitBtn = this.querySelector('.btn-submit');
  const originalText = submitBtn.innerHTML;
  setButtonLoading(submitBtn, true, originalText);

  const formData = new FormData(this);

  try {
    const response = await fetch('/Findex/register.php', {
      method: 'POST',
      body: formData
    });

    const text = await response.text();

    if (text.trim() === "success") {

      showSuccessMessage("Account created successfully!");

      setTimeout(() => {
        window.location.href = "/Findex/auth.php";
      }, 1500);

    } else {
      showError(registerErrorDiv, text);
      setButtonLoading(submitBtn, false, originalText);
    }

  } catch (error) {
    console.error('Register error:', error);
    showError(registerErrorDiv, 'Network error. Please try again.');
    setButtonLoading(submitBtn, false, originalText);
  }
});




    // Clear errors on input
    const clearLoginError = () => { loginErrorDiv.style.display = 'none'; };
    document.getElementById('loginEmail')?.addEventListener('input', clearLoginError);
    document.getElementById('loginPassword')?.addEventListener('input', clearLoginError);
    
    const clearRegError = () => { 
      registerErrorDiv.style.display = 'none'; 
      registerSuccessDiv.style.display = 'none'; 
    };
    document.getElementById('regName')?.addEventListener('input', clearRegError);
    document.getElementById('regEmail')?.addEventListener('input', clearRegError);
    document.getElementById('regPassword')?.addEventListener('input', clearRegError);
    document.getElementById('confirmPassword')?.addEventListener('input', clearRegError);

    // Live password match hint
    const confirmField = document.getElementById('confirmPassword');
    const passwordField = document.getElementById('regPassword');
    
    function updateMatchHint() {
      if (confirmField.value.length > 0 && passwordField.value !== confirmField.value) {
        let hint = document.getElementById('matchHint');
        if (!hint) {
          hint = document.createElement('div');
          hint.id = 'matchHint';
          hint.className = 'field-hint';
          hint.style.color = '#fca5a5';
          confirmField.parentNode.insertBefore(hint, confirmField.nextSibling);
        }
        hint.innerHTML = '⚠️ Passwords do not match';
      } else {
        document.getElementById('matchHint')?.remove();
      }
    }
    
    passwordField?.addEventListener('input', updateMatchHint);
    confirmField?.addEventListener('input', updateMatchHint);
  })();
</script>
</body>
</html>