document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("renewalForm");

    form.addEventListener("submit", (e) => {
        const ref = document.getElementById("gcash_reference").value.trim();

        if (ref.length !== 13 || isNaN(ref)) {
            e.preventDefault();
            // ❌ THIS LINE WAS THE PROBLEM: It said "14-digit"
            // ✅ CORRECTION: Update the message to "13-digit"
            alert("Please enter a valid 13-digit numeric GCash Reference Number."); 
        }
    });
});