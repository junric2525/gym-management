  // Wait until the DOM is fully loaded
document.addEventListener("DOMContentLoaded", () => {
  const coachBtn = document.querySelector(".coach-btn");
  const gymBtn = document.querySelector(".gym-btn");


  // Redirect when Coach Evaluation button is clicked
  coachBtn.addEventListener("click", () => {
    window.location.href = "CoachEvaluation.html"; // Update with the correct path
  });

  // Redirect when Gym Evaluation button is clicked
  gymBtn.addEventListener("click", () => {
    window.location.href = "GymEvaluation.html"; // Update with the correct path
  });

 
});
