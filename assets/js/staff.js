// ==================== PROFILE DROPDOWN ====================
document.addEventListener("DOMContentLoaded", () => {
  const profileDropdown = document.querySelector(".profile-dropdown");
  const profileBtn = document.querySelector(".profile-btn");

  if (profileBtn) {
    profileBtn.addEventListener("click", () => {
      profileDropdown.classList.toggle("show");
    });
  }

  // Close dropdown if click outside
  document.addEventListener("click", (e) => {
    if (!profileDropdown.contains(e.target)) {
      profileDropdown.classList.remove("show");
    }
  });
});

// ==================== SEARCH STAFF ====================
function searchStaff() {
  const input = document.getElementById("searchInput").value.toLowerCase();
  const table = document.querySelector(".staff-table tbody");
  const rows = table.getElementsByTagName("tr");

  for (let row of rows) {
    const cells = row.getElementsByTagName("td");
    let match = false;

    for (let cell of cells) {
      if (cell.textContent.toLowerCase().includes(input)) {
        match = true;
        break;
      }
    }

    row.style.display = match ? "" : "none";
  }
}

// Make search work on typing (live search)
document.getElementById("searchInput").addEventListener("keyup", searchStaff);
