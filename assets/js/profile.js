function toggleMenu() {
  document.getElementById("mobileMenu").classList.toggle("show");
}

// Profile dropdown (desktop)
const profileBtn = document.querySelector(".profile-btn");
const profileDropdown = document.querySelector(".profile-dropdown");
const dropdownMenu = document.querySelector(".dropdown-menu");

profileBtn.addEventListener("click", function (e) {
  e.stopPropagation();
  dropdownMenu.classList.toggle("show");
});

// Close dropdown if clicked outside
window.addEventListener("click", function (e) {
  if (!profileDropdown.contains(e.target)) {
    dropdownMenu.classList.remove("show");
  }
});

// Close dropdown when scrolling
window.addEventListener("scroll", () => {
  dropdownMenu.classList.remove("show");
});

// Edit Profile Button
const editProfileBtn = document.getElementById("editProfileBtn");
editProfileBtn.addEventListener("click", () => {
  alert("Edit profile functionality coming soon!");
});

// Burger menu
const burgerBtn = document.getElementById("burgerBtn");
const burgerNav = document.getElementById("burgerNav");

// Toggle burger menu
burgerBtn.addEventListener("click", () => {
  burgerNav.classList.toggle("show");
});

// Close burger menu when clicking outside
document.addEventListener("click", (e) => {
  if (!burgerBtn.contains(e.target) && !burgerNav.contains(e.target)) {
    burgerNav.classList.remove("show");
  }
});

// ✅ Close burger menu when scrolling
window.addEventListener("scroll", () => {
  burgerNav.classList.remove("show");
});
