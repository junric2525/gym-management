// Get references to form elements
const form = document.querySelector(".appointment-form");
const dateInput = document.getElementById("date");

// Prevent past dates from being selected
const today = new Date().toISOString().split("T")[0];
dateInput.setAttribute("min", today);

// Handle form submission
form.addEventListener("submit", function(e) {
  e.preventDefault(); // prevent default submission

  const name = document.getElementById("name").value.trim();
  const email = document.getElementById("email").value.trim();
  const coach = document.getElementById("coach").value;
  const date = document.getElementById("date").value;
  const time = document.getElementById("time").value;

  // Basic validation
  if (!name || !email || !coach || !date || !time) {
    alert("Please fill in all required fields.");
    return;
  }

  // Optional: email pattern validation
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(email)) {
    alert("Please enter a valid email address.");
    return;
  }

  // Show confirmation message
  alert(`Thank you ${name}! Your appointment with ${coach} is booked for ${date} at ${time}.`);

  // Optionally, submit the form to the server
  form.submit();
});
