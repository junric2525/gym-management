// =======================================================
// MOBILE MENU TOGGLE (MUST BE GLOBAL)
// (This needs to be defined FIRST and OUTSIDE DOMContentLoaded)
// =======================================================
window.toggleMenu = function() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu) {
        mobileMenu.classList.toggle('show-mobile'); 
        console.log("MOBILE: Toggling menu visibility.");
    }
};


document.addEventListener('DOMContentLoaded', () => {
    // Check for general script execution
    console.log("DOM READY: Running edit_profile.js logic.");

    // =======================================================
    // 1. DESKTOP PROFILE DROPDOWN LOGIC
    // =======================================================
    const profileDropdown = document.querySelector('.profile-dropdown');
    const profileBtn = document.querySelector('.profile-btn');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (profileBtn && dropdownMenu) {
        console.log("DROPDOWN: Button and Menu elements found.");
        
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation(); 
            dropdownMenu.classList.toggle('show-dropdown');
            console.log("DROPDOWN: Click event fired. Class toggled.");
        });
    } else {
        console.error("DROPDOWN ERROR: Could not find button or menu. Check selectors.");
        // Log the elements to see what is null
        console.log("profileBtn:", profileBtn);
        console.log("dropdownMenu:", dropdownMenu);
    }
    
    // ... (Keep the rest of your click-outside logic and form logic here) ...
    // ... (The form toggle logic is less critical for the header problem) ...

    // Close the dropdown when clicking anywhere else on the document
    document.addEventListener('click', (e) => {
        // ... (rest of the logic) ...
    });
    
    // Form toggle logic...
});