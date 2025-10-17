<?php
session_start();
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');


// Include the database connection file
include '../backend/db.php'; 

// CRITICAL SECURITY CHECK: Ensure only logged-in admins can access this page
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}

$coaches = [];
$errorMessage = '';
$successMessage = '';

// Check for status messages from other scripts (e.g., delete)
if (isset($_SESSION['coach_status'])) {
    if ($_SESSION['coach_status']['type'] === 'success') {
        $successMessage = $_SESSION['coach_status']['message'];
    } else {
        $errorMessage = $_SESSION['coach_status']['message'];
    }
    // Clear the session message after displaying it
    unset($_SESSION['coach_status']);
}

// ------------------------------------------------------------------
// === 2. Fetch All Coach Data ===
// ------------------------------------------------------------------
// Query updated to match your current table structure (no 'picture_url')
$sql = "SELECT coach_id, name, gender, specialization FROM coaches ORDER BY coach_id ASC";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $coaches[] = $row;
        }
    } else {
        $errorMessage = "No coaches have been registered yet.";
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
    <title>Admin: Manage Coaches</title>
    <link rel="stylesheet" href="../assets/css/coach_list.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    

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
                <li class="active"><a href="coach_update.php"><i class="fas fa-user-tie"></i> Coach Updating</a></li>
                <li><a href="coach_appointmentview.php"><i class="fas fa-chalkboard-teacher"></i> Coach Appointments</a></li>
                <li><a href="promo_event.php"><i class="fas fa-bullhorn"></i> Updating Promo</a></li>
                <li><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="form-header-flex">
                <h2>Manage Coaches</h2>
                <a href="coach_update.php" class="btn-primary-link">
                    <i class="fas fa-plus"></i> Add New Coach
                </a>
            </div>

            <?php if ($successMessage): ?>
                <p class="status-message success"><?php echo htmlspecialchars($successMessage); ?></p>
            <?php endif; ?>
            <?php if ($errorMessage && empty($coaches)): ?>
                <p class="status-message error"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>

            <?php if (!empty($coaches)): ?>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Icon</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Specialization</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coaches as $coach): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($coach['coach_id']); ?></td>
                                    <td>
                                        <i class="fas fa-user-tie coach-icon"></i>
                                    </td>
                                    <td><?php echo htmlspecialchars($coach['name']); ?></td>
                                    <td><?php echo htmlspecialchars($coach['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($coach['specialization']); ?></td>
                                    <td>
                                        <a href="../backend/coach_process.php?action=delete&id=<?php echo htmlspecialchars($coach['coach_id']); ?>" 
                                            class="delete-btn"
                                            onclick="return confirm('WARNING: Are you sure you want to delete coach ID <?php echo $coach['coach_id']; ?>? This action cannot be undone.');">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-table-container" style="text-align: center; padding: 40px;">
                    <p style="color: #6c757d; font-size: 1.1em;">
                        <?php echo htmlspecialchars($errorMessage ?: "No coaches found."); ?>
                    </p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // JavaScript for the profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function () {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) {
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
            }
        });
    </script>
</body>
</html>