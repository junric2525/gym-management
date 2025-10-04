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



    // Attach confirmation dialogs
    const confirmButtons = document.querySelectorAll("button[name='confirm']");
    const rejectButtons = document.querySelectorAll("button[name='reject']");

    confirmButtons.forEach(btn => {
        btn.addEventListener("click", (e) => {
            if (!confirm("Are you sure you want to CONFIRM this membership?")) {
                e.preventDefault();
            }
        });
    });

    rejectButtons.forEach(btn => {
        btn.addEventListener("click", (e) => {
            if (!confirm("Are you sure you want to REJECT this membership? This action cannot be undone.")) {
                e.preventDefault();
            }
        });
    });
});


  