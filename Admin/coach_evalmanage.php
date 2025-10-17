<?php

session_start();

// --- Initialization ---
$message = '';
$messageType = '';
$coach_evaluations = [];
$conn = null; // Initialize connection variable
$db_error = false;

// =======================================================================
// 1. Database Connection Handling
// =======================================================================
if (file_exists('../backend/db.php')) {
    include '../backend/db.php'; 
    
    // Check if $conn was set and if the connection is successful
    if (!isset($conn) || $conn->connect_error) {
        $message = "FATAL ERROR: Could not connect to the database.";
        $messageType = 'error';
        $conn = null; // Ensure $conn is null if connection failed
        $db_error = true;
    }
} else {
    $message = "FATAL ERROR: db.php not found. Cannot connect to database.";
    $messageType = 'error';
    $db_error = true;
}

// =======================================================================
// 2. CRITICAL SECURITY CHECK & Session Access
// =======================================================================
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Non-admin users are redirected to the guest index
    header("Location: ../Guest/index.php");
    exit(); 
}

// Safely fetch admin user details for display (though not used in HTML, good practice)
$current_user_id = $_SESSION['user_id'] ?? 'N/A'; 
$current_user_full_name = $_SESSION['full_name'] ?? 'Admin';


// =======================================================================
// 3. HANDLE DELETE REQUESTS (Single Delete & Delete All)
//    - Single Delete uses standard POST form submission.
//    - Delete All is designed to be hit by the AJAX (fetch) request.
// =======================================================================
if (!$db_error && $conn !== null && $conn->connect_error === null) {
    if (isset($_POST['delete_id'])) {
        // --- Handle Single Delete (Standard Form Submission) ---
        $delete_id = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);

        if ($delete_id !== false) {
            // Prepare the DELETE statement to prevent SQL injection
            $stmt = $conn->prepare("DELETE FROM coach_evaluations WHERE evaluation_id = ?");
            
            if ($stmt === false) {
                error_log("Failed to prepare single delete statement: " . $conn->error);
                $message = "Database Error: Could not prepare delete statement.";
                $messageType = 'error';
            } else {
                $stmt->bind_param("i", $delete_id);

                if ($stmt->execute()) {
                    $message = "Evaluation ID **{$delete_id}** successfully deleted.";
                    $messageType = 'success';
                } else {
                    error_log("SQL Error deleting evaluation: " . $stmt->error);
                    $message = "Error deleting evaluation. Check logs for details.";
                    $messageType = 'error';
                }
                $stmt->close();
            }
        } else {
            $message = "Invalid Evaluation ID provided for deletion.";
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete_all']) && $_POST['delete_all'] === 'true') {
        // --- Handle Delete All (Targeted by AJAX/Fetch) ---
        // NOTE: We don't exit here. The page will reload via JS fetch() success handler.
        $sql_delete_all = "DELETE FROM coach_evaluations";
        
        if ($conn->query($sql_delete_all)) {
            $deleted_count = $conn->affected_rows;
            if ($deleted_count > 0) {
                $message = "{$deleted_count} coach evaluations successfully deleted.";
                $messageType = 'success';
            } else {
                $message = "No evaluations found to delete (table was already empty).";
                $messageType = 'info';
            }
        } else {
            error_log("SQL Error deleting all evaluations: " . $conn->error);
            $message = "Error deleting all evaluations. Check logs for details.";
            $messageType = 'error';
        }
        // Since this script renders the full page, the message is set above and 
        // will be displayed when the page reloads (see JS below).
    }
}

// =======================================================================
// 4. FETCH ALL COACH EVALUATIONS
// =======================================================================
// Check if connection is valid before running the query
if (!$db_error && $conn !== null && $conn->connect_error === null) {
    
    $sql = "
        SELECT 
            ce.evaluation_id, 
            ce.behavior_rating, 
            ce.teaching_rating, 
            ce.communication_rating, 
            ce.opinion, 
            ce.evaluation_date,
            c.name AS coach_name, 
            CONCAT_WS(' ', u.first_name, u.last_name) AS member_name, 
            ce.member_id 
        FROM 
            coach_evaluations ce
        JOIN 
            membership m ON ce.member_id = m.members_id
        JOIN
            users u ON m.user_id = u.id 
        LEFT JOIN
            coaches c ON ce.coach_id = c.coach_id
        ORDER BY 
            ce.evaluation_date DESC;
    ";
    
    $result = $conn->query($sql);
    
    if ($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $coach_evaluations[] = $row;
            }
            $result->free();
        } else {
            // Only show this message if a delete message wasn't just shown
            if (empty($message)) {
                $message = "No coach evaluations have been submitted yet.";
                $messageType = 'info';
            }
        }
    } else {
        // Log the actual error for debugging
        error_log("SQL Error fetching coach evaluations: " . $conn->error);
        $message = "Database Error: Could not retrieve coach evaluations. Check table structure.";
        $messageType = 'error';
    }
}

// The connection must be closed at the end of the script execution if it was opened successfully
if ($conn !== null && $conn->connect_error === null) {
    // Check if the connection is still open before closing
    if (isset($conn->server_info)) {
        $conn->close(); 
    }
}


// --- HELPER FUNCTION FOR MESSAGE DISPLAY ---
function display_message($msg, $type) {
    if (!empty($msg)) {
        // Determine color class based on message type
        $class = $type === 'success' ? 'success' : ($type === 'info' ? 'info' : 'error');
        
        // Output the message box with a simple close button
        echo "<div id='messageBox' class='message-box {$class}'>";
        echo htmlspecialchars($msg);
        echo "<span class='close-message-btn' onclick='document.getElementById(\"messageBox\").style.display=\"none\";'>&times;</span>";
        echo "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Coach Evaluations</title>
    <link rel="stylesheet" href="../assets/css/coach_evalmanage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    
</head>
<body>

    <header class="header">
        <div class="header-content-wrapper header-flex">
            <div class="logo">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <div class="profile-dropdown">
                <button class="profile-btn">
                    <i class="fas fa-user"></i> <i class="fas fa-caret-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="Admin.php"><i class="fas fa-user"></i> Home</a> 
                    <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>

                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        
       <aside class="sidebar">
            <ul>
                <li><a href="Attendance_monitoring.php"><i class="fas fa-user-check"></i> Attendance Monitoring</a></li>
                <li><a href="Attendance_history.php"><i class="fas fa-user-check"></i> Attendance Logs</a></li>
                <li><a href="membership_manage.php"><i class="fas fa-users"></i> Membership Management</a></li>
                <li><a href="pending_renewal.php"><i class="fas fa-hand-holding-usd"></i> Renewal Pending</a></li>
                <li><a href="payment_pendingview.php"><i class="fas fa-hourglass-half"></i> Membership Pending</a></li>
                <li><a href="subscriptionview.php"><i class="fas fa-sync-alt"></i> Subscription Management</a></li>
                <li><a href="pending_subscription.php"><i class="fas fa-hourglass-half"></i> Subscription Pending</a></li>
                <li><a href="coach_update.php"><i class="fas fa-user-tie"></i> Coach Updating</a></li>
                <li><a href="coach_appointmentview.php"><i class="fas fa-chalkboard-teacher"></i> Coach Appointments</a></li>
                <li><a href="promo_event.php"><i class="fas fa-bullhorn"></i> Updating Promo</a></li>
                <li class="active"><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations</a></li>
            </ul>
        </aside>

    <div class="admin-container">
        <h1>Coach Evaluations Management</h1>
        <?php display_message($message, $messageType); ?>

        <div class="button-toolbar">
            <a href="../backend/generate_coacheval.php" class="action-btn export-pdf-btn" target="_blank" title="Generate and download a PDF report">
                <i class="fas fa-file-pdf"></i> Download as PDF
            </a>
            
            <a href="gym_evalmanage.php" class="action-btn gym-eval-btn">
                <i class="fas fa-star"></i> Gym Evaluation
            </a>
            
            <button type="button" 
                class="action-btn delete-all-btn" 
                id="deleteAllBtn"
                title="Delete ALL Coach Evaluations">
                <i class="fas fa-times-circle"></i> Delete All
            </button>
        </div>
        
        <?php if (!empty($coach_evaluations)): ?>
            <table class="eval-table" id="evaluationTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">Eval ID</th>
                        <th onclick="sortTable(1)">Coach Name</th>
                        <th onclick="sortTable(2)">Evaluated By (Member)</th>
                        <th onclick="sortTable(3)">Behavior (1-5)</th>
                        <th onclick="sortTable(4)">Teaching (1-5)</th>
                        <th onclick="sortTable(5)">Communication (1-5)</th>
                        <th onclick="sortTable(6)">Opinion</th>
                        <th onclick="sortTable(7)">Date Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coach_evaluations as $eval): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($eval['evaluation_id']); ?></td>
                            <td><?php echo htmlspecialchars($eval['coach_name'] ?? 'N/A'); ?></td>
                            <td title="Member ID: <?php echo htmlspecialchars($eval['member_id']); ?>">
                                <?php echo htmlspecialchars($eval['member_name']); ?>
                            </td>
                            <td class="rating-cell"><?php echo htmlspecialchars($eval['behavior_rating']); ?></td>
                            <td class="rating-cell"><?php echo htmlspecialchars($eval['teaching_rating']); ?></td>
                            <td class="rating-cell"><?php echo htmlspecialchars($eval['communication_rating']); ?></td>
                            <td title="<?php echo htmlspecialchars($eval['opinion']); ?>">
                                <?php echo htmlspecialchars(substr($eval['opinion'], 0, 80)) . (strlen($eval['opinion']) > 80 ? '...' : ''); ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($eval['evaluation_date'])); ?></td>
                            
                            <td>
                                <form method="POST" action="coach_evalmanage.php" 
                                        onsubmit="return confirmDeletionCustom();" 
                                        style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($eval['evaluation_id']); ?>">
                                    <button type="submit" class="delete-btn action-btn" title="Delete Evaluation ID <?php echo htmlspecialchars($eval['evaluation_id']); ?>">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    </div> 

   <script>
        
    /**
     * @function confirmDeletionCustom
     * Placeholder to replace native confirm() for single delete. 
     */
    function confirmDeletionCustom() {
         // This is left as the simpler native confirm() as it aligns with the "warning and click ok" style.
         return confirm("Are you sure you want to delete this single evaluation?"); 
    }

    // --- AJAX Function for Delete All (SIMPLIFIED CONFIRMATION) ---
    /**
     * @async @function handleDeleteAllAjax
     * Handles the "Delete All" action using the Fetch API (AJAX).
     */
    async function handleDeleteAllAjax() {
        // 1. SIMPLE WARNING AND OK/CANCEL CONFIRMATION
        const confirmed = confirm("⚠️ WARNING: This will permanently delete ALL coach evaluations.\n\nClick OK to confirm deletion.");
        
        if (!confirmed) {
            displayMessage("Deletion canceled by user.", 'info');
            return; // Exit if the user clicks Cancel
        }
        
        // Show a waiting message while processing
        displayMessage("Processing deletion...", 'info');
        
        try {
            const formData = new FormData();
            formData.append('delete_all', 'true'); // The same POST data the PHP handler expects

            const response = await fetch('coach_evalmanage.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                // Success! Reload the page to display the updated table 
                // and the success/info message generated by the PHP script.
                window.location.reload(); 
            } else {
                // Handle HTTP errors
                displayMessage(`Network error: ${response.status} ${response.statusText}`, 'error');
            }

        } catch (error) {
            console.error('Fetch error:', error);
            displayMessage('A client-side error occurred during the delete operation.', 'error');
        }
    }

    // --- Helper for simple client-side message display (used by AJAX) ---
    function displayMessage(msg, type) {
        const container = document.querySelector('.admin-container');
        let messageBox = document.getElementById('messageBox');

        if (messageBox) {
            messageBox.remove(); // Remove existing box
        }
        
        if (!msg) return;

        const classMap = {
            'success': 'success',
            'info': 'info',
            'error': 'error'
        };
        const className = classMap[type] || 'info';

        messageBox = document.createElement('div');
        messageBox.id = 'messageBox';
        messageBox.className = `message-box ${className}`;
        messageBox.textContent = msg;
        messageBox.innerHTML += `<span class='close-message-btn' onclick='this.parentNode.style.display="none";'>&times;</span>`;

        // Insert after the main heading
        const h1 = container.querySelector('h1');
        if(h1) {
            h1.parentNode.insertBefore(messageBox, h1.nextSibling);
        } else {
             container.prepend(messageBox);
        }
    }

    // --- Event Listener to trigger AJAX on Delete All Button Click ---
    document.addEventListener('DOMContentLoaded', () => {
        const deleteAllBtn = document.getElementById('deleteAllBtn');
        if (deleteAllBtn) {
            deleteAllBtn.addEventListener('click', handleDeleteAllAjax);
        }
    });
    
    /**
     * Simple client-side table sorting function.
     * Toggles between ascending and descending sort for a given column.
     * (Unchanged from previous versions)
     */
    function sortTable(n) {
        let table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("evaluationTable");
        switching = true;
        
        // Get the current sort direction (if any) from a header attribute or default to asc
        let currentDir = table.getAttribute('data-sort-col') == n && table.getAttribute('data-sort-dir') == 'asc' ? 'desc' : 'asc';
        dir = currentDir;
        
        // Update table attributes for next click
        table.setAttribute('data-sort-col', n);
        table.setAttribute('data-sort-dir', dir);


        while (switching) {
            switching = false;
            rows = table.rows;
            
            // Loop through all table rows (except the first, which contains table headers):
            for (i = 1; i < (rows.length - 1); i++) {
                shouldSwitch = false;
                // Get the two elements you want to compare, one from current row and one from the next:
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                
                // Prioritize numeric comparison for rating columns (3, 4, 5) and ID column (0)
                let isNumericColumn = (n === 0 || (n >= 3 && n <= 5));
                
                let xContent = isNumericColumn ? parseFloat(x.innerHTML) : x.innerHTML.toLowerCase();
                let yContent = isNumericColumn ? parseFloat(y.innerHTML) : y.innerHTML.toLowerCase();

                // Fallback to string comparison if not a number
                if (isNumericColumn && (isNaN(xContent) || isNaN(yContent))) {
                    xContent = x.innerHTML.toLowerCase();
                    yContent = y.innerHTML.toLowerCase();
                    isNumericColumn = false; // Treat as string from now on
                }
                
                // Check if the two rows should switch place:
                if (dir == "asc") {
                    if (xContent > yContent) {
                        shouldSwitch = true;
                        break;
                    }
                } else if (dir == "desc") {
                    if (xContent < yContent) {
                        shouldSwitch = true;
                        break;
                    }
                }
            }
            
            if (shouldSwitch) {
                // If a switch has been marked, make the switch and mark that a switch has been done:
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                // Each time a switch is done, increase this count:
                switchcount++;
            } else {
                // If no switch has been done AND the direction is "asc", set the direction to "desc" and run the while loop again.
                if (switchcount == 0 && currentDir == "asc") {
                    dir = "desc";
                    switching = true;
                }
            }
        }
    }
</script>
</body>
</html>