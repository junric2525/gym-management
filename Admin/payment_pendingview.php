<?php
// Ensure the database connection is established and the session is started before any HTML output
session_start();
// NOTE: Assuming db.php is in the backend directory
include '../backend/db.php'; 

// Check for Admin Session (BEST PRACTICE: Should be in all Admin pages)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // If not authorized, redirect to the Guest Index/Login page immediately.
    header("Location: ../Guest/Index.html"); 
    exit(); 
}

// Initialize error variable for page load query
$db_error = null;
$fetched_rows = [];
$num_rows = 0;

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

$result = $conn->query($query);

// Check if the query was successful
if (!$result) {
    // Capture the exact database error message instead of using die()
    $db_error = "Database Query Failed: " . $conn->error;
} else {
    // Store the number of rows and fetch data if successful
    $num_rows = $result->num_rows; 
    $fetched_rows = $result->fetch_all(MYSQLI_ASSOC);
}

// --- End of PHP Query Block ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Pending</title>
    <link rel="stylesheet" href="../assets/css/paymentpending.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
</head>
<body>
    <header class="header">
        <div class="header-flex">
            <div class="logo">
               <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <div class="profile-dropdown">
                <button class="profile-btn">
                    <i class="fas fa-user"></i> <i class="fas fa-caret-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="Admin.php"><i class="fas fa-home"></i> Home</a> 
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        
        <aside class="sidebar">
            <ul>
                <li><a href="Attendance.html"><i class="fas fa-user-check"></i> Attendance Monitoring</a></li>
                <li><a href="membership_manage.php"><i class="fas fa-users"></i> Membership Management</a></li>
                <li class="active"><a href="payment_pendingview.php"><i class="fas fa-hand-holding-usd"></i> Membership Pending</a></li>
                <li><a href="subscriptionview.php"><i class="fas fa-sync-alt"></i> Subscription Management</a></li>
                <li><a href="pending_subscription.php"><i class="fas fa-hourglass-half"></i> Subscription Pending</a></li>
                <li><a href="Staff.html"><i class="fas fa-user-tie"></i> Coach Updating</a></li>
                <li><a href="UpdatingPromo.html"><i class="fas fa-bullhorn"></i> Updating Event/Promo</a></li>
                <li><a href="PerformanceAnalytics.html"><i class="fas fa-chart-line"></i> Performance Analytics</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-content-header">
                <h1>Pending Membership Applications</h1>
            </div>

            <?php 
            // --- Message Display: Show alerts/errors outside the main content card ---
            // The fatal DB error should always be shown if it exists.
            if ($db_error): ?>
                <div class="alert error-message" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> **SQL Error (Page Load):** <?php echo htmlspecialchars($db_error); ?>
                </div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'no_id'): ?>
                <div class="alert error-message" style="margin-bottom: 20px;">
                    <i class="fas fa-times-circle"></i> **Processing Error:** The necessary Member ID was not passed.
                </div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'db_error'): 
                $redirect_error = isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'An unknown database error occurred during an action.';
            ?>
                <div class="alert error-message" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> **SQL Error (Action):** <?php echo $redirect_error; ?>
                </div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'duplicate_gcash'): ?>
                <div class="alert error-message" style="margin-bottom: 20px;">
                    <i class="fas fa-times-circle"></i> This GCash reference number is already used.
                </div>
            <?php elseif (isset($_GET['success']) && $_GET['success'] === 'approved'): ?>
                <div class="alert success-message" style="margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> Member approved successfully.
                </div>
            <?php elseif (isset($_GET['success']) && $_GET['success'] === 'rejected'): ?>
                <div class="alert success-message" style="margin-bottom: 20px;">
                    <i class="fas fa-trash-alt"></i> Application rejected and removed.
                </div>
            <?php endif; ?>

            <?php if ($num_rows > 0): ?>
                <div class="content-card"> 
                    <div class="table-header">
                        <div class="search-bar">
                            <input type="text" id="searchInput" placeholder="Search by Member ID..." onkeyup="searchMember()">
                            <button onclick="searchMember()"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Full Name</th>  
                                    <th>Email</th> 
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
                                    <th>Expiration Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fetched_rows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['members_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>  
                                    <td><?php echo htmlspecialchars($row['email']); ?></td> 
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
                                        <a href="<?php echo htmlspecialchars($row['validid_path']); ?>" target="_blank" class="view-link">
                                            <i class="fas fa-image"></i> View
                                        </a>
                                    </td>
                                    <td><span class="status-badge pending"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($row['expiration_date']); ?></td>
                                    <td> 
                                        
                                        <div class='action-buttons'>
                                            <form method='POST' action='../backend/membership_action.php'>
                                                <input type='hidden' name='action' value='approve'>
                                                <input type='hidden' name='temp_id' value='<?php echo htmlspecialchars($row['members_id']); ?>'>
                                                <button type='submit' class="btn-confirm action-btn approve-btn" onclick="return confirm('Confirm approval for <?php echo htmlspecialchars($row['full_name']); ?>?');" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>

                                            <form method='POST' action='../backend/membership_action.php'>
                                                <input type='hidden' name='action' value='reject'>
                                                <input type='hidden' name='temp_id' value='<?php echo htmlspecialchars($row['members_id']); ?>'>
                                                <button type='submit' class="btn-reject action-btn reject-btn" onclick="return confirm('Reject application from <?php echo htmlspecialchars($row['full_name']); ?>? This action cannot be undone.');" title="Reject">
                                                    <i class="fas fa-times"></i>
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
                    <i class="fas fa-hourglass-half empty-state-icon" style="color: #ff6600; font-size: 5rem; margin-bottom: 15px;"></i>
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

    <script src="../assets/js/payment_pending.js" defer></script>
    
</body>
</html>