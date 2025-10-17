// ======================================================================
// 1. Element Selections
// ======================================================================

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
// Select the button using its class: <button class="menu-btn" ...>
const menuBtn = document.querySelector(".menu-btn");


// ======================================================================
// 2. Password Show/Hide Toggle
// ======================================================================
if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", () => {
        const isPassword = passwordInput.getAttribute("type") === "password";

        // 1. Toggle the input type
        passwordInput.setAttribute("type", isPassword ? "text" : "password");

        // 2. Corrected icon toggling logic:

        if (isPassword) {
            // It WAS password (hidden), so switch to text (show) and display fa-eye-slash
            togglePassword.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            // It WAS text (show), so switch back to password (hide) and display fa-eye
            togglePassword.classList.replace("fa-eye-slash", "fa-eye");
        }
        
        // 3. Keep the 'active' class logic as it was (it correctly activates when NOT password)
        togglePassword.classList.toggle("active", !isPassword);
    });
}
// ======================================================================
// 3. Client-Side Validation + AJAX Login
// ======================================================================
if (signinForm) {
    signinForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // Prevent normal form submission

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        // Reset error message
        errorMsg.textContent = '';

        // Simple presence validation
        if (!email || !password) {
            errorMsg.textContent = 'Please enter both your email and password!';
            return;
        }

        // Email format check
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            errorMsg.textContent = 'Please enter a valid email address.';
            return;
        }

        // Disable button and prepare data for submission
        signinBtn.disabled = true;

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
                // ✅ PHP decides where to go
                window.location.href = data.redirect; 
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

// ======================================================================
// 4. Mobile Menu Toggle Logic (Function Definition)
// ======================================================================
function toggleMenu() {
    // Check if the elements were successfully selected
    if (mobileMenu && menuIcon) {
        if (mobileMenu.classList.contains("show")) {
            // Close menu
            mobileMenu.classList.remove("show");
            menuIcon.classList.replace("fa-times", "fa-bars");
        } else {
            // Open menu
            mobileMenu.classList.add("show");
            menuIcon.classList.replace("fa-bars", "fa-times");
        }
    }
}
// Note: This function is expected to be called via an onclick attribute in the HTML.

// ======================================================================
// 5. Close Mobile Menu When Clicking Outside
// ======================================================================
document.addEventListener("click", (e) => {
    // Ensure mobileMenu is shown and necessary elements exist
    if (
        mobileMenu && mobileMenu.classList.contains("show") &&
        menuBtn && // Ensures the button element exists
        !mobileMenu.contains(e.target) && // Click is outside the menu
        !menuBtn.contains(e.target)      // Click is outside the toggle button
    ) {
        mobileMenu.classList.remove("show");
        // Only update the icon if it exists
        if (menuIcon) {
            menuIcon.classList.replace("fa-times", "fa-bars");
        }
    }
});

// ======================================================================
// 6. Footer Year Update
// ======================================================================
const footerYear = document.getElementById('footerYear');
if (footerYear) {
    footerYear.textContent = new Date().getFullYear();
}