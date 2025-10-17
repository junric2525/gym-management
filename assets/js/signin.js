// --- Form elements ---
const emailInput = document.getElementById('emailInput');
const passwordInput = document.getElementById('passwordInput');
const togglePassword = document.getElementById('togglePassword');
const signinBtn = document.getElementById('signinBtn');
const signinForm = document.getElementById('signinForm');
const errorMsg = document.getElementById('errorMsg'); // <p id="errorMsg"></p>

// --- Menu Elements ---
const mobileMenu = document.getElementById("mobileMenu");
const menuIcon = document.getElementById("menuIcon");
// Select the button using its class, as referenced in your HTML: <button class="menu-btn" ...>
const menuBtn = document.querySelector(".menu-btn");


// ----------------------------------------------------------------------
// 1. Password show/hide toggle
// ----------------------------------------------------------------------
if (togglePassword && passwordInput) {
  togglePassword.addEventListener("click", () => {
    const isPassword = passwordInput.getAttribute("type") === "password";
    passwordInput.setAttribute("type", isPassword ? "text" : "password");
    togglePassword.classList.toggle("fa-eye");
    togglePassword.classList.toggle("fa-eye-slash");
    togglePassword.classList.toggle("active", !isPassword);
  });
}

// ----------------------------------------------------------------------
// 2. Client-side validation + AJAX login
// ----------------------------------------------------------------------
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

      // Check for network or server errors that aren't JSON
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
        window.location.href = data.redirect; // ✅ PHP decides where to go
      } else {
        errorMsg.textContent = data.message || 'Invalid credentials or login failed.';
      }
    } catch (err) {
      console.error('AJAX or Fetch error:', err);
      errorMsg.textContent = 'An unexpected network or server error occurred. Please try again.';
    } finally {
      signinBtn.disabled = false;
    }
  });
}

// ----------------------------------------------------------------------
// 3. Mobile Menu Toggle Logic
// ----------------------------------------------------------------------
function toggleMenu() {
  // Check if the elements were successfully selected
  if (mobileMenu && menuIcon) {
    if (mobileMenu.classList.contains("show")) {
      mobileMenu.classList.remove("show");
      menuIcon.classList.replace("fa-times", "fa-bars");
    } else {
      mobileMenu.classList.add("show");
      menuIcon.classList.replace("fa-bars", "fa-times");
    }
  }
}
// Note: The HTML already uses onclick="toggleMenu()", so an extra event listener here is redundant
// but harmless if you prefer to rely on the JS setup for consistency.

// ----------------------------------------------------------------------
// 4. Close mobile menu when clicking outside
// ----------------------------------------------------------------------
document.addEventListener("click", (e) => {
  // Ensure mobileMenu is shown and necessary elements exist
  if (
    mobileMenu && mobileMenu.classList.contains("show") &&
    menuBtn && // Ensures the button element exists
    !mobileMenu.contains(e.target) &&
    !menuBtn.contains(e.target)
  ) {
    mobileMenu.classList.remove("show");
    // Only update the icon if it exists
    if (menuIcon) {
      menuIcon.classList.replace("fa-times", "fa-bars");
    }
  }
});

// ----------------------------------------------------------------------
// 5. Footer Year Update
// ----------------------------------------------------------------------
const footerYear = document.getElementById('footerYear');
if (footerYear) {
    footerYear.textContent = new Date().getFullYear();
}