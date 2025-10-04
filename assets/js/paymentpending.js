/**
 * payment_pending.js
 * * Implements client-side search functionality for the Pending Membership table.
 * It filters table rows based on the input in the search bar.
 */

 // Simple script to toggle the profile dropdown
        document.querySelector('.profile-btn').addEventListener('click', function() {
            document.querySelector('.profile-dropdown').classList.toggle('show');
        });
        // Close dropdown if user clicks outside
        window.addEventListener('click', function(e) {
            if (!document.querySelector('.profile-dropdown').contains(e.target) && document.querySelector('.profile-dropdown').classList.contains('show')) {
                document.querySelector('.profile-dropdown').classList.remove('show');
            }
        });

document.addEventListener('DOMContentLoaded', () => {
    // Log message runs when the page is fully loaded
    console.log("Pending Memberships page loaded successfully.");

    // Attach event listener for real-time filtering as the user types
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        // Trigger the search function every time a key is released
        searchInput.addEventListener('keyup', searchMember);
    }
});

// NOTE: The function is defined globally so it can be accessed by the HTML 
// onclick="searchMember()" attribute on the search button.
function searchMember() {
    // 1. Get the search input value and normalize it (trim whitespace and convert to lower case)
    const input = document.getElementById('searchInput');
    const filter = input.value.trim().toLowerCase();

    // 2. Get the table body and all its rows
    // Assuming the table is the first (or only) one in the main content and has a standard tbody
    const table = document.querySelector('.main-content table');
    // If the table or tbody is not found (e.g., if there are no pending members), exit.
    if (!table || !table.getElementsByTagName('tbody')[0]) return; 

    const tr = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    // Define the column indices to search (based on the PHP output order: ID, User ID, Full Name, Email)
    const SEARCH_COLUMNS = [
        0, // ID (members_id)
        1, // User ID (user_id)
        2, // Full Name
        3  // Email
    ];

    // 3. Loop through all table rows, and hide those that don't match the search query
    for (let i = 0; i < tr.length; i++) {
        let rowMatches = false;

        // Iterate through the defined search columns for the current row
        for (const colIndex of SEARCH_COLUMNS) {
            const td = tr[i].getElementsByTagName('td')[colIndex];
            
            if (td) {
                // Get the cell's text and normalize it
                const cellText = td.textContent || td.innerText;
                
                // Check if the filter term is found in the cell text
                if (cellText.toLowerCase().indexOf(filter) > -1) {
                    rowMatches = true;
                    break; // Stop checking columns for this row if a match is found
                }
            }
        }

        // Show or hide the row based on the match status
        if (rowMatches) {
            tr[i].style.display = ""; // Show the row
        } else {
            tr[i].style.display = "none"; // Hide the row
        }
    }
}
