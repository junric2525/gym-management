document.addEventListener('DOMContentLoaded', () => {
    const radioButtons = document.querySelectorAll('input[name="billing-cycle"]');
    const priceDisplay = document.getElementById('current-price');
    const cycleLabel = document.getElementById('cycle-label');
    const gcashInput = document.getElementById('gcash_reference'); // Get the GCash input field
    const paymentDetailsDiv = gcashInput ? gcashInput.closest('.payment-details') : null; // Get the parent container
    const submitButton = document.querySelector('.subscribe-button'); // Get the submit button (optional for clarity)
    const footerYear = document.getElementById('footerYear');

    const prices = {
        monthly: { value: '₱1000', label: 'per month' },
        daily: { value: '₱130', label: 'per day' }
    };

    // Function to update the displayed price and label
    function updateDisplay(cycle) {
        priceDisplay.textContent = prices[cycle].value;
        cycleLabel.textContent = prices[cycle].label;

        // --- NEW LOGIC: CONDITIONAL GCASH INPUT REQUIREMENT ---
        if (gcashInput && paymentDetailsDiv) {
            if (cycle === 'monthly') {
                // Monthly requires a reference
                gcashInput.required = true;
                paymentDetailsDiv.style.display = 'block'; // Show GCash details
            } else if (cycle === 'daily') {
                // Daily does NOT require a reference
                gcashInput.required = false;
                // Optional: Clear the value to prevent accidental submission of old data
                gcashInput.value = ''; 
                // Optional: You might choose to hide the payment details section entirely for daily
                // If the daily payment is expected to be processed *in person* later.
                // paymentDetailsDiv.style.display = 'none'; 
            }
        }
    }

    // Add event listener to each radio button
    radioButtons.forEach(radio => {
        radio.addEventListener('change', (event) => {
            const selectedCycle = event.target.value;
            updateDisplay(selectedCycle);
        });
    });

    // Initialize the display and the input requirement to the default checked state
    const initialCycle = document.querySelector('input[name="billing-cycle"]:checked').value;
    updateDisplay(initialCycle);

    // Set the current year for the footer
    if (footerYear) {
        footerYear.textContent = new Date().getFullYear();
    }
});