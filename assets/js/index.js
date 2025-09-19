function toggleMenu() {
  const mobileMenu = document.getElementById('mobileMenu');
  const menuIcon = document.getElementById('menuIcon');

  mobileMenu.classList.toggle('show');

  if (mobileMenu.classList.contains('show')) {
    menuIcon.classList.remove('fa-bars');
    menuIcon.classList.add('fa-xmark');
  } else {
    menuIcon.classList.remove('fa-xmark');
    menuIcon.classList.add('fa-bars');
  }
}

// Automatically hide mobile menu when resizing to desktop
window.addEventListener('resize', () => {
  const mobileMenu = document.getElementById('mobileMenu');
  const menuIcon = document.getElementById('menuIcon');
  
  if (window.innerWidth >= 769 && mobileMenu.classList.contains('show')) {
    mobileMenu.classList.remove('show');
    menuIcon.classList.remove('fa-xmark');
    menuIcon.classList.add('fa-bars');
  }
});

// Simulated promo data (this will later come from backend API or DB)
const promoData = {
  image: "assets/img/september-promo.jpg", // replace with backend-uploaded path
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




