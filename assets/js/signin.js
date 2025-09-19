   // --- Form elements ---
    const emailInput = document.getElementById('emailInput');
    const signinBtn = document.getElementById('signinBtn');
    const signinForm = document.getElementById('signinForm');
    const errorMsg = document.getElementById('errorMsg'); // <p id="errorMsg"></p>

    // --- Client-side validation + AJAX login ---
    if (signinForm) {
    signinForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Prevent normal form submission

        const email = emailInput.value.trim();
        const password = passwordInput.value;

        // Simple client-side validation
        if (email === '' || password === '') {
        errorMsg.textContent = 'Please enter both your email and password!';
        return;
        }

        // Prepare form data for AJAX
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);

        // Send AJAX request
        fetch('../backend/signin.php', {
        method: 'POST',
        body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
            // Redirect if login successful
            window.location.href = '../User/User.html';
            } else {
            // Show error message on the same page
            errorMsg.textContent = data.message || 'Invalid credentials';
            }
        })
        .catch(err => {
            console.error('AJAX error:', err);
            errorMsg.textContent = 'An unexpected error occurred. Please try again.';
        });
    });
    }

    // --- Mobile menu toggle ---
    function toggleMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const menuIcon = document.getElementById('menuIcon');

    if (!mobileMenu || !menuIcon) return;

    mobileMenu.classList.toggle('show');

    if (mobileMenu.classList.contains('show')) {
        menuIcon.classList.remove('fa-bars');
        menuIcon.classList.add('fa-xmark');
    } else {
        menuIcon.classList.remove('fa-xmark');
        menuIcon.classList.add('fa-bars');
    }
    }

const togglePassword = document.querySelector("#togglePassword");
const passwordInput = document.querySelector("#passwordInput");

if (togglePassword && passwordInput) {
  togglePassword.addEventListener("click", () => {
    const isPassword = passwordInput.getAttribute("type") === "password";
    passwordInput.setAttribute("type", isPassword ? "text" : "password");

    // Toggle icons
    togglePassword.classList.toggle("fa-eye");
    togglePassword.classList.toggle("fa-eye-slash");

    // Toggle active state (color change)
    togglePassword.classList.toggle("active", !isPassword);
  });
}
