

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

function searchFeedback() {
  let input = document.getElementById("searchInput").value.toLowerCase();
  let table = document.getElementById("feedbackTable");
  let rows = table.getElementsByTagName("tr");

  for (let i = 1; i < rows.length; i++) { // skip the header row
    let rowText = rows[i].innerText.toLowerCase();
    if (rowText.includes(input)) {
      rows[i].style.display = "";
    } else {
      rows[i].style.display = "none";
    }
  }
}



