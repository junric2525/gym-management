document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.querySelector("#signinForm"); // match form ID
  const validEmail = "admin@example.com";
  const validPassword = "Admin123";

  loginForm.addEventListener("submit", (e) => {
    e.preventDefault(); // prevent default form submission

    const email = loginForm.adminEmail.value.trim(); // match input name
    const password = loginForm.password.value.trim();

    // Password constraints: at least 6 characters, one uppercase, one lowercase, one number
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/;
    if (!passwordPattern.test(password)) {
      alert("Password must be at least 6 characters and include uppercase, lowercase, and a number.");
      return;
    }

    // Check credentials
    if (email === validEmail && password === validPassword) {
      window.location.href = '../Admin/AdminDashboard.html';
    } else {
      alert("Invalid email or password!");
    }
  });
});
