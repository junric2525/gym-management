<?php
session_start();
include '../backend/db.php';

// --- PHP FUNCTION TO FORCE MACHINE-READABLE DATETIME FORMAT ---
// Keeping this function here, as it was the fix for the previous date validation error.
function format_to_html_datetime($mysql_datetime) {
    if (!$mysql_datetime || $mysql_datetime === '-') {
        return '';
    }
    
    $timestamp = strtotime($mysql_datetime);

    if ($timestamp === false) {
        return ''; 
    }

    // Format the timestamp to the exact HTML datetime-local required format: YYYY-MM-DDTHH:MM
    return date('Y-m-d\TH:i', $timestamp);
}
// -------------------------------------------------------------------

// Protect admin route
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}

// Fetch all attendance logs
$result = $conn->query("
    SELECT a.log_id, m.members_id, m.gender, m.address, a.time_in, a.time_out, a.scan_type
    FROM attendance_logs a
    JOIN membership m ON a.members_id = m.members_id
    ORDER BY a.time_in DESC
"); // <<< MODIFICATION: Changed m.user_id to m.members_id

// Check if there are results for the main table
$has_logs = ($result->num_rows > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Logs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/attendance_history.css">
    <style>
        /* New CSS for Action Buttons */
        .action-btns button {
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 0.8em;
        }
        .btn-edit { background-color: #2196F3; } /* Blue (Kept for CSS consistency) */
        .btn-delete { background-color: #f44336; } /* Red (Kept for CSS consistency) */
        .btn-timeout { background-color: #4CAF50; } /* Green */
        
        /* New CSS for Clear All button */
        .btn-clear-all { 
            background-color: #FF9800; /* Orange/Amber */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-clear-all:hover {
            background-color: #e68900;
        }

        /* Ensure header children align correctly */
        .main-content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
        }
    </style>
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
                <li class="active"><a href="Attendance_history.php"><i class="fas fa-user-check"></i> Attendance Logs</a></li>
                <li><a href="membership_manage.php"><i class="fas fa-users"></i> Membership Management</a></li>
                <li><a href="pending_renewal.php"><i class="fas fa-hand-holding-usd"></i> Renewal Pending</a></li>
                <li><a href="payment_pendingview.php"><i class="fas fa-hourglass-half"></i> Membership Pending</a></li>
                <li><a href="subscriptionview.php"><i class="fas fa-sync-alt"></i> Subscription Management</a></li>
                <li><a href="pending_subscription.php"><i class="fas fa-hourglass-half"></i> Subscription Pending</a></li>
                <li><a href="coach_update.php"><i class="fas fa-user-tie"></i> Coach Updating</a></li>
                <li><a href="coach_appointmentview.php"><i class="fas fa-chalkboard-teacher"></i> Coach Appointments</a></li>
                <li><a href="promo_event.php"><i class="fas fa-bullhorn"></i> Updating Promo</a></li>
                <li><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations</a></li>
            </ul>
        </aside>
        
        <div class="main-content"> 
            <div class="main-content-header">
                <h1>Attendance Logs</h1>
                <div class="header-actions">
                    <a href="../backend/generate_attendance_pdf.php" target="_blank" class="btn-pdf">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </a>
                    <button class="btn-clear-all" onclick="clearAllLogsConfirmation()">
                        <i class="fas fa-broom"></i> Clear All
                    </button>
                    </div>
            </div>
        
            <?php if ($has_logs): ?>
            <div class="content-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Member ID</th> <th>Gender</th>
                                <th>Address</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                                <th>Actions</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <?php
                                $time_in_html = format_to_html_datetime($row['time_in']); 
                                $time_out_html = format_to_html_datetime($row['time_out']);
                            ?>
                            <tr id="log-row-<?= $row['log_id'] ?>">
                                <td><?= $row['log_id'] ?></td>
                                <td><?= htmlspecialchars($row['members_id']) ?></td> <td><?= htmlspecialchars($row['gender']) ?></td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><?= $row['time_in'] ?></td>
                                <td class="time-out-cell"><?= $row['time_out'] ?: '-' ?></td>
                                
                                <td>
                                    <?php 
                                    // Status should be OUT if a time_out timestamp exists.
                                    if (!empty($row['time_out'])) {
                                        echo 'OUT'; 
                                    } else {
                                        echo 'IN'; 
                                    }
                                    ?>
                                </td>
                                <td class="action-btns">
                                    <?php if (empty($row['time_out'])): // Show Time Out button only if time_out is NULL ?>
                                        <button class="btn-timeout" onclick="manualTimeOut(<?= $row['log_id'] ?>)"><i class="fas fa-clock"></i> Time Out</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state-container">
                <i class="fas fa-calendar-times empty-state-icon"></i>
                <h3 class="empty-state-title">No Attendance Records Found</h3>
                <p class="empty-state-text">
                    No check-in or check-out logs are currently recorded in the database.
                </p>
                <a href="Admin.php" class="btn-pending-link">
                    <i class="fas fa-home"></i> Go to Dashboard
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Profile dropdown logic
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileDropdown) {
                profileDropdown.querySelector('.profile-btn').addEventListener('click', function() {
                    profileDropdown.classList.toggle('show');
                });
            }
            window.addEventListener('click', function(e) {
                if (profileDropdown && !profileDropdown.contains(e.target)) {
                    profileDropdown.classList.remove('show');
                }
            });
        });

        function manualTimeOut(logId) {
            if (confirm(`Are you sure you want to manually set the Time Out for Log ID ${logId} to the current time?`)) {
                // AJAX call to set Time Out 
                fetch('../backend/manual_timeout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ log_id: logId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Log ID ${logId} manually timed out successfully!`);
                        
                        const row = document.getElementById(`log-row-${logId}`);
                        if(row) {
                            // 1. Update the Time Out column with the new time
                            row.querySelector('.time-out-cell').textContent = data.time_out;
                            
                            // 2. Update the Status column to 'OUT'
                            const statusCell = row.querySelector('.time-out-cell').nextElementSibling;
                            if (statusCell) {
                                statusCell.textContent = 'OUT';
                            }

                            // 3. Remove the Time Out button
                            const actionCell = row.querySelector('.action-btns');
                            const timeoutBtn = actionCell.querySelector('.btn-timeout');
                            if(timeoutBtn) timeoutBtn.remove();
                        }
                    } else {
                        alert('Error processing manual Time Out: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during the Time Out process.');
                });
            }
        }
        
        // =======================================================
        // NEW JAVASCRIPT FUNCTIONS FOR CLEAR ALL LOGS
        // =======================================================

        function clearAllLogsConfirmation() {
            const confirmed = confirm(
                "⚠️ WARNING: CLEARING ALL LOGS IS IRREVERSIBLE.\n\n" +
                "Have you already downloaded the current attendance log data as a PDF?\n\n" +
                "Press 'OK' to proceed with clearing all logs.\n" +
                "Press 'Cancel' to stop and download the PDF first."
            );

            if (confirmed) {
                // If the admin confirms, proceed to the actual clear function
                clearAllLogs();
            } else {
                alert("Action cancelled. Please remember to download the PDF before clearing logs.");
            }
        }

        function clearAllLogs() {
            // A final confirmation for safety
            if (!confirm("FINAL CONFIRMATION: Are you absolutely sure you want to DELETE ALL ATTENDANCE LOGS?")) {
                return;
            }

            // AJAX call to a new backend script (e.g., clear_all_logs.php)
            fetch('../backend/clear_all_logs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ All attendance logs have been successfully cleared!');
                    // Reload the page to show the empty state or the cleared table
                    window.location.reload(); 
                } else {
                    alert('❌ Error clearing logs: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during the clear process.');
            });
        }
        // =======================================================
        
    </script>

</body>
</html>