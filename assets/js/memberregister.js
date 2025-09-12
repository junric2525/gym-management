// ===============================
// Mobile Menu Toggle
// ===============================
const menuBtn = document.querySelector(".menu-btn");
const mobileMenu = document.getElementById("mobileMenu");
menuBtn.addEventListener("click", e => {
  e.stopPropagation();
  mobileMenu.classList.toggle("show");
});

// ===============================
// Profile Dropdown
// ===============================
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

// ===============================
// Medical/Medications Toggle
// ===============================
['medicalConditions','medications'].forEach(name => {
  document.querySelectorAll(`input[name="${name}"]`).forEach(radio => {
    radio.addEventListener("change", function() {
      const detailsInput = document.getElementById(name === 'medicalConditions' ? 'medicalDetails' : 'medicationsDetails');
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

// ===============================
// File Validation
// ===============================
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

// ===============================
// Registration & GCash Modal
// ===============================
const registrationForm = document.getElementById("registrationForm");
const modal = document.getElementById("gcashModal");
const openModalBtn = document.getElementById("openModalBtn");
const closeModalBtn = document.getElementById("closeModalBtn");
const gcashForm = document.getElementById("gcashForm");
const refNumber = document.getElementById("refNumber");

// Open GCash modal after form validation
openModalBtn.addEventListener("click", () => {
  const medicalChecked = document.querySelector('input[name="medicalConditions"]:checked');
  const medicationsChecked = document.querySelector('input[name="medications"]:checked');

  if (!medicalChecked) return alert("Please select Yes or No for Medical Conditions.");
  if (!medicationsChecked) return alert("Please select Yes or No for Medications.");

  if (registrationForm.checkValidity()) {
    // Submit the registration form via PHP backend before opening modal
    // Use AJAX to submit registration without leaving page
    const formData = new FormData(registrationForm);
    fetch(registrationForm.action, {
      method: "POST",
      body: formData
    })
    .then(response => response.text()) // or response.json() if PHP returns JSON
    .then(data => {
      // Registration successful, open GCash modal
      modal.style.display = "flex";
    })
    .catch(err => {
      console.error(err);
      alert("Error submitting registration. Please try again.");
    });
  } else {
    registrationForm.reportValidity();
  }
});

// Close modal
closeModalBtn.addEventListener("click", () => modal.style.display = "none");
window.addEventListener("click", e => { if (e.target === modal) modal.style.display = "none"; });

// Submit GCash form to backend
gcashForm.addEventListener("submit", e => {
  e.preventDefault();
  const ref = refNumber.value.trim();
  if (!/^[0-9]{13}$/.test(ref)) return alert("Reference number must be exactly 13 digits (numbers only).");

  const formData = new FormData(gcashForm);
  fetch(gcashForm.action, {
    method: "POST",
    body: formData
  })
  .then(response => response.text()) // or response.json() if PHP returns JSON
  .then(data => {
    alert("Payment submitted successfully!");
    modal.style.display = "none";
    gcashForm.reset();
  })
  .catch(err => {
    console.error(err);
    alert("Error submitting payment. Please try again.");
  });
});

// ===============================
// Terms Modal
// ===============================
const termsModal = document.getElementById('termsModal');
const closeTermsBtn = document.getElementById('closeTermsBtn');
const termsLink = document.getElementById('termscondition');

termsLink.addEventListener('click', e => { 
  e.preventDefault(); 
  termsModal.style.display = 'flex'; 
});
closeTermsBtn.addEventListener('click', () => termsModal.style.display = 'none');
window.addEventListener('click', e => { if (e.target === termsModal) termsModal.style.display = 'none'; });
