
// --- Form elements ---
const emailInput = document.getElementById('emailInput');
const passwordInput = document.getElementById('passwordInput');
const togglePassword = document.getElementById('togglePassword');
const signinBtn = document.getElementById('signinBtn');
const signinForm = document.getElementById('signinForm');
const errorMsg = document.getElementById('errorMsg'); // <p id="errorMsg"></p>

// --- Password show/hide toggle ---
if (togglePassword && passwordInput) {
  togglePassword.addEventListener("click", () => {
    const isPassword = passwordInput.getAttribute("type") === "password";
    passwordInput.setAttribute("type", isPassword ? "text" : "password");
    togglePassword.classList.toggle("fa-eye");
    togglePassword.classList.toggle("fa-eye-slash");
    togglePassword.classList.toggle("active", !isPassword);
  });
}

// --- Client-side validation + AJAX login ---
if (signinForm) {
  signinForm.addEventListener('submit', async (e) => {
    e.preventDefault(); // Prevent normal form submission

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    // Simple validation
    if (!email || !password) {
      errorMsg.textContent = 'Please enter both your email and password!';
      return;
    }

    // Optional: email format check
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
      errorMsg.textContent = 'Please enter a valid email address.';
      return;
    }

    // Disable button while sending
    signinBtn.disabled = true;
    errorMsg.textContent = '';

    const formData = new URLSearchParams();
    formData.append('email', email);
    formData.append('password', password);

    try {
      const response = await fetch('../backend/signin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        window.location.href = data.redirect; // ✅ PHP decides where to go
      } else {
        errorMsg.textContent = data.message || 'Invalid credentials';
      }
    } catch (err) {
      console.error('AJAX error:', err);
      errorMsg.textContent = 'An unexpected error occurred. Please try again.';
    } finally {
      signinBtn.disabled = false;
    }
  });
}

