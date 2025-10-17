// Get button elements
const coachBtn = document.querySelector('.coach-btn');
const gymBtn = document.querySelector('.gym-btn');
const footerYear = document.getElementById('footerYear');

// 1. Button Link Handlers
// ----------------------------------------------------------------------

// Coach Evaluation Button
if (coachBtn) {
    coachBtn.addEventListener('click', () => {
        // Redirect the user to the coach evaluation page
        // You'll need to replace 'coach_evaluation.php' with your actual file path
        window.location.href = 'coach_evaluation.php'; 
    });
}

// Gym Evaluation Button
if (gymBtn) {
    gymBtn.addEventListener('click', () => {
        // Redirect the user to the gym evaluation page
        // You'll need to replace 'gym_evaluation.php' with your actual file path
        window.location.href = 'gym_evaluation.php';
    });
}

// 2. Footer Year Update
// ----------------------------------------------------------------------
if (footerYear) {
    footerYear.textContent = new Date().getFullYear();
}