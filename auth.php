<?php include("includes/header.php"); ?>





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
<?php include("includes/footer.php"); ?>