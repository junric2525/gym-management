

// === PROFILE DROPDOWN ===
document.addEventListener("DOMContentLoaded", () => {
  const profileBtn = document.querySelector(".profile-btn");
  const dropdownMenu = document.querySelector(".dropdown-menu");

  // Toggle dropdown on button click
  profileBtn.addEventListener("click", (e) => {
  e.stopPropagation();
  profileBtn.parentElement.classList.toggle("show"); // toggle on parent
});

  // Close dropdown when clicking outside
  document.addEventListener("click", () => {
    dropdownMenu.classList.remove("show");
  });

  // === IMAGE UPLOAD PREVIEW ===
  const imageUploads = document.querySelectorAll(".image-upload input[type='file']");

  imageUploads.forEach((input) => {
    input.addEventListener("change", (event) => {
      const file = event.target.files[0];
      if (file && file.type.startsWith("image/")) {
        const reader = new FileReader();

        reader.onload = function (e) {
          const placeholder = input.nextElementSibling;
          placeholder.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%; height:100%; object-fit:cover; border-radius:8px;">`;
        };

        reader.readAsDataURL(file);
      }
    });
  });

  // === CONFIRM BUTTON ACTION ===
  const confirmBtn = document.getElementById("confirmBtn");

  confirmBtn.addEventListener("click", () => {
    alert("Event/Promo updated successfully!");
  });
});
