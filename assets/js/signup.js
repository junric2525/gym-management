
// Mobile Menu Toggle

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
 
  // Password Show/Hide Toggle
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



  // Password Strength Check
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

 
  // Confirm Password Validation
  confirmPasswordInput.addEventListener("input", () => {
    if (confirmPasswordInput.value !== passwordInput.value) {
      confirmPasswordInput.setCustomValidity("Passwords do not match");
      confirmPasswordInput.style.borderColor = "red";
    } else {
      confirmPasswordInput.setCustomValidity("");
      confirmPasswordInput.style.borderColor = "";
    }
  });

  

  // Terms & Conditions Modal
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

// Final Form Validation + AJAX Submit
const signUpForm = document.getElementById("signUpForm");
const formMessage = document.getElementById("formMessage");

if (signUpForm) {
  signUpForm.addEventListener("submit", async (e) => {
    e.preventDefault(); // prevent page reload

    // Client-side validation
    if (!strongPassword.test(passwordInput.value)) {
      passwordMessage.classList.remove("hidden");
      passwordInput.focus();
      return;
    }
    if (confirmPasswordInput.value !== passwordInput.value) {
      confirmPasswordInput.focus();
      confirmPasswordInput.reportValidity();
      return;
    }
    if (!signUpForm.querySelector("input[name='agree']").checked) {
      alert("You must agree to the Terms & Conditions.");
      return;
    }

    // Prepare data
    const formData = new FormData(signUpForm);

    try {
      const response = await fetch(signUpForm.action, {
        method: "POST",
        body: formData
      });
      
      const result = await response.json();

    // Show message under form
    formMessage.style.display = "block";

    if (result.status === "success") {
      formMessage.className = "form-message success";
      formMessage.innerHTML = "✅ " + result.message;
      signUpForm.reset();
    } else {
      formMessage.className = "form-message error";

      if (Array.isArray(result.messages)) {
        // Multiple error messages
        formMessage.innerHTML = result.messages.map(m => "❌ " + m).join("<br>");
      } else {
        // Single error message
        formMessage.innerHTML = "❌ " + result.message;
      }
    }


    } catch (error) {
      formMessage.style.display = "block";
      formMessage.className = "form-message error";
      formMessage.textContent = "❌ Something went wrong. Please try again.";
    }
  });
}


});
