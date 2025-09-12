// staffupdate.js

// ===== PROFILE DROPDOWN =====
document.addEventListener("DOMContentLoaded", () => {
  const profileBtn = document.querySelector(".profile-btn");
  const profileDropdown = document.querySelector(".profile-dropdown");

  if (profileBtn) {
    profileBtn.addEventListener("click", (e) => {
      e.stopPropagation(); // Prevent closing immediately
      profileDropdown.classList.toggle("show");
    });

    // Close dropdown if clicked outside
    document.addEventListener("click", () => {
      profileDropdown.classList.remove("show");
    });
  }
});

// ===== AUTO CALCULATE AGE =====
const birthdateInput = document.getElementById("birthdate");
const ageInput = document.getElementById("age");

if (birthdateInput && ageInput) {
  birthdateInput.addEventListener("change", () => {
    const birthdate = new Date(birthdateInput.value);
    if (!isNaN(birthdate.getTime())) {
      const today = new Date();
      let age = today.getFullYear() - birthdate.getFullYear();
      const monthDiff = today.getMonth() - birthdate.getMonth();

      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
        age--;
      }
      ageInput.value = age;
    } else {
      ageInput.value = "";
    }
  });
}

// ===== VALID ID PREVIEW =====
const validIDInput = document.getElementById("validID");
const previewImg = document.getElementById("previewImg");

if (validIDInput && previewImg) {
  validIDInput.addEventListener("change", () => {
    const file = validIDInput.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        previewImg.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
}

// ===== FORM SUBMISSION =====
const staffForm = document.getElementById("staffForm");

if (staffForm) {
  staffForm.addEventListener("submit", (e) => {
    e.preventDefault(); // Prevent refresh

    // Grab values (for demo purpose, can be sent to backend later)
    const fname = document.getElementById("fname").value;
    const lname = document.getElementById("lname").value;
    const contact = document.getElementById("contact").value;

    alert(`✅ Staff updated successfully!\n\nName: ${fname} ${lname}\nContact: ${contact}`);

    // Optional: reset form after submission
    // staffForm.reset();
    // previewImg.src = "https://cdn-icons-png.flaticon.com/512/1160/1160358.png";
  });
}
