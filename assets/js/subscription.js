// Get modal and buttons
const modal = document.getElementById("gcashModal");
const closeBtn = document.querySelector(".close-btn");
const subscribeButtons = document.querySelectorAll(".btnsubscribe");
const gcashForm = document.getElementById("gcashForm");
const referenceInput = document.getElementById("referenceNumber");

// Track selected plan (temporary variable)
let selectedPlan = "";

// Open modal when any "Subscribe" button is clicked
subscribeButtons.forEach(button => {
  button.addEventListener("click", () => {
    const planCard = button.closest(".plan-card");
    const planName = planCard.querySelector("h2").innerText;
    const planPrice = planCard.querySelector(".price").innerText;

    // Save to temporary variable
    selectedPlan = `${planName} - ${planPrice}`;

    // Show modal
    modal.style.display = "flex";
  });
});

// Close modal on (x) click
closeBtn.addEventListener("click", () => {
  modal.style.display = "none";
});

// Close modal when clicking outside content
window.addEventListener("click", (e) => {
  if (e.target === modal) {
    modal.style.display = "none";
  }
});

// Handle Reference Number Submission
gcashForm.addEventListener("submit", (e) => {
  e.preventDefault(); // prevent page reload

  const referenceNumber = referenceInput.value.trim();
  if (referenceNumber === "") {
    alert("⚠ Please enter a reference number."); 
    return;
  }

   // Check if it's exactly 13 digits
  const refPattern = /^\d{13}$/; 
  if (!refPattern.test(referenceNumber)) {
    alert("⚠ Reference number must be exactly 13 digits.");
    return;
  }

  // For now, just show confirmation
  alert(`✅ Thank you! You subscribed to: ${selectedPlan}\nReference No: ${referenceNumber}`);

  // (Later you will send this to PHP with fetch or form submit)
  // Example with fetch (to be used when backend is ready):
  // fetch("process_subscription.php", {
  //   method: "POST",
  //   headers: { "Content-Type": "application/x-www-form-urlencoded" },
  //   body: `plan=${encodeURIComponent(selectedPlan)}&reference=${encodeURIComponent(referenceNumber)}`
  // });

  // Reset input & close modal
  referenceInput.value = "";
  modal.style.display = "none";
});
