
document.addEventListener("DOMContentLoaded", () => {
  /* ================== PROFILE DROPDOWN ================== */
  const profileBtn = document.querySelector(".profile-btn");
  const profileDropdown = document.querySelector(".profile-dropdown");

  if (profileBtn) {
    profileBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      profileDropdown.classList.toggle("show");
    });
  }

  // Close dropdown if clicked outside
  document.addEventListener("click", (e) => {
    if (!profileDropdown.contains(e.target)) {
      profileDropdown.classList.remove("show");
    }
  });

  /* ================== SIDEBAR ACTIVE ITEM ================== */
  const sidebarItems = document.querySelectorAll(".sidebar li");

  sidebarItems.forEach((item) => {
    item.addEventListener("click", () => {
      sidebarItems.forEach((li) => li.classList.remove("active"));
      item.classList.add("active");
    });
  });

  /* ================== SEARCH FUNCTION ================== */
  window.searchMember = function () {
    const input = document.getElementById("searchInput").value.toLowerCase();
    const rows = document.querySelectorAll("#paymentTableBody tr");

    rows.forEach((row) => {
      const memberId = row.cells[0].textContent.toLowerCase();
      if (memberId.includes(input)) {
        row.style.display = "";
      } else {
        row.style.display = "none";
      }
    });
  };
});