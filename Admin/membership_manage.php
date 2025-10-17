<?php
session_start();
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');


include '../backend/db.php'; 

// CRITICAL SECURITY CHECK (Keep this)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}
// SQL Query to fetch first_name, last_name, email, and CRITICALLY the birth_date from the 'users' table
$sql = "
    SELECT 
        m.*, 
        u.first_name, 
        u.last_name,
        u.email,
        m.birth_date    -- Fetching birth_date from the membership table (m)
    FROM 
        membership m
    JOIN 
        users u ON m.user_id = u.id 
    ORDER BY 
        m.approved_at DESC"; 
$result = $conn->query($sql);

$members = [];
if ($result) {
    $members = $result->fetch_all(MYSQLI_ASSOC);
}
// Close database connection
$conn->close();

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
        // Handle invalid date format if necessary
        return 'Invalid Date';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Management - Admin</title>
    <link rel="stylesheet" href="../assets/css/membership_manage.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                <li class="active"><a href="membership_manage.php"><i class="fas fa-users"></i> Membership Management</a></li>
                <li><a href="pending_renewal.php"><i class="fas fa-hand-holding-usd"></i> Renewal Pending</a></li>
                <li><a href="payment_pendingview.php"><i class="fas fa-hourglass-half"></i> Membership Pending</a></li>
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
                <h1>Approved Members</h1>
            </div>
            <div class="table-header">
                
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search by Member ID or Name..." onkeyup="searchMember()">
                    <button onclick="searchMember()"><i class="fas fa-search"></i></button>
                </div>

                <a href="../backend/generate_member_report.php" class="btn-history-link pdf-btn" title="Download Approved Members Report">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
                
                <a href="deleted_members_view.php" class="btn-history-link" title="View archived members">
                    <i class="fas fa-trash"></i> Deletion History
                </a>
                </div>
            
            <?php 
            if (isset($_GET['status']) && $_GET['status'] === 'archived_member_success'): ?>
                <div class="alert success-message" style="margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> Member successfully archived/deleted.
                </div>
            <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): 
                $redirect_error = isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'An unknown database error occurred.';
            ?>
                <div class="alert error-message" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> Archival Failed: <?php echo $redirect_error; ?>
                </div>
            <?php endif; ?>


            <?php if (count($members) > 0): ?>
                <div class="content-card">
                    <div class='table-responsive'>
                        <table>
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
                                    <th>Approved at</th>
                                    <th>Expiration Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $row): 
                                    // Calculate age for display
                                    // Using the correct database column name: 'birth_date'
                                    $age = calculateAge($row['birth_date']);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['members_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars("{$row['first_name']} {$row['last_name']}"); ?></td> 
                                    <td><?php echo htmlspecialchars($row['email']); ?></td> 
                                    <td><?php echo htmlspecialchars($row['birth_date']); ?></td> 
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
                                    <td><?php echo htmlspecialchars($row['approved_at']); ?></td>
                                    <td><?php echo htmlspecialchars($row['expiration_date']); ?></td>
                                    <td>
                                        <div class='action-buttons-group'>
                                            <form method='POST' 
                                                action='../backend/process_member_archival.php' 
                                                onsubmit="return confirm('Are you sure you want to archive this member? (This will move it to the history list and effectively delete it from this view)');">
                                                <input type='hidden' name='members_id' value='<?php echo htmlspecialchars($row['members_id']); ?>'> 
                                                <button type='submit' name='archive' class='action-btn reject-btn' title="Archive">
                                                    <i class='fa-solid fa-trash'></i> Delete
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
            <?php else: ?>
                <div class='empty-state-container'>
                    <i class='fas fa-user-slash empty-state-icon'></i>
                    <h3 class='empty-state-title'>No Approved Members Found</h3>
                    <p class='empty-state-text'>It looks like no membership applications have been approved yet, or the database is currently empty.</p>
                    <a href='payment_pendingview.php' class='btn-pending-link'><i class='fas fa-clipboard-list'></i> Check Pending Applications</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Profile dropdown functionality
        document.querySelector('.profile-btn').addEventListener('click', function() {
            document.querySelector('.profile-dropdown').classList.toggle('show');
        });
        window.addEventListener('click', function(e) {
            const profileDropdown = document.querySelector('.profile-dropdown');
            if (!profileDropdown.contains(e.target) && profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
            }
        });

        // Simple client-side search function (Searches by Member ID (0) and Full Name (2))
        function searchMember() {
            var input, filter, table, tr, tdId, tdName, i, txtValueId, txtValueName;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.querySelector("table");
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