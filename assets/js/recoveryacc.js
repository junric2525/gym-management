// Handle form submit with AJAX
document.getElementById("recovery-form").addEventListener("submit", async (e) => {
  e.preventDefault();

  const email = document.getElementById("email").value;
  const submitBtn = e.target.querySelector("button[type=submit]");

  // Disable button + show loading state
  submitBtn.disabled = true;
  submitBtn.textContent = "Sending...";

  try {
    const response = await fetch("../backend/recoveryacc.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "email=" + encodeURIComponent(email)
    });

   const result = await response.json(); // parse JSON instead of text

    if (result.status === "success") {
      alert(result.message); // show only the message
      window.location.href = "../Guest/Signin.html"; // redirect after success
    } else {
      alert(result.message); // show error message
    }

  } catch (error) {
    console.error("Error:", error);
    alert("⚠ Something went wrong. Please try again.");
  } finally {
    // Re-enable button
    submitBtn.disabled = false;
    submitBtn.textContent = "Recover Account";
  }
});


// Toggle mobile menu
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
