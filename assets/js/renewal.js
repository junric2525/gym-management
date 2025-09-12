// DOM References
const renewButtons = document.querySelectorAll(".btn-renew");
const gcashModal = document.getElementById("gcashModal");
const closeBtn = document.querySelector(".close-btn");
const selectedPlanInput = document.getElementById("selectedPlan");
const referenceInput = document.getElementById("referenceNumber");
const gcashForm = document.getElementById("gcashForm");
const currentInfoDiv = document.getElementById("currentInfo");

// Open modal when clicking a renew button
renewButtons.forEach(btn => {
  btn.addEventListener("click", () => {
    selectedPlanInput.value = btn.getAttribute("data-duration");
    referenceInput.value = "";
    gcashModal.style.display = "flex";
  });
});

// Close modal
closeBtn.addEventListener("click", () => gcashModal.style.display = "none");
window.addEventListener("click", e => {
  if (e.target === gcashModal) gcashModal.style.display = "none";
});

// Handle form submission via AJAX
gcashForm.addEventListener("submit", (e) => {
  e.preventDefault(); // prevent default form submit

  const plan = selectedPlanInput.value;
  const referenceNumber = referenceInput.value.trim();

  if (!referenceNumber) {
    alert("Please enter a GCash reference number.");
    return;
  }

  // Prepare data to send
  const formData = new FormData();
  formData.append("plan", plan);
  formData.append("referenceNumber", referenceNumber);

  // Send data to PHP
  fetch("process_subscription.php", {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("✅ Payment successful! Membership updated.");
      currentInfoDiv.innerHTML = data.membershipHtml; // PHP can return HTML
      gcashModal.style.display = "none";
    } else {
      alert("❌ Error: " + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert("❌ Something went wrong. Please try again.");
  });
});
