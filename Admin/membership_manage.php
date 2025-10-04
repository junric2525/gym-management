<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Membership Management</title>
    <link rel="stylesheet" href="../assets/css/membership_manage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
</head>
<body>
    <header class="header">
        <div class="container header-flex">
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
            <li><a href="payment_pendingview.php"><i class="fas fa-hand-holding-usd"></i> Membership Pending</a></li>
            <li><a href="subscriptionview.php"><i class="fas fa-sync-alt"></i> Subscription Management</a></li>
            <li class="active"><a href="pending_subscription.php"><i class="fas fa-hourglass-half"></i> Subscription Pending</a></li>
            <li><a href="Staff.html"><i class="fas fa-user-tie"></i> Coach Updating</a></li>
            <li><a href="UpdatingPromo.html"><i class="fas fa-bullhorn"></i> Updating Event/Promo</a></li>
            <li><a href="PerformanceAnalytics.html"><i class="fas fa-chart-line"></i> Performance Analytics</a></li>
        </ul>
    </aside>

        <main class="main-content">
            <h2>Approved Members</h2>
            
            <div class="table-header">
                
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search by Member ID or Name...">
                    <button onclick="searchMember()"><i class="fas fa-search"></i></button>
                </div>

                <!-- NEW BUTTON LINK ADDED HERE -->
                <a href="deleted_members_view.php" class="history-btn">
                    <i class="fas fa-history"></i> Deletion History
                </a>
                
            </div>

            <?php
            // Include database connection file
            // NOTE: Replace '../backend/db.php' with your actual connection path if different
            include '../backend/db.php';
            
            // SQL Query to fetch first_name and last_name from the 'users' table
            $sql = "
                SELECT 
                    m.*, 
                    u.first_name, 
                    u.last_name,
                    u.email 
                FROM 
                    membership m
                JOIN 
                    users u ON m.user_id = u.id 
                ORDER BY 
                    m.approved_at DESC"; 
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                // --- TABLE OUTPUT (If members exist) ---
                echo "<div class='table-responsive'><table>";
                echo "<thead><tr>
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
                    <th>Approved at</th>
                    <th>Expiration Date</th>
                    <th>Action</th>
                </tr></thead>";

                echo "<tbody>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['members_id']}</td>
                        <td>{$row['user_id']}</td>
                        <td>" . htmlspecialchars("{$row['first_name']} {$row['last_name']}") . "</td> 
                        <td>" . htmlspecialchars($row['email']) . "</td> 
                        <td>" . htmlspecialchars($row['gender']) . "</td>
                        <td>" . htmlspecialchars($row['contact']) . "</td>
                        <td>" . htmlspecialchars($row['emergency_name']) . "</td>
                        <td>" . htmlspecialchars($row['emergency_number']) . "</td>
                        <td>" . htmlspecialchars($row['emergency_relation']) . "</td>
                        <td>" . htmlspecialchars($row['medical_conditions']) . "</td>
                        <td>" . htmlspecialchars($row['medical_details']) . "</td>
                        <td>" . htmlspecialchars($row['medications']) . "</td>
                        <td>" . htmlspecialchars($row['medications_details']) . "</td>
                        <td>" . htmlspecialchars($row['gcash_reference']) . "</td>
                        <td><a href='" . htmlspecialchars($row['validid_path']) . "' target='_blank'>View ID</a></td>
                        <td>" . htmlspecialchars($row['approved_at']) . "</td>
                        <td>" . htmlspecialchars($row['expiration_date']) . "</td>
                        <td>
                            <div class='action-buttons'>
                                <form method='POST' action='../backend/process_member_archival.php' 
                                    onsubmit='return confirm(\"Are you sure you want to archive this member?\");'>
                                    <input type='hidden' name='members_id' value='{$row['members_id']}'> 
                                    <button type='submit' name='archive' class='delete-btn'><i class='fa-solid fa-trash'></i> Delete </button> 
                                </form>
                            </div>
                        </td>
                    </tr>";
                }
                echo "</tbody>";
                echo "</table></div>";
                
            } else {
                // --- EMPTY STATE UI (If no members exist) ---
                echo "<div class='empty-state-container'>";
                echo "       <i class='fas fa-user-slash empty-state-icon'></i>";
                echo "       <h3 class='empty-state-title'>No Approved Members Found</h3>";
                echo "       <p class='empty-state-text'>It looks like no membership applications have been approved yet, or the database is currently empty.</p>";
                echo "       <a href='payment_pendingview.php' class='btn-pending-link'><i class='fas fa-clipboard-list'></i> Check Pending Applications</a>";
                echo "</div>";
            }
            
            // Close database connection
            $conn->close();
            ?>
        </main>
    </div>

    <script src="../assets/js/membership_manage.js" defer></script>
    <script>
        // Simple client-side search function
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
