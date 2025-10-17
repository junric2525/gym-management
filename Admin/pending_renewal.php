<?php
session_start();
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');


// Ensure db.php path is correct
include '../backend/db.php'; 

// CRITICAL SECURITY CHECK (Keep this)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}

// --- MESSAGE RETRIEVAL FROM URL ---
$message = '';
$status = ''; // Will hold 'success' or 'error' from the URL

if (isset($_GET['message'])) {
    // Decode and store the message from the URL
    $message = htmlspecialchars(urldecode($_GET['message']));
}

if (isset($_GET['status'])) {
    // Store the status from the URL
    $status = htmlspecialchars($_GET['status']);
}

// --- DATABASE CONNECTION ---
// The initial include should handle the connection, but checking again for safety
if (!isset($conn) || $conn->connect_error) {
    die("FATAL ERROR: Could not connect to the database: " . $conn->connect_error);
}


// --- HANDLE APPROVAL / REJECTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Sanitize and validate inputs
    $members_id = filter_input(INPUT_POST, 'members_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    if ($members_id) {
        
        // Start transaction for atomic operations
        $conn->begin_transaction();
        $is_transaction_successful = false;

        try {
            // 1. Fetch current expiration date and GCash reference from membership table
            $member_query = $conn->prepare("SELECT expiration_date, gCash_reference FROM membership WHERE members_id = ? AND renewal_status = 'Pending'");
            $member_query->bind_param("i", $members_id);
            $member_query->execute();
            $member_result = $member_query->get_result();
            $member_data = $member_result->fetch_assoc();
            $member_query->close();

            if (!$member_data) {
                // Throw an exception to trigger rollback/error message
                throw new Exception("Renewal request for ID #{$members_id} was already processed or not found in 'Pending' status.");
            }
            
            // Extract current data
            $current_expiry = $member_data['expiration_date'];
            $gcash_ref = $member_data['gCash_reference'];


            if ($action === 'approve') {
                
                // --- DATE CALCULATION LOGIC ---
                $today_dt = new DateTime(date('Y-m-d'));
                $expiry_dt = new DateTime($current_expiry);

                // Determine the base date for adding the period
                if ($expiry_dt < $today_dt) {
                    // Membership is expired. Base renewal start on TODAY.
                    $base_dt = clone $today_dt;
                } else {
                    // Membership is active. Base renewal start is the CURRENT expiration date.
                    $base_dt = clone $expiry_dt;
                }

                // Add 1 year (P1Y) to the determined base date
                $new_expiry_dt = $base_dt->add(new DateInterval('P1Y'));
                $new_expiry = $new_expiry_dt->format('Y-m-d');
                
                
                // --- 2. UPDATE permanent 'membership' table (Renewal) ---
                $update_sql = "UPDATE membership 
                               SET renewal_status = 'Approved', 
                                   gCash_reference = NULL, 
                                   expiration_date = ? 
                               WHERE members_id = ? AND renewal_status = 'Pending'";
                $stmt_update = $conn->prepare($update_sql);
                $stmt_update->bind_param("si", $new_expiry, $members_id);
                
                if (!$stmt_update->execute() || $stmt_update->affected_rows !== 1) {
                    throw new Exception("Failed to update membership status and expiration date.");
                }
                $stmt_update->close();
                
                // 🛑 --- 3. CRITICAL: INSERT INTO 'invoices' TABLE (Invoice Creation) --- 
                $invoice_item_type = "Membership Renewal";
                $invoice_item_name = "Annual Membership Renewal"; 
                // Set payment date to the current date/time of approval
                $payment_date = date('Y-m-d H:i:s'); 

                $sql_insert_invoice = "
                    INSERT INTO invoices (
                        members_id, item_type, item_name, gcash_reference, payment_date, end_date
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ";
                
                $stmt_insert_invoice = $conn->prepare($sql_insert_invoice);
                
                $stmt_insert_invoice->bind_param(
                    "isssss", 
                    $members_id, 
                    $invoice_item_type, 
                    $invoice_item_name, 
                    $gcash_ref, // Use the GCash reference fetched from the member's record
                    $payment_date, 
                    $new_expiry // The new expiration date
                );

                if (!$stmt_insert_invoice->execute()) {
                    throw new Exception("Invoice creation failed (Check invoice table columns). Error: " . $stmt_insert_invoice->error);
                }
                $stmt_insert_invoice->close();
                
                
                // Commit the transaction only if all steps succeeded
                $conn->commit();
                $is_transaction_successful = true;
                
                if ($is_transaction_successful) {
                    // --- PRG REDIRECT SUCCESS ---
                    $successMsg = "Success: Membership ID #{$members_id} approved and extended to {$new_expiry}. Invoice created.";
                    header("Location: pending_renewal.php?message=" . urlencode($successMsg) . "&status=success");
                    exit();
                }

            } elseif ($action === 'reject') {
                // --- REJECTION LOGIC (No invoice needed) ---
                $update_sql = "UPDATE membership 
                               SET renewal_status = 'Rejected', 
                                   gCash_reference = NULL 
                               WHERE members_id = ? AND renewal_status = 'Pending'";
                $stmt_reject = $conn->prepare($update_sql);
                $stmt_reject->bind_param("i", $members_id);
                
                if (!$stmt_reject->execute() || $stmt_reject->affected_rows !== 1) {
                    throw new Exception("Failed to reject renewal request.");
                }
                $stmt_reject->close();
                
                // Commit the rejection transaction
                $conn->commit();

                // --- PRG REDIRECT SUCCESS ---
                $successMsg = "Success: Renewal request for ID #{$members_id} rejected. Member can resubmit.";
                header("Location: pending_renewal.php?message=" . urlencode($successMsg) . "&status=success");
                exit();
            }
        
        } catch (Exception $e) {
            // Rollback transaction on any error
            $conn->rollback();
            $errorMsg = "Action Failed: " . $e->getMessage();
            header("Location: pending_renewal.php?message=" . urlencode($errorMsg) . "&status=error");
            exit();
        }
    }
}


// --- FETCH PENDING RENEWALS ---
$pending_renewals = [];

// Fetch list of members with 'Pending' renewal_status
$sql_pending = "
    SELECT 
        m.members_id,
        CONCAT(u.first_name, ' ', u.last_name) AS full_name,
        u.email,
        m.gCash_reference,
        m.approved_at,
        m.expiration_date
    FROM membership m
    JOIN users u ON m.user_id = u.id
    WHERE m.renewal_status = 'Pending' 
    ORDER BY m.expiration_date ASC
";
$result = $conn->query($sql_pending);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_renewals[] = $row;
    }
} else {
    // Only set message if there was a DB error AND the message hasn't been set by a URL parameter
    if (!$message) {
        $message = "Database error retrieving pending renewals: " . $conn->error;
        $status = "error";
    }
}

// Close connection after all DB operations
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Membership Renewals</title>
    <link rel="stylesheet" href="../assets/css/pending_renewal.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        
        .btn-view { /* Used for Approve */
            background-color: #28a745;
            color: white;
            margin-right: 5px;
        }
        .btn-view:hover {
            background-color: #218838;
        }
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        .reject-btn:hover {
            background-color: #c82333;
        }
        .msg.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .msg.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-flex">
            <div class="logo">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img" onerror="this.onerror=null; this.src='https://placehold.co/40x40/ff6600/ffffff?text=CG';" />
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
                <li class="active"><a href="pending_renewal.php"><i class="fas fa-hand-holding-usd"></i> Renewal Pending</a></li>
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
                <h1>Pending Membership Renewals</h1>
                <p class="subtext">Review and verify GCash payments before approving the membership extension.</p>
            </div>

            <?php if ($message): ?>
                <div class="msg <?php echo $status === 'success' ? 'success' : 'error'; ?>">
                    <i class="fas <?php echo $status === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($pending_renewals)): ?>
                <div class="empty-state-container">
                    <i class="fas fa-hourglass-half empty-state-icon"></i>
                    <h3 class="empty-state-title">No Pending Renewals Found</h3>
                    <p class="empty-state-text">It looks like there are currently no renewal requests waiting for approval.</p>
                    <a href="membership_manage.php" class="btn-pending-link">
                        <i class="fas fa-users"></i> View Membership Management
                    </a>
                </div>
            <?php else: ?>
                <div class="content-card">
                    <div class="table-header">
                        <div class="search-bar">
                            <input type="text" id="searchInput" placeholder="Search by Member ID..." onkeyup="searchMemberRenewal()">
                            <button onclick="searchMemberRenewal()"><i class="fas fa-search"></i></button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Member ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>GCash Ref #</th>
                                    <th>Current Expiry</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="renewalTableBody">
                                <?php foreach ($pending_renewals as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['members_id']); ?></td>
                                        <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['email']); ?></td>
                                        <td style="font-weight:bold; color:#f97316;"><?php echo htmlspecialchars($request['gCash_reference']); ?></td>
                                        <td style="color:blue;"><?php echo htmlspecialchars(date('Y-m-d', strtotime($request['expiration_date']))); ?></td>
                                        <td>
                                            <form method="POST" class="action-buttons-group">
                                                <input type="hidden" name="members_id" value="<?php echo htmlspecialchars($request['members_id']); ?>">
                                                <button type="submit" name="action" value="approve" class="action-btn btn-view" onclick="return confirm('Are you sure you want to approve this renewal? This will extend the membership by one year and generate an invoice.');">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="submit" name="action" value="reject" class="action-btn reject-btn" onclick="return confirm('Are you sure you want to reject this renewal? The member will need to resubmit.');">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    // Profile dropdown functionality
    document.querySelector('.profile-btn').addEventListener('click', function() {
        // Using a class toggle for better styling control
        this.closest('.profile-dropdown').classList.toggle('show');
    });
    window.addEventListener('click', function(e) {
        const profileDropdown = document.querySelector('.profile-dropdown');
        // If the click is outside the dropdown area
        if (profileDropdown && !profileDropdown.contains(e.target)) {
            profileDropdown.classList.remove('show');
        }
    });

    /**
     * Filters the table rows based on the Member ID entered in the search input.
     */
    function searchMemberRenewal() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toUpperCase();
        const tableBody = document.getElementById("renewalTableBody");
        const rows = tableBody.getElementsByTagName("tr");

        for (let i = 0; i < rows.length; i++) {
            const td = rows[i].getElementsByTagName("td")[0]; // Member ID column
            if (td) {
                const txtValue = td.textContent || td.innerText;
                rows[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
            }
        }
    }
    </script>

</body>
</html>