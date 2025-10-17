// Get references to form elements
const dateInput = document.getElementById("date");

// --- CORE FUNCTIONALITY: Prevent past dates from being selected ---
/**
 * Sets the 'min' attribute of the date input to today's date, 
 * preventing the user from selecting past dates.
 */
function setMinDate() {
    // Get today's date in YYYY-MM-DD format (required for date input min attribute)
    const today = new Date().toISOString().split("T")[0];
    if (dateInput) {
        dateInput.setAttribute("min", today);
    }
}

// Initialize the date constraint when the script loads
setMinDate();

// Note: All form submission logic has been removed to allow the HTML/PHP form to submit directly.