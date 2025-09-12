document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("signUpForm");
  const passwordInput = document.getElementById("signUpPassword");
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const passwordMessage = document.getElementById("passwordMessage");

  // Password validation regex: 8+ chars, 1 uppercase, 1 special character
  const passwordRegex = /^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/;

  // Live validation on typing password
  passwordInput.addEventListener("input", () => {
    if (!passwordRegex.test(passwordInput.value)) {
      passwordMessage.classList.remove("hidden");
    } else {
      passwordMessage.classList.add("hidden");
    }
  });

  // Handle form submit
  form.addEventListener("submit", (e) => {
    e.preventDefault(); // stop default reload

    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    // Check password validity
    if (!passwordRegex.test(password)) {
      passwordMessage.classList.remove("hidden");
      return;
    }

    // Check confirm password
    if (password !== confirmPassword) {
      alert("Passwords do not match!");
      return;
    }

    // ✅ Success
    alert("Account created successfully!");
    form.reset();
  });
});

function toggleMenu() {
  const menu = document.getElementById("mobileMenu");
  const icon = document.getElementById("menuIcon");

  menu.classList.toggle("show");

  // Switch burger ↔ X
  if (menu.classList.contains("show")) {
    icon.classList.replace("fa-bars", "fa-times");
  } else {
    icon.classList.replace("fa-times", "fa-bars");
  }
}

// Auto-close menu when clicking a link/button
document.querySelectorAll("#mobileMenu a, #mobileMenu button").forEach(item => {
  item.addEventListener("click", () => {
    const menu = document.getElementById("mobileMenu");
    const icon = document.getElementById("menuIcon");

    // Close dropdown
    menu.classList.remove("show");
    // Reset icon back to burger
    icon.classList.replace("fa-times", "fa-bars");
  });
});

