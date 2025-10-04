 // ----------------------------------------------------
        // JAVASCRIPT FUNCTIONALITY (Embedded)
        // ----------------------------------------------------
        document.addEventListener('DOMContentLoaded', () => {
            const radioButtons = document.querySelectorAll('input[name="billing-cycle"]');
            const priceDisplay = document.getElementById('current-price');
            const cycleLabel = document.getElementById('cycle-label');
            const footerYear = document.getElementById('footerYear');

            const prices = {
                monthly: { value: '₱1000', label: 'per month' },
                daily: { value: '₱130', label: 'per day' }
            };

            // Function to update the displayed price and label
            function updateDisplay(cycle) {
                priceDisplay.textContent = prices[cycle].value;
                cycleLabel.textContent = prices[cycle].label;
            }

            // Add event listener to each radio button
            radioButtons.forEach(radio => {
                radio.addEventListener('change', (event) => {
                    const selectedCycle = event.target.value;
                    updateDisplay(selectedCycle);
                });
            });

            // Initialize the display to the default checked state
            const initialCycle = document.querySelector('input[name="billing-cycle"]:checked').value;
            updateDisplay(initialCycle);

            // Set the current year for the footer
            if (footerYear) {
                footerYear.textContent = new Date().getFullYear();
            }
        });