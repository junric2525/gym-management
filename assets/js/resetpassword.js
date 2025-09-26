// Show/Hide password
document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', () => {
        const target = document.getElementById(icon.getAttribute('data-target'));
        if (target.type === "password") {
            target.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            target.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    });
});

// Password validation
function validatePassword() {
    const password = document.getElementById("new_password").value;
    const confirm = document.getElementById("confirm_password").value;
    const errorDiv = document.getElementById("error-message");

    // At least 8 chars, 1 uppercase, 1 special char
    const regex = /^(?=.*[A-Z])(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/;

    if (!regex.test(password)) {
        errorDiv.textContent = "⚠ Password must be at least 8 characters, include 1 uppercase letter and 1 special character.";
        return false;
    }

    if (password !== confirm) {
        errorDiv.textContent = "⚠ Passwords do not match.";
        return false;
    }

    errorDiv.textContent = ""; // clear errors
    return true;
}