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
// ---
// Fetch all subscriptions from the subscription table, joining for member and user names
$query = "
    SELECT
        s.subscription_id,
        s.members_id,
        s.subscription_type,
        s.gcash_reference_number,
        s.start_date,
        s.end_date,
        s.status,
        s.created_at,
        CONCAT(u.first_name, ' ', u.last_name) AS full_name
    FROM
        subscription s
    JOIN
        membership m ON s.members_id = m.members_id
    JOIN
        users u ON m.user_id = u.id
    ORDER BY s.created_at DESC
";
$result = $conn->query($query);
$subscriptions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Subscriptions - Admin</title>
<link rel="stylesheet" href="../assets/css/subscriptionview.css">
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
                <li class="active"><a href="subscriptionview.php"><i class="fas fa-sync-alt"></i> Subscription Management</a></li>
                <li><a href="pending_subscription.php"><i class="fas fa-hourglass-half"></i> Subscription Pending</a></li>
                <li><a href="coach_update.php"><i class="fas fa-user-tie"></i> Coach Updating</a></li>
                <li><a href="coach_appointmentview.php"><i class="fas fa-chalkboard-teacher"></i> Coach Appointments</a></li>
                <li><a href="promo_event.php"><i class="fas fa-bullhorn"></i> Updating Promo</a></li>
                <li><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations </a></li>
            </ul>
        </aside>


    <main class="main-content">
        <div class="main-content-header">
            <h1>All Subscriptions</h1>
        </div>
        <div class="table-header">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search by Member ID..." onkeyup="searchMember()">
                        <button onclick="searchMember()"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <a href="../backend/generate_subscription_report.php" class="btn-history-link pdf-btn" title="Download Subscription Report">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </a>
                    
                    <a href="deleted_subscription_view.php" class="btn-history-link" title="View archived subscriptions">
                        <i class="fas fa-trash"></i> Deletion History
                    </a>
                </div>


            <?php 
            if (isset($_GET['status']) && $_GET['status'] === 'archived_sub_success'): ?>
                <div class="alert success-message" style="margin-bottom: 20px; background-color: #d4edda; color: #155724; border-color: #c3e6cb;">
                    <i class="fas fa-check-circle"></i> Subscription successfully archived.
                </div>
            <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): 
                // This catches errors from process_subscription_archival.php
                $redirect_error = isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'An unknown database error occurred.';
            ?>
                <div class="alert error-message" style="margin-bottom: 20px; background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                    <i class="fas fa-exclamation-triangle"></i> Archival Failed: <?php echo $redirect_error; ?>
                </div>
            <?php endif; ?>

        <?php if (count($subscriptions) > 0): ?>
            <div class="content-card">
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Subscription ID</th>
                                <th>Member ID</th>
                                <th>Member Name</th>
                                <th>Plan</th>
                                <th>GCash Reference</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['subscription_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['members_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($row['subscription_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['gcash_reference_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                                    <td><?php echo $row['end_date'] ? htmlspecialchars($row['end_date']) : 'N/A'; ?></td>
                                    <td style="font-weight:bold; color:<?php echo ($row['status'] == 'active' ? 'green' : 'red'); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td>
                                        <div class="action-buttons-group">
                                            
                                            <form method="POST"
                                                action="../backend/process_subscription_archival.php"
                                                onsubmit="return confirm('Are you sure you want to archive this subscription? (This will move it to the history list)');">

                                                <input type="hidden" name="subscription_id" value="<?php echo htmlspecialchars($row['subscription_id']); ?>">

                                                <button type="submit" class="action-btn reject-btn" title="Archive">
                                                    <i class="fa-solid fa-box-archive"></i> Delete
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
            <div class="empty-state-container">
                <i class="fas fa-hourglass-half empty-state-icon" style="color: #ff6600;"></i>
                <h3 class="empty-state-title">No Subscriptions Found</h3>
                <p class="empty-state-text">
                    It looks like there are currently no subscriptions in the system.
                </p>
                <a href="membership_manage.php" class="btn-pending-link">
                    <i class="fas fa-users"></i> View Members
                </a>
            </div>
        <?php endif; ?>

    </main>
</div>

<script>
    // Profile dropdown
    document.querySelector('.profile-btn').addEventListener('click', function() {
        document.querySelector('.profile-dropdown').classList.toggle('show');
    });
    window.addEventListener('click', function(e) {
        const profileDropdown = document.querySelector('.profile-dropdown');
        if (!profileDropdown.contains(e.target) && profileDropdown.classList.contains('show')) {
            profileDropdown.classList.remove('show');
        }
    });

    // Client-side search function (searches by Member ID, which is in the second column/cell)
    function searchMember() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('table tbody tr');
        rows.forEach(row => {
            // Index 1 corresponds to the 'Member ID' column
            row.style.display = row.cells[1].textContent.toLowerCase().includes(input) ? '' : 'none';
        });
    }
</script>

</body>
</html>