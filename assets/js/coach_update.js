document.addEventListener('DOMContentLoaded', () => {
    const birthDateInput = document.getElementById('birthDate');
    const ageInput = document.getElementById('age');
    const imagePlaceholder = document.querySelector('.image-placeholder');
    const fileInput = document.getElementById('validID');

    // Function to calculate age from a date string (YYYY-MM-DD)
    function calculateAge(birthDate) {
        if (!birthDate) return '';
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const m = today.getMonth() - birth.getMonth();

        // Adjust age if the birthday hasn't occurred yet this year
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    }

    // Event listener for Birth Date change
    birthDateInput.addEventListener('change', () => {
        const age = calculateAge(birthDateInput.value);
        ageInput.value = age > 0 ? age : ''; // Only set age if it's positive
    });

    // Event listener to trigger file input when the placeholder is clicked
    imagePlaceholder.addEventListener('click', () => {
        fileInput.click();
    });

    // Optional: Display the selected image filename (or a preview if you implement it)
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            console.log('File selected:', fileInput.files[0].name);
            // You could update the placeholder content here to show a preview or filename
        }
    });

    // Basic form submission validation (front-end only)
    const form = document.getElementById('coachUpdateForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault(); // Stop form submission if validation fails
            alert('Please fill out all required fields.');
            // More advanced validation could go here
        } else {
             // In a real application, you'd make an AJAX call or let PHP handle the submission
             // event.preventDefault(); // Uncomment this line if using AJAX
             // console.log('Form data ready for submission (via PHP/AJAX)');
        }
    });
});
