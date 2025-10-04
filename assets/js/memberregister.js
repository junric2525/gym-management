// Mobile Menu Toggle
const menuBtn = document.querySelector(".menu-btn");
const mobileMenu = document.getElementById("mobileMenu");
menuBtn.addEventListener("click", e => {
  e.stopPropagation();
  mobileMenu.classList.toggle("show");
});

// Profile Dropdown
const profileBtn = document.querySelector(".profile-btn");
const profileDropdown = document.querySelector(".profile-dropdown");
profileBtn.addEventListener("click", e => {
  e.stopPropagation();
  profileDropdown.classList.toggle("show");
});

// Close dropdowns if clicked outside
window.addEventListener("click", e => {
  if (!profileDropdown.contains(e.target)) profileDropdown.classList.remove("show");
  if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) mobileMenu.classList.remove("show");
});

// Medical/Medications Toggle
['medicalConditions','medications'].forEach(name => {
  document.querySelectorAll(`input[name="${name}"]`).forEach(radio => {
    radio.addEventListener("change", function() {
      const detailsInput = document.getElementById(
        name === 'medicalConditions' ? 'medicalDetails' : 'medicationsDetails'
      );
      if (this.value === "yes") {
        detailsInput.style.display = "block";
        detailsInput.required = true;
      } else {
        detailsInput.style.display = "none";
        detailsInput.required = false;
        detailsInput.value = "";
      }
    });
  });
});

// File Validation
const validIdUpload = document.getElementById("validIdUpload");
validIdUpload.addEventListener("change", () => {
  const file = validIdUpload.files[0];
  if (file) {
    const allowedTypes = ["image/jpeg", "image/png", "application/pdf"];
    const maxSize = 5 * 1024 * 1024;
    if (!allowedTypes.includes(file.type)) {
      alert("Invalid file type. Only JPG, PNG, or PDF allowed.");
      validIdUpload.value = "";
    } else if (file.size > maxSize) {
      alert("File too large. Maximum size is 5MB.");
      validIdUpload.value = "";
    }
  }
});

// --- FORM SUBMISSION VALIDATION ---
const registrationForm = document.getElementById("registrationForm");
registrationForm.addEventListener("submit", (e) => {
  const medicalChecked = document.querySelector('input[name="medicalConditions"]:checked');
  const medicationsChecked = document.querySelector('input[name="medications"]:checked');
  const gcashRef = document.querySelector('input[name="gcashReference"]').value.trim();

  if (!medicalChecked) {
    e.preventDefault();
    alert("Please select Yes or No for Medical Conditions.");
    return;
  }
  if (!medicationsChecked) {
    e.preventDefault();
    alert("Please select Yes or No for Medications.");
    return;
  }
  if (!/^\d{13}$/.test(gcashRef)) {
    e.preventDefault();
    alert("GCash Reference Number must be exactly 13 digits.");
    return;
  }

  // ✅ If all validations pass, form submits to register.php
});

// Terms Modal
const termsModal = document.getElementById('termsModal');
const closeTermsBtn = document.getElementById('closeTermsBtn');
const termsLink = document.getElementById('termscondition');

termsLink.addEventListener('click', e => { 
  e.preventDefault(); 
  termsModal.style.display = 'flex'; 
});
closeTermsBtn.addEventListener('click', () => termsModal.style.display = 'none');
window.addEventListener('click', e => { 
  if (e.target === termsModal) termsModal.style.display = 'none'; 
});

// Set footer year
document.getElementById('footerYear').textContent = new Date().getFullYear();


