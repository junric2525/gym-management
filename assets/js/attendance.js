// 1. Profile Dropdown
const profileBtn = document.querySelector(".profile-btn");
const profileDropdown = document.querySelector(".profile-dropdown");

if (profileBtn) {
  profileBtn.addEventListener("click", (e) => {
    e.stopPropagation(); // prevent click bubbling
    profileDropdown.classList.toggle("show");
  });
}

// Close dropdown when clicking outside
window.addEventListener("click", (e) => {
  if (!e.target.closest(".profile-dropdown")) {
    profileDropdown.classList.remove("show");
  }
});

// 2. Dynamic Footer Year
const footerYear = document.getElementById("footerYear");
if (footerYear) {
  footerYear.textContent = new Date().getFullYear();
}

// Get references
const timeInBtn = document.getElementById("time-in");
const timeOutBtn = document.getElementById("time-out");
const idInput = document.getElementById("id-number");
const tableBody = document.getElementById("attendance-list");

// Helper: get current time in hh:mm:ss
function getCurrentTime() {
  const now = new Date();
  return now.toLocaleTimeString([], {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
}

// Helper: get current date (MM/DD/YYYY)
function getCurrentDate() {
  const now = new Date();
  return now.toLocaleDateString("en-US", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
}

// Time In
timeInBtn.addEventListener("click", () => {
  const id = idInput.value.trim();
  if (!id) {
    alert("Please enter an ID number!");
    return;
  }

  // Check if already timed in today
  let existingRow = Array.from(tableBody.rows).find(
    (row) => row.cells[0].textContent === id && row.cells[3].textContent === getCurrentDate()
  );

  if (existingRow) {
    alert("This ID has already timed in today.");
    return;
  }

  // Create new row
  const row = tableBody.insertRow();
  row.insertCell(0).textContent = id;              // ID number
  row.insertCell(1).textContent = getCurrentTime(); // Time in
  row.insertCell(2).textContent = "";              // Time out empty
  row.insertCell(3).textContent = getCurrentDate(); // Date

  idInput.value = ""; // clear input
});

// Time Out
timeOutBtn.addEventListener("click", () => {
  const id = idInput.value.trim();
  if (!id) {
    alert("Please enter an ID number!");
    return;
  }

  // Find today's row for that ID
  let existingRow = Array.from(tableBody.rows).find(
    (row) => row.cells[0].textContent === id && row.cells[3].textContent === getCurrentDate()
  );

  if (!existingRow) {
    alert("This ID has not timed in today.");
    return;
  }

  if (existingRow.cells[2].textContent) {
    alert("This ID has already timed out.");
    return;
  }

  existingRow.cells[2].textContent = getCurrentTime(); // Add time out
  idInput.value = ""; // clear input
});

// Allow Enter key to act as Time In
idInput.addEventListener("keypress", (e) => {
  if (e.key === "Enter") {
    timeInBtn.click();
  }
});

