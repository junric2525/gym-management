// =================================================================
// GLOBAL FUNCTION FOR TABLE SEARCH (Called by HTML onkeyup/onclick)
// =================================================================

/**
 * Filters the membership applications table rows based on the text
 * entered in the search input field. The search is case-insensitive
 * and checks the 'ID' (column 0) and 'Full Name' (column 2) columns.
 */
function searchMember() {
    // 1. Get the search term and normalize it
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();

    // 2. Get the table body and all its rows
    const table = document.querySelector('.table-responsive table');
    // Ensure table and tbody exist
    const tbody = table ? table.querySelector('tbody') : null;

    if (!tbody) {
        // If the table is empty or doesn't exist, just return silently
        return;
    }

    const tr = tbody.getElementsByTagName('tr');

    // 3. Loop through all table rows, and hide those that don't match
    for (let i = 0; i < tr.length; i++) {
        // Column indices based on your HTML table structure:
        // 0: ID (members_id), 1: User ID, 2: Full Name
        const id_td = tr[i].getElementsByTagName('td')[0];
        const name_td = tr[i].getElementsByTagName('td')[2];

        if (id_td && name_td) {
            const id_text = id_td.textContent || id_td.innerText;
            const name_text = name_td.textContent || name_td.innerText;

            // Check if the filter is found in either the ID or the Full Name
            if (id_text.toUpperCase().indexOf(filter) > -1 || name_text.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = ""; // Show the row
            } else {
                tr[i].style.display = "none"; // Hide the row
            }
        }
    }
}


// =================================================================
// DOMContentLoaded - Event Listeners for UI and Actions
// =================================================================
document.addEventListener("DOMContentLoaded", () => {
    
    /* ================== PROFILE DROPDOWN ================== */
    const profileBtn = document.querySelector(".profile-btn");
    const profileDropdown = document.querySelector(".profile-dropdown");

    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle("show");
        });

        // Close dropdown if clicked outside
        document.addEventListener("click", (e) => {
            if (!profileDropdown.contains(e.target) && profileDropdown.classList.contains("show")) {
                profileDropdown.classList.remove("show");
            }
        });
    }

    /* ================== SIDEBAR ACTIVE ITEM ================== */
    // Note: The 'active' class is already correctly applied to the <li> in your PHP.
    // This JS snippet is usually used if you want to allow clicking an item 
    // to change its 'active' state without a page reload. Since your links 
    // cause a reload, the PHP handles the active state better, but we'll keep 
    // the listener structure if you want to use it for future dynamic changes.
    const sidebarItems = document.querySelectorAll(".sidebar li");
    sidebarItems.forEach((item) => {
        item.addEventListener("click", () => {
            // Optional: Keep this if the user clicks a link that reloads the page
            // to a different view, which might confuse the active state logic.
            // Since payment_pendingview.php is currently the active page, 
            // the PHP hardcoding is sufficient.
        });
    });


    /* ================== ACTION CONFIRMATION (APPROVE/REJECT) ================== */
    
    // Select the actual buttons that trigger the actions
    const approveButtons = document.querySelectorAll(".action-btn.approve-btn");
    const rejectButtons = document.querySelectorAll(".action-btn.reject-btn");

    // Add confirmation to Approve buttons
    approveButtons.forEach(btn => {
        btn.addEventListener("click", (e) => {
            // Find the parent form of the button
            const form = btn.closest('form');
            if (form) {
                 // Prevent default form submission initially
                e.preventDefault(); 
                
                // Show standard confirm dialog
                if (confirm("Are you sure you want to ACCEPT this membership application? This will create a permanent member record.")) {
                    // If confirmed, submit the form programmatically
                    form.submit();
                }
            }
        });
    });

    // Add confirmation to Reject buttons
    rejectButtons.forEach(btn => {
        btn.addEventListener("click", (e) => {
            const form = btn.closest('form');
            if (form) {
                e.preventDefault(); 

                if (confirm("Are you sure you want to REJECT this membership? This will DELETE the pending application and cannot be undone.")) {
                    form.submit();
                }
            }
        });
    });
});