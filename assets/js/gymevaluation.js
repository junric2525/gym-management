 // Wait until the DOM is fully loaded
document.addEventListener("DOMContentLoaded", () => {
  const closeBtn = document.querySelector(".close-btn");
  const gymBtn = document.querySelector(".gym-btn");


  // Redirect when Coach Evaluation button is clicked
  closeBtn.addEventListener("click", () => {
    window.location.href = "gym_evalchoice.php"; // Update with the correct path
  });

  // Redirect when Gym Evaluation button is clicked
  gymBtn.addEventListener("click", () => {
    window.location.href = "gym_evalchoice.php"; // Update with the correct path
  });

 
});
