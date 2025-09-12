// Profile dropdown toggle
const profileBtn = document.querySelector(".profile-btn");
const dropdownMenu = document.querySelector(".dropdown-menu");

profileBtn.addEventListener("click", () => {
  // Toggle dropdown visibility
  dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
});

// Close dropdown when clicking outside
window.addEventListener("click", (e) => {
  if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
    dropdownMenu.style.display = "none";
  }
});
