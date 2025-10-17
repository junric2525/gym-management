function toggleMenu() {
    document.getElementById("mobileMenu").classList.toggle("show");
}

// Toggle profile dropdown (desktop)
const profileBtn = document.querySelector(".profile-btn");
const profileDropdown = document.querySelector(".profile-dropdown");

profileBtn.addEventListener("click", function(e) {
    e.stopPropagation();
    profileDropdown.classList.toggle("show");
});

// Close dropdown if clicked outside
window.addEventListener("click", function(e) {
    if (!profileDropdown.contains(e.target)) {
        profileDropdown.classList.remove("show");
    }
});

// --- REMOVED PROMO LOGIC ---
// The following code was removed:
// 1. const promoData declaration.
// 2. function updatePromo(promo) {...} declaration.
// 3. document.addEventListener("DOMContentLoaded", ...) listener.
// The image path is now solely controlled by the PHP code in User/user.php.