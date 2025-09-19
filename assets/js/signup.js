
  // ==========================
// Mobile Menu Toggle
// ==========================
function toggleMenu() {
  const mobileMenu = document.getElementById("mobileMenu");
  const menuIcon = document.getElementById("menuIcon");

  if (mobileMenu.classList.contains("show")) {
    mobileMenu.classList.remove("show");
    menuIcon.classList.replace("fa-times", "fa-bars");
  } else {
    mobileMenu.classList.add("show");
    menuIcon.classList.replace("fa-bars", "fa-times");
  }
}

// Close mobile menu when clicking outside
document.addEventListener("click", (e) => {
  const mobileMenu = document.getElementById("mobileMenu");
  const menuBtn = document.querySelector(".menu-btn");

  if (
    mobileMenu.classList.contains("show") &&
    !mobileMenu.contains(e.target) &&
    !menuBtn.contains(e.target)
  ) {
    mobileMenu.classList.remove("show");
    const menuIcon = document.getElementById("menuIcon");
    menuIcon.classList.replace("fa-times", "fa-bars");
  }
});




document.addEventListener("DOMContentLoaded", () => {


 

  // ==========================
  // Password Show/Hide Toggle
  // ==========================
 document.querySelectorAll(".toggle-password").forEach(icon => {
  icon.addEventListener("click", () => {
    const input = document.getElementById(icon.dataset.target);
    if (input.type === "password") {
      input.type = "text";
      icon.classList.replace("fa-eye", "fa-eye-slash");
      icon.classList.add("active"); // add CSS class
    } else {
      input.type = "password";
      icon.classList.replace("fa-eye-slash", "fa-eye");
      icon.classList.remove("active"); // remove CSS class
    }
  });
});


  // ==========================
  // Password Strength Check
  // ==========================
  const passwordInput = document.getElementById("signUpPassword");
  const passwordMessage = document.getElementById("passwordMessage");
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const strongPassword = /^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/;

  passwordInput.addEventListener("input", () => {
    passwordMessage.classList.toggle("hidden", strongPassword.test(passwordInput.value));
    if (confirmPasswordInput.value) {
      confirmPasswordInput.dispatchEvent(new Event("input"));
    }
  });

  // ==========================
  // Confirm Password Validation
  // ==========================
  confirmPasswordInput.addEventListener("input", () => {
    if (confirmPasswordInput.value !== passwordInput.value) {
      confirmPasswordInput.setCustomValidity("Passwords do not match");
      confirmPasswordInput.style.borderColor = "red";
    } else {
      confirmPasswordInput.setCustomValidity("");
      confirmPasswordInput.style.borderColor = "";
    }
  });

  
  // ==========================
  // Terms & Conditions Modal
  // ==========================
  const modal = document.getElementById("termsModal");
  const openTerms = document.getElementById("openTerms");
  const closeModal = document.querySelector(".close-modal");
  const acceptTerms = document.getElementById("acceptTerms");
  const termsCheckbox = document.getElementById("terms");

  if (openTerms && modal) {
    openTerms.addEventListener("click", (e) => {
      e.preventDefault();
      modal.style.display = "block";
    });

    if (closeModal) {
      closeModal.addEventListener("click", () => modal.style.display = "none");
    }

    if (acceptTerms) {
      acceptTerms.addEventListener("click", () => {
        termsCheckbox.checked = true;
        modal.style.display = "none";
      });
    }

    window.addEventListener("click", (e) => {
      if (e.target === modal) modal.style.display = "none";
    });
  }

  // ==========================
  // Final Form Validation
  // ==========================
  const signUpForm = document.getElementById("signUpForm");
  if (signUpForm) {
    signUpForm.addEventListener("submit", (e) => {
      if (!strongPassword.test(passwordInput.value)) {
        e.preventDefault();
        passwordMessage.classList.remove("hidden");
        passwordInput.focus();
        return;
      }
      if (confirmPasswordInput.value !== passwordInput.value) {
        e.preventDefault();
        confirmPasswordInput.focus();
        confirmPasswordInput.reportValidity();
        return;
      }
      if (!termsCheckbox.checked) {
        e.preventDefault();
        alert("You must agree to the Terms & Conditions.");
        return;
      }
    });
  }

});
