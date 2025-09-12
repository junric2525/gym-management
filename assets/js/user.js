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

  // // Mobile menu toggle
  // function toggleMenu() {
  //   document.getElementById("mobileMenu").classList.toggle("show");
  // }