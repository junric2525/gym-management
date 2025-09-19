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

// Simulated promo data (this will later come from backend API or DB)
const promoData = {
  image: "assets/img/promo.jpg", // replace with backend-uploaded path
  title: "🔥 September Gym Promo: 50% OFF Membership!"
};

// Function to update promo dynamically
function updatePromo(promo) {
  const promoImage = document.getElementById("promoImage");
  const promoTitle = document.getElementById("promoTitle");

  if (promo.image) promoImage.src = promo.image;
  if (promo.title) promoTitle.textContent = promo.title;
}

// Example: Load promo when page loads
document.addEventListener("DOMContentLoaded", () => {
  updatePromo(promoData);
});
