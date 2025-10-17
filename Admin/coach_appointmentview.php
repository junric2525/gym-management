<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

session_start();
// This script requires admin privileges to view and manage appointments.

// CRITICAL SECURITY CHECK
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}

// 1. Database Connection
if (file_exists('../backend/db.php')) {
    require_once '../backend/db.php'; 
    if (!isset($conn) || $conn->connect_error) {
        die("FATAL ERROR: Could not connect to the database: " . $conn->connect_error);
    }
} else {
    die("FATAL ERROR: db.php not found. Cannot connect to database.");
}

$appointments = [];
$errorMessage = '';
$successMessage = '';

// Check for status messages from the processing script (admin_process_appointment.php)
if (isset($_SESSION['appointment_status'])) {
    if ($_SESSION['appointment_status']['type'] === 'success') {
        $successMessage = $_SESSION['appointment_status']['message'];
    } else {
        $errorMessage = $_SESSION['appointment_status']['message'];
    }
    // Clear the session message after displaying it
    unset($_SESSION['appointment_status']);
}

// --- 2. Fetch All Appointment Data (Including NEW 'status' column) ---
$sql = "
    SELECT 
        a.appointment_id,
        a.members_id,
        a.member_name,       /* Name saved during booking */
        a.appointment_datetime,
        c.name AS coach_name,
        c.specialization,
        a.booked_at,
        a.status             /* NEW: Added status from the appointments table */
    FROM appointments a
    JOIN coaches c ON a.coach_id = c.coach_id
    ORDER BY a.appointment_datetime DESC
";

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    } else {
        $errorMessage = "No coaching appointments have been booked yet."; 
    }
    $result->close();
} else {
    $errorMessage = "Database query error: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: View Appointments</title>
    <link rel="stylesheet" href="../assets/css/coach_appointmentview.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    
</head>
<body>
    <header class="header">
        <div class="header-content-wrapper header-flex">
            <div class="logo">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <div class="profile-dropdown" id="profileDropdown">
                <button class="profile-btn">
                    <i class="fas fa-user"></i> <i class="fas fa-caret-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="Admin.php"><i class="fas fa-user"></i> Home</a> 
                    <a href="../Guest/index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                <li class="active"><a href="coach_appointmentview.php"><i class="fas fa-chalkboard-teacher"></i> Coach Appointments</a></li>
                <li><a href="promo_event.php"><i class="fas fa-bullhorn"></i> Updating Promo</a></li>
                <li><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations</a></li>
            </ul>
        </aside>

    <main class="container main-content">
        <h2>Scheduled Coaching Appointments</h2>
        
        <?php if ($successMessage): ?>
            <p class="status-message success"><?php echo htmlspecialchars($successMessage); ?></p>
        <?php endif; ?>

        <?php if (!empty($appointments)): ?>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Member ID</th>
                            <th>Member Name</th>
                            <th>Date & Time</th>
                            <th>Coach</th>
                            <th>Specialization</th>
                            <th>Booked On</th>
                            <th>Status</th> <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $app): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['appointment_id']); ?></td>
                            <td><?php echo htmlspecialchars($app['members_id']); ?></td>
                            <td><?php echo htmlspecialchars($app['member_name']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y @ h:i A', strtotime($app['appointment_datetime']))); ?></td>
                            <td><?php echo htmlspecialchars($app['coach_name']); ?></td>
                            <td><?php echo htmlspecialchars($app['specialization']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($app['booked_at']))); ?></td>
                            
                            <td><?php echo htmlspecialchars($app['status'] ?? 'Pending'); ?></td>

                                     <td class="action-cell">
                                            <?php 
                                            // Show Accept/Cancel buttons only if status is Pending
                                            if (($app['status'] ?? 'Pending') === 'Pending'): 
                                            ?>
                                            <a href="../backend/coach_appointment_process.php?action=accept&id=<?php echo htmlspecialchars($app['appointment_id']); ?>" 
                                                class="accept-btn"
                                                onclick="return confirm('Confirm: ACCEPT appointment ID <?php echo $app['appointment_id']; ?>?');">
                                                <i class="fas fa-check"></i> Accept
                                            </a>
                                            <a href="../backend/coach_appointment_process.php?action=cancel&id=<?php echo htmlspecialchars($app['appointment_id']); ?>" 
                                                class="cancel-btn"
                                                onclick="return confirm('Confirm: CANCEL appointment ID <?php echo $app['appointment_id']; ?>?');">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                            <?php else: ?>
                                                <span class="processed-message"><?php echo htmlspecialchars($app['status']); ?></span>
                                            <?php endif; ?>

                                            <a href="../backend/coach_appointment_process.php?action=delete&id=<?php echo htmlspecialchars($app['appointment_id']); ?>" 
                                                class="delete-btn" 
                                                onclick="return confirm('WARNING! This will permanently DELETE appointment ID <?php echo $app['appointment_id']; ?>. Are you sure?');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state-container">
                <i class="fas fa-calendar-times empty-state-icon"></i>
                <h3 class="empty-state-title">No Appointments Scheduled</h3>
                <p class="empty-state-text">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </p>
                <a href="Admin.php" class="btn-pending-link">
                    <i class="fas fa-home"></i> Go to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </main>
    </div>

    <script>
        // JavaScript for the profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function () {
            const dropdown = document.getElementById('profileDropdown');
            const button = dropdown.querySelector('.profile-btn');

            button.addEventListener('click', function () {
                dropdown.classList.toggle('show');
            });

            // Close the dropdown if the user clicks outside of it
            window.addEventListener('click', function (e) {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>