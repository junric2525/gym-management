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



const modal = document.getElementById('loginModal');
const closeBtn = document.querySelector('.close-btn');

// Grab all "Sign In" buttons (header, mobile menu, bottom)
const openBtns = document.querySelectorAll('.btn-signin');

// Function to open modal
function openModal() {
  modal.style.display = 'flex';
  // close mobile menu if it's open
  document.getElementById("mobileMenu").classList.remove("show");
  document.getElementById("menuIcon").classList.add("fa-bars");
  document.getElementById("menuIcon").classList.remove("fa-times");
}

// Attach modal opening to all Sign In buttons
openBtns.forEach(btn => {
  btn.addEventListener('click', openModal);
});

// Close modal (X button)
closeBtn.addEventListener('click', () => {
  modal.style.display = 'none';
});

// Close modal (click outside the card)
window.addEventListener('click', (e) => {
  if (e.target === modal) {
    modal.style.display = 'none';
  }
});

  window.onload = function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get("showLogin") === "true") {
      document.getElementById("loginModal").style.display = "flex"; 
    }
  };


