document.addEventListener('DOMContentLoaded', () => {
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
    // Handle tiles (if they exist)
    const tiles = document.querySelectorAll('.tile');
    tiles.forEach(tile => {
        tile.addEventListener('click', () => {
            const label = tile.querySelector('.tile-label')?.textContent.trim();
            if (label) {
                console.log(`Navigating to: ${label} section`);
                // Example navigation:
                // window.location.href = `/admin/${label.toLowerCase().replace(/\s+/g, '-')}`;
            }
        });
    });
});
