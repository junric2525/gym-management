// --- 1. Mobile Navigation Menu (Top Header) ---

function toggleMenu() {
    // Toggles the visibility of the main header's mobile menu
    document.getElementById("mobileMenu").classList.toggle("show-mobile");
}


// --- 2. Profile Dropdown (Desktop Header) ---

const profileBtn = document.querySelector(".profile-btn");
const profileDropdown = document.querySelector(".profile-dropdown");
const dropdownMenu = document.querySelector(".dropdown-menu");

if (profileBtn) {
    profileBtn.addEventListener("click", function (e) {
        // Stops the event from immediately bubbling up to the window click handler
        e.stopPropagation();
        dropdownMenu.classList.toggle("show-dropdown"); // Use unique class: show-dropdown
    });
}

// Close dropdown if clicked outside
window.addEventListener("click", function (e) {
    if (dropdownMenu && !profileDropdown.contains(e.target)) {
        dropdownMenu.classList.remove("show-dropdown");
    }
});

// Close dropdown when scrolling
window.addEventListener("scroll", () => {
    if (dropdownMenu) {
        dropdownMenu.classList.remove("show-dropdown");
    }
});


// --- 3. Quick Actions Burger Menu (Profile Page) ---

const burgerBtn = document.getElementById("burgerBtn");
const burgerNav = document.getElementById("burgerNav");

if (burgerBtn && burgerNav) {
    // Toggle burger menu
    burgerBtn.addEventListener("click", (e) => {
        e.stopPropagation(); // Prevents immediate close by document click listener
        burgerNav.classList.toggle("show-burger"); // Use unique class: show-burger
    });

    // Close burger menu when clicking outside
    document.addEventListener("click", (e) => {
        // Check if the click target is NOT the button and NOT inside the menu
        if (!burgerBtn.contains(e.target) && !burgerNav.contains(e.target)) {
            burgerNav.classList.remove("show-burger");
        }
    });

    // Close burger menu when scrolling
    window.addEventListener("scroll", () => {
        burgerNav.classList.remove("show-burger");
    });
}


// --- 4. Edit Profile Button ---

const editProfileBtn = document.getElementById("editProfileBtn");
if (editProfileBtn) {
    editProfileBtn.addEventListener("click", () => {
        alert("Edit profile functionality coming soon!");
    });
}

// --- 5. Footer Year Update ---
document.addEventListener("DOMContentLoaded", () => {
    const footerYear = document.getElementById("footerYear");
    if (footerYear) {
        footerYear.textContent = new Date().getFullYear();
    }
});