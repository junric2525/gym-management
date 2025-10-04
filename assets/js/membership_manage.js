document.addEventListener("DOMContentLoaded", () => {
  /* ========== PROFILE DROPDOWN ========== */
  const profileBtn = document.querySelector(".profile-btn");
  const profileDropdown = document.querySelector(".profile-dropdown");

  if (profileBtn) {
    profileBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      profileDropdown.classList.toggle("show");
    });
  }

  // Close dropdown if click outside
  document.addEventListener("click", (e) => {
    if (!profileDropdown.contains(e.target)) {
      profileDropdown.classList.remove("show");
    }
  });

  /* ========== SIDEBAR ACTIVE ITEM ========== */
  const sidebarItems = document.querySelectorAll(".sidebar li");

  sidebarItems.forEach((item) => {
    item.addEventListener("click", () => {
      sidebarItems.forEach((li) => li.classList.remove("active"));
      item.classList.add("active");
    });
  });

  /* ========== DELETE CONFIRMATION ========== */
  const deleteButtons = document.querySelectorAll("button[name='delete']");

  deleteButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      if (!confirm("Are you sure you want to DELETE this member? This action cannot be undone.")) {
        e.preventDefault();
      }
    });
  });
});

/* ========== SEARCH FUNCTION ========== */
function searchMember() {
  const input = document.getElementById("searchInput").value.toLowerCase();
  const table = document.querySelector("table");
  const rows = table.getElementsByTagName("tr");

  for (let i = 1; i < rows.length; i++) {
    const cells = rows[i].getElementsByTagName("td");
    let found = false;
    for (let j = 0; j < cells.length; j++) {
      if (cells[j]) {
        const text = cells[j].innerText.toLowerCase();
        if (text.includes(input)) {
          found = true;
          break;
        }
      }
    }
    rows[i].style.display = found ? "" : "none";
  }
}
