// Handle close button (redirect back to sign-in page)
document.getElementById("closeBtn").addEventListener("click", () => {
  window.location.href = "http://127.0.0.1:5500/Guest/Signin.html"; // change this path if needed
});

// Handle form submit
document.getElementById("recovery-form").addEventListener("submit", (e) => {
  e.preventDefault();

  const email = document.getElementById("email").value;
  alert(`Recovery link has been sent to: ${email}`);

  // TODO: Add backend call here (e.g., fetch API to send recovery email)
});

function toggleMenu() {
  const mobileMenu = document.getElementById('mobileMenu'); // the dropdown menu
  const menuIcon = document.getElementById('menuIcon');     // the icon inside the button

  // Toggle visibility
  mobileMenu.classList.toggle('show');

  // Optional: toggle icon between bars and X
  if (mobileMenu.classList.contains('show')) {
    menuIcon.classList.remove('fa-bars');
    menuIcon.classList.add('fa-xmark');
  } else {
    menuIcon.classList.remove('fa-xmark');
    menuIcon.classList.add('fa-bars');
  }
}
