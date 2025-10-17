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
        .btn-edit { background-color: #2196F3; } /* Blue */
        .btn-delete { background-color: #f44336; } /* Red */
        .btn-timeout { background-color: #4CAF50; } /* Green */

        /* Modal styling (optional, but good for user interaction) */
        .modal {
            display: none; 
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4); 
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close-btn:hover,
        .close-btn:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
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
                    <a href="../Guest/index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                <a href="../backend/generate_attendance_pdf.php" target="_blank" class="btn-pdf">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
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
                                // Use the PHP function to prepare the date strings for the modal
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
                                    <button class="btn-edit" onclick="openEditModal(<?= $row['log_id'] ?>, '<?= $time_in_html ?>', '<?= $time_out_html ?>')"><i class="fas fa-edit"></i> Edit</button>
                                    
                                    <?php if (empty($row['time_out'])): // Show Time Out button only if time_out is NULL ?>
                                        <button class="btn-timeout" onclick="manualTimeOut(<?= $row['log_id'] ?>)"><i class="fas fa-clock"></i> Time Out</button>
                                    <?php endif; ?>
                                    
                                    <button class="btn-delete" onclick="deleteLog(<?= $row['log_id'] ?>)"><i class="fas fa-trash"></i> Delete</button>
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

    <div id="logModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Edit Log Entry</h2>
            <form id="logForm">
                <input type="hidden" id="modal_log_id" name="log_id">
                
                <label for="time_in">Time In:</label>
                <input type="datetime-local" id="modal_time_in" name="time_in" required><br><br>
                
                <label for="time_out">Time Out:</label>
                <input type="datetime-local" id="modal_time_out" name="time_out"><br><br>
                
                <button type="submit" class="btn-edit">Save Changes</button>
            </form>
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

            // Modal save/submit logic
            document.getElementById('logForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const logId = document.getElementById('modal_log_id').value;
                const timeIn = document.getElementById('modal_time_in').value;
                const timeOut = document.getElementById('modal_time_out').value;

                // Simple AJAX call 
                fetch('../backend/edit_log.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        log_id: logId,
                        time_in: timeIn,
                        time_out: timeOut 
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Log updated successfully!');
                        closeModal();
                        // Reloading the page ensures the new IN/OUT status is checked correctly
                        window.location.reload(); 
                    } else {
                        alert('Error updating log: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during the update.');
                });
            });
        });

        const logModal = document.getElementById('logModal');

        function closeModal() {
            logModal.style.display = 'none';
        }

        function openEditModal(logId, timeInHtml, timeOutHtml) {
            document.getElementById('modal_log_id').value = logId;
            document.getElementById('modal_time_in').value = timeInHtml;
            document.getElementById('modal_time_out').value = timeOutHtml;
            logModal.style.display = 'block';
        }

        function deleteLog(logId) {
            if (confirm(`Are you sure you want to permanently delete log ID ${logId}? This action cannot be undone.`)) {
                // AJAX call to delete the log 
                fetch('../backend/delete_log.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ log_id: logId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Log ID ${logId} deleted successfully!`);
                        document.getElementById(`log-row-${logId}`).remove();
                    } else {
                        alert('Error deleting log: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during the deletion.');
                });
            }
        }

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
                            // The Status cell is the one immediately before the Actions cell (assuming standard layout)
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
    </script>

</body>
</html>