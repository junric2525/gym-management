document.addEventListener("DOMContentLoaded", () => {
  const closeBtn = document.querySelector(".close-btn"); // use class

  closeBtn.addEventListener("click", () => {
    window.location.href = "gym_evalchoice.php";
  });
});
