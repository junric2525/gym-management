document.addEventListener("DOMContentLoaded", () => {
  const closeBtn = document.querySelector(".close-btn"); // use class

  closeBtn.addEventListener("click", () => {
    window.location.href = "Gymevalchoice.html";
  });
});
