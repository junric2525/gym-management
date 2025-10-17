<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');


session_start();
include '../backend/db.php';

// CRITICAL SECURITY CHECK
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}
// Initialize variables
$db_error = null;
$fetched_rows = [];
$num_rows = 0;

/**
 * Calculates the current age based on the date of birth string.
 * @param string|null $birthDate The date of birth (e.g., '1990-01-15').
 * @return int|string The calculated age or 'N/A'.
 */
function calculateAge($birthDate) {
    if (!$birthDate || $birthDate === '0000-00-00') {
        return 'N/A';
    }
    try {
        $dob = new DateTime($birthDate);
        $now = new DateTime();
        $interval = $now->diff($dob);
        return $interval->y;
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}


// --- 1. Database Query with JOIN ---
$query = "
    SELECT 
        mt.*, 
        u.email, 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name 
    FROM 
        membership_temp mt
    JOIN 
        users u ON mt.user_id = u.id 
    ORDER BY
        mt.created_at DESC
";

// We need a fresh connection since the previous one might have been closed.
// Re-include db.php if it initializes $conn, or ensure $conn is available.
if (!isset($conn) || !$conn) {
    // Attempt to re-establish connection if needed, otherwise this assumes 
    // db.php initializes $conn and we just closed it earlier.
    // If db.php is short, it's safer to re-include it.
    // For this example, we assume $conn is still accessible or re-initialized at the top.
}

$result = $conn->query($query);

// Check if the query was successful
if (!$result) {
    $db_error = "Database Query Failed: " . $conn->error;
} else {
    $num_rows = $result->num_rows; 
    $fetched_rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

// Close connection early
if (isset($conn) && $conn) {
    $conn->close();
}
// --- End of PHP Query Block ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Pending</title>
    <link rel="stylesheet" href="../assets/css/paymentpending.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
    <style>
        
        /* Style for action buttons */
        .action-buttons button {
            cursor: pointer;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .approve-btn {
            background-color: #28a745;
            color: white;
        }
        .approve-btn:hover {
            background-color: #218838;
        }
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        .reject-btn:hover {
            background-color: #c82333;
        }
        /* Table Styles (assuming paymentpending.css defines main table styles) */
        .table-responsive table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
        }
        .table-responsive th, .table-responsive td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-flex">
            <div class="logo">
               <img src="../assets/img/logo.png" alt="Logo" class="logo-img" onerror="this.onerror=null; this.src='https://placehold.co/40x40/ff6600/ffffff?text=CG';" />
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <div class="profile-dropdown">
                <button class="profile-btn">
                    <i class="fas fa-user"></i> <i class="fas fa-caret-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="Admin.php"><i class="fas fa-home"></i> Home</a> 
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
                <li class="active"><a href="payment_pendingview.php"><i class="fas fa-hourglass-half"></i> Membership Pending</a></li>
                <li><a href="subscriptionview.php"><i class="fas fa-sync-alt"></i> Subscription Management</a></li>
                <li><a href="pending_subscription.php"><i class="fas fa-hourglass-half"></i> Subscription Pending</a></li>
                <li><a href="coach_update.php"><i class="fas fa-user-tie"></i> Coach Updating</a></li>
                <li><a href="coach_appointmentview.php"><i class="fas fa-chalkboard-teacher"></i> Coach Appointments</a></li>
                <li><a href="promo_event.php"><i class="fas fa-bullhorn"></i> Updating Promo</a></li>
                <li><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations </a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-content-header">
                <h1>Pending Membership Applications</h1>
            </div>

            <?php 
            // --- Message Display: Show alerts/errors outside the main content card ---
            if ($db_error): ?>
                <div class="alert error-message" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> **SQL Error (Page Load):** <?php echo htmlspecialchars($db_error); ?>
                </div>
            <?php elseif (isset($_GET['error'])): 
                $redirect_error = isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'An unknown error occurred during an action.';
                $error_class = ($_GET['error'] === 'duplicate_gcash') ? 'error-message' : 'error-message';
                $error_icon = ($_GET['error'] === 'no_id' || $_GET['error'] === 'duplicate_gcash') ? 'fas fa-times-circle' : 'fas fa-exclamation-triangle';
            ?>
                <div class="alert <?php echo $error_class; ?>" style="margin-bottom: 20px;">
                    <i class="<?php echo $error_icon; ?>"></i> **Error:** <?php echo $redirect_error; ?>
                </div>
            <?php elseif (isset($_GET['success'])): 
                $success_message = ($_GET['success'] === 'approved') ? 'Member approved successfully and invoice created.' : 'Application rejected and removed.';
                $success_icon = ($_GET['success'] === 'approved') ? 'fas fa-check-circle' : 'fas fa-trash-alt';
                $success_class = ($_GET['success'] === 'approved') ? 'success-message' : 'error-message'; // Using error-message style for rejected status for clarity
            ?>
                <div class="alert <?php echo $success_class; ?>" style="margin-bottom: 20px;">
                    <i class="<?php echo $success_icon; ?>"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($num_rows > 0): ?>
                <div class="content-card"> 
                    <div class="table-header">
                        <div class="search-bar">
                            <input type="text" id="searchInput" placeholder="Search by ID or Name..." onkeyup="searchMember()">
                            <button onclick="searchMember()"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                
                    <div class="table-responsive">
                        <table id="membershipTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Full Name</th> 
                                    <th>Email</th> 
                                    <th>Birth Date</th> <th>Age</th> 
                                    <th>Gender</th>
                                    <th>Contact</th>
                                    <th>Emergency Contact</th>
                                    <th>Emergency Number</th>
                                    <th>Emergency Relation</th>
                                    <th>Medical Condition</th>
                                    <th>Medical Details</th>
                                    <th>Medication</th>
                                    <th>Medication Details</th>
                                    <th>Gcash Reference</th>
                                    <th>Valid ID</th>
                                    <th>Status</th>
                                    <th>Submitted At</th>
                                    <th>Expiration Date (Initial)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fetched_rows as $row): 
                                    $age = calculateAge($row['birth_date']); 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['members_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>  
                                    <td><?php echo htmlspecialchars($row['email']); ?></td> 
                                    <td><?php echo htmlspecialchars($row['birth_date'] ?? 'N/A'); ?></td> 
                                    <td><?php echo htmlspecialchars($age); ?></td> 
                                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                    <td><?php echo htmlspecialchars($row['emergency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['emergency_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['emergency_relation']); ?></td>
                                    <td><?php echo htmlspecialchars($row['medical_conditions']); ?></td>
                                    <td><?php echo htmlspecialchars($row['medical_details']); ?></td>
                                    <td><?php echo htmlspecialchars($row['medications']); ?></td>
                                    <td><?php echo htmlspecialchars($row['medications_details']); ?></td>
                                    <td><?php echo htmlspecialchars($row['gcash_reference']); ?></td>
                                    <td>
                                        <a href="../backend/<?php echo htmlspecialchars($row['validid_path']); ?>" target="_blank" class="view-link">
                                            <i class="fas fa-image"></i> View
                                        </a>
                                    </td>
                                    <td><span class="status-badge pending"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($row['expiration_date'] ?? 'N/A'); ?></td>
                                    
                                    <td> 
                                        <div class='action-buttons'>
                                            <form method='POST' action='../backend/membership_action.php' style="display: inline-block;" onsubmit="return confirm('Are you sure you want to APPROVE this membership? This will grant gym access.');">
                                                <input type='hidden' name='action' value='approve'>
                                                <input type='hidden' name='temp_id' value='<?php echo htmlspecialchars($row['members_id']); ?>'>
                                                <button type='submit' class="action-btn approve-btn" title="Approve">
                                                    <i class="fas fa-check"></i> Accept
                                                </button>
                                            </form>

                                            <form method='POST' action='../backend/membership_action.php' style="display: inline-block; margin-left: 5px;" onsubmit="return confirm('Are you sure you want to REJECT this membership? This action cannot be undone.');">
                                                <input type='hidden' name='action' value='reject'>
                                                <input type='hidden' name='temp_id' value='<?php echo htmlspecialchars($row['members_id']); ?>'>
                                                <button type='submit' class="action-btn reject-btn" title="Reject">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
            <?php elseif (!$db_error): ?>
                <div class="empty-state-container">
                    <i class="fas fa-hourglass-half empty-state-icon" style="color: var(--primary-color); font-size: 5rem; margin-bottom: 15px;"></i>
                    <h3 class="empty-state-title">No Pending Applications Found</h3>
                    <p class="empty-state-text">
                        It looks like all membership payments have been processed, or the pending list is currently empty.
                    </p>
                    <?php if (!isset($_GET['error'])): ?>
                        <a href="membership_manage.php" class="btn-pending-link">
                            <i class="fas fa-users"></i> View Approved Members
                        </a>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </main>
    </div>
    
    <div id="confirmationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Confirm Action</h3>
            <p id="modalText"></p>
            <div class="modal-buttons">
                <button id="modalConfirmBtn" class="modal-btn modal-btn-confirm">Yes, Proceed</button>
                <button id="modalCancelBtn" class="modal-btn modal-btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Profile dropdown functionality
        document.querySelector('.profile-btn').addEventListener('click', function() {
            // Using a class toggle for better styling control
            this.closest('.profile-dropdown').classList.toggle('show');
        });
        window.addEventListener('click', function(e) {
            const profileDropdown = document.querySelector('.profile-dropdown');
            // If the click is outside the dropdown area
            if (profileDropdown && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });


        // Simple client-side search function (Searches by Member ID and Full Name)
        function searchMember() {
            var input, filter, table, tr, tdId, tdName, i, txtValueId, txtValueName;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("membershipTable"); // Added ID for clarity
            if (!table) return; 

            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) { // Start at 1 to skip the header row
                // Get the cell containing Member ID (index 0) and Full Name (index 2)
                tdId = tr[i].getElementsByTagName("td")[0];
                tdName = tr[i].getElementsByTagName("td")[2]; 
                
                if (tdId && tdName) {
                    txtValueId = tdId.textContent || tdId.innerText;
                    txtValueName = tdName.textContent || tdName.innerText;

                    if (txtValueId.toUpperCase().indexOf(filter) > -1 || txtValueName.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }
    </script>
    </body>
</html>