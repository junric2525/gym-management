<?php
// FILE: Admin/deleted_subscriptions_view.php

require_once '../backend/db.php';
session_start();

// ✅ Security check (Ensure admin is logged in)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/Index.html");
    exit();
}

// --- 1. PHP Query to Fetch Deleted Subscriptions ---
// Joins: deleted_subscriptions -> membership -> users (for member name)
// LEFT JOIN: users (for admin name, in case admin account is deleted)
$sql = "
    SELECT
        ds.*,
        u.first_name AS member_first_name,
        u.last_name AS member_last_name,
        a.first_name AS admin_first_name,
        a.last_name AS admin_last_name
    FROM deleted_subscriptions ds
    JOIN membership m ON ds.members_id = m.members_id
    JOIN users u ON m.user_id = u.id /* Join to get the member's name */
    LEFT JOIN users a ON ds.deleted_by_admin_id = a.id
    ORDER BY ds.deletion_timestamp DESC";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Subscriptions History</title>
    <link rel="stylesheet" href="../assets/css/membership_manage.css">
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
                <a href="../Guest/Index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<div class="dashboard-container">

    <aside class="sidebar">
        <ul>
            <li><a href="deleted_members_view.php"><i class="fas fa-history"></i> Deletion History</a></li>
            <li class="active"><a href="deleted_subscriptions_view.php"><i class="fas fa-history"></i> Subscription History</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="main-content-header">
            <h1>Archived Subscription History</h1>
        </div>

        <?php
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
            $msg = isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : '';

            if ($status == 'restored') {
                echo '<div class="alert success-message">Subscription successfully restored to the active list.</div>';
            } elseif ($status == 'error') {
                echo '<div class="alert error-message">**Operation Failed!** Error: ' . $msg . '</div>';
            }
        }
        ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="content-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>History ID</th>
                                <th>Member Name</th>
                                <th>Plan Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>GCash Ref</th>
                                <th>Deletion Date</th>
                                <th>Deleted By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['history_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['member_first_name'] . ' ' . $row['member_last_name']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($row['subscription_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                                    <td><?php echo htmlspecialchars($row['gcash_reference_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['deletion_timestamp']); ?></td>
                                    <td><?php echo htmlspecialchars($row['admin_first_name'] . ' ' . $row['admin_last_name']); ?></td>
                                    <td>
                                        <div class='action-buttons'>
                                            <form method='POST' action='../backend/process_subscription_restore.php'
                                                onsubmit='return confirm("Are you sure you want to restore this subscription to the active list?");' style="display:inline;">

                                                <input type='hidden' name='history_id' value='<?php echo htmlspecialchars($row['history_id']); ?>'>

                                                <button type='submit' class='action-btn btn-restore' title='Restore'>
                                                    <i class='fas fa-undo'></i> Restore
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state-container">
                <i class="fas fa-box-open empty-state-icon"></i>
                <h3 class="empty-state-title">No Archived Subscriptions Found</h3>
                <p class="empty-state-text">No subscription records have been archived yet.</p>
                <a href="subscriptionview.php" class="btn-pending-link">
                    <i class="fas fa-sync-alt"></i> View Active Subscriptions
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

  
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.querySelector('table tbody');
        const mainContent = document.querySelector('.main-content');

        // Function to display alerts
        const showAlert = (message, type) => {
            const existingAlert = document.querySelector('.ajax-alert');
            if (existingAlert) existingAlert.remove();

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${type}-message ajax-alert`;
            alertDiv.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i> ${message}`;
            
            // Insert after the main content header
            mainContent.insertBefore(alertDiv, mainContent.children[1]); 

            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        };

        // Event listener for all restore forms
        table.addEventListener('submit', function(e) {
            if (e.target.matches('form')) {
                e.preventDefault(); // Stop the default form submission (the redirect)

                const form = e.target;
                const historyId = form.querySelector('input[name="history_id"]').value;
                const row = form.closest('tr');

                if (!confirm('Are you sure you want to restore this subscription to the active list?')) {
                    return;
                }

                // Prepare form data for AJAX
                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        // 1. Remove the row from the table (UI update)
                        row.remove();
                        // 2. Display success message
                        showAlert('Subscription successfully restored to the active list.', 'success');
                        
                        // 3. Optional: Check if the table is now empty and update the UI
                        if (table.rows.length === 0) {
                            // You might want to reload the main content section to show the 'No Archived Subscriptions Found' message
                            window.location.reload(); 
                        }
                    } else {
                        // Display error message
                        showAlert(`Restoration failed. ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showAlert('An unexpected network error occurred.', 'error');
                });
            }
        });
    });

</script>

</body>
</html>