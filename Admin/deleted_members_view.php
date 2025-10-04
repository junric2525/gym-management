<?php
// FILE: Admin/deleted_members_view.php

// 1. Database Connection and Session Check (Always include this)
require_once '../backend/db.php'; 
session_start();
// NOTE: Add your authentication/authorization check here (e.g., if admin not logged in, redirect)

// --- 2. PHP Query to Fetch Deleted Members ---
$sql = "
    SELECT 
        dm.*, 
        u.first_name, 
        u.last_name,
        a.first_name AS admin_first_name,
        a.last_name AS admin_last_name
    FROM deleted_members dm
    JOIN users u ON dm.user_id = u.id  
    LEFT JOIN users a ON dm.deleted_by_admin_id = a.id
    ORDER BY dm.deletion_timestamp DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Members History</title>
    <link rel="stylesheet" href="../assets/css/membership_manage.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                <li class="active"><a href="deleted_members_view.php"><i class="fas fa-history"></i> Deletion History</a></li> 
                 <li><a href="deleted_subscription_view.php"><i class="fas fa-history"></i> Subscription History</a></li>
                </ul>
        </aside>

        <main class="main-content"> 
            <h2>Archived Member History</h2>

            <?php
            if (isset($_GET['status'])) {
                $status = $_GET['status'];
                $msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
                
                if ($status == 'restored') {
                    echo '<div class="alert alert-success">Member successfully restored to the active list.</div>';
                } elseif ($status == 'error') {
                    echo '<div class="alert alert-danger">**Operation Failed!** Error: ' . $msg . '</div>';
                }
            }
            ?>

            <?php
            if ($result->num_rows > 0) {
                // --- TABLE OUTPUT ---
                echo "<table>";
                echo "<thead><tr>
                        <th>History ID</th>
                        <th>Member Name</th> 
                        <th>Original ID</th>
                        <th>Deletion Date</th>
                        <th>Deleted By</th>
                        <th>Expiration Date (Original)</th>
                        <th>Action</th>
                      </tr></thead>";

                echo "<tbody>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['history_id']}</td>
                        <td>{$row['first_name']} {$row['last_name']}</td> 
                        <td>{$row['original_members_id']}</td>
                        <td>{$row['deletion_timestamp']}</td>
                        <td>{$row['admin_first_name']} {$row['admin_last_name']}</td>
                        <td>{$row['expiration_date']}</td>
                        <td>
                            <div class='action-buttons'>
                                <form method='POST' action='../backend/process_member_restore.php' 
                                    onsubmit='return confirm(\"Restore member {$row['first_name']} {$row['last_name']} to active list?\");'>
                                    
                                    <input type='hidden' name='history_id' value='{$row['history_id']}'> 
                                    <button type='submit' class='btn btn-success'>
                                        <i class='fas fa-undo'></i> Restore
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>";
                }
                echo "</tbody>";
                echo "</table>";
                
            } else {
                // --- EMPTY STATE UI ---
                echo " <div class='empty-state-container'>";
                echo " <i class='fas fa-box-open empty-state-icon'></i>";
                echo " <h3 class='empty-state-title'>No Archived Members Found</h3>";
                echo " <p class='empty-state-text'>No member records have been archived yet.</p>";
                 echo "    <a href='subscriptionview.php' class='btn-pending-link'><i class='fas fa-clipboard-list'></i> Check Membership Management</a>";
                echo "</div>";
            }
            
            $conn->close();
            ?>
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

    </script>
</body>
</html>