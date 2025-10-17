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

/* =========================================
 * 1. SESSION & AUTHENTICATION
 * ========================================= */

// Check for Admin Session (Redundant check, but safe to keep)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php"); 
    exit(); 
}

// Initialize messages
$msg = "";
$error = "";

/* =========================================
 * 2. HELPER FUNCTIONS
 * ========================================= */

/**
 * Calculates the subscription end date based on the billing cycle.
 * @param string $billing_cycle The cycle ('monthly', 'daily', etc.)
 * @param string $start_date The starting date
 * @return string|null The calculated end date (Y-m-d format) or null if invalid/daily.
 */
function calculate_end_date($billing_cycle, $start_date) {
    $end_date = null;
    // CRITICAL FIX: Ensure $start_date is only the date part for correct 'strtotime' calculation, 
    // as full timestamps can sometimes cause issues with +1 month/year calculations crossing over.
    $start_date_only = date("Y-m-d", strtotime($start_date)); 
    $start_timestamp = strtotime($start_date_only);

    switch (strtolower($billing_cycle)) {
        // 🛑 FIX: Return NULL for 'daily' subscription (no set end date/auto-renewal implied)
        case "daily": 
            return null; // This will insert NULL into the 'end_date' column.
            break; 
        case "monthly": 
            $end_date = date("Y-m-d", strtotime("+1 month", $start_timestamp)); 
            break;
        case "quarterly": 
            $end_date = date("Y-m-d", strtotime("+3 months", $start_timestamp)); 
            break;
        case "yearly": 
            $end_date = date("Y-m-d", strtotime("+1 year", $start_timestamp)); 
            break;
    }
    return $end_date;
}

/* =========================================
 * 3. ACTION HANDLER (Approve/Reject)
 * ========================================= */

if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === "approve") {
        // 1. Fetch data for processing
        $query = $conn->prepare("SELECT members_id, billing_cycle, gcash_reference FROM temporary_subscription WHERE temp_id = ?");
        $query->bind_param("i", $id);
        $query->execute();
        $result = $query->get_result();

        if ($row = $result->fetch_assoc()) {
            $conn->begin_transaction(); 
            try {
                // FIX: Use current date/time for accurate start of subscription (in case of approval delay)
                $start_date = date("Y-m-d H:i:s"); 
                $billing_cycle_lower = strtolower($row['billing_cycle']);
                // $end_date will be NULL for 'daily' due to the updated function
                $end_date = calculate_end_date($billing_cycle_lower, $start_date); 
                $sub_type_clean = $billing_cycle_lower;
                
                // Determine MySQL interval for membership update
                $interval = '';
                switch ($billing_cycle_lower) {
                    case "daily": $interval = 'INTERVAL 1 DAY'; break;
                    case "monthly": $interval = 'INTERVAL 1 MONTH'; break;
                    case "quarterly": $interval = 'INTERVAL 3 MONTH'; break;
                    case "yearly": $interval = 'INTERVAL 1 YEAR'; break;
                    default: 
                        // CRITICAL SECURITY FIX: Throw an error for an unexpected interval.
                        throw new Exception("Invalid billing cycle for membership update."); 
                }

                // A. Update the main 'membership' expiration date (critical for access)
                // The switch statement effectively whitelists the $interval string.
                $update_membership = $conn->prepare("
                    UPDATE membership 
                    SET expiration_date = DATE_ADD(COALESCE(expiration_date, NOW()), $interval)
                    WHERE members_id = ?
                ");
                $update_membership->bind_param("i", $row['members_id']);
                if (!$update_membership->execute()) throw new Exception("Membership expiration update failed: " . $update_membership->error);
                $update_membership->close();
                
                // B. Insert into the 'subscription' history table
                $insert_sub = $conn->prepare("
                    INSERT INTO subscription 
                    (members_id, subscription_type, gcash_reference_number, start_date, end_date, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'active', NOW())
                ");
                // Use $end_date here. If it is NULL, it will insert NULL into the DB column.
                $insert_sub->bind_param("issss", $row['members_id'], $sub_type_clean, $row['gcash_reference'], $start_date, $end_date);
                if (!$insert_sub->execute()) throw new Exception("Subscription history insert failed: " . $insert_sub->error);
                $insert_sub->close();

                // B.1. 🛑 CRITICAL FIX: Insert into the centralized 'invoices' table
                $invoice_item_type = "Subscription Payment";
                $invoice_item_name = ucfirst($billing_cycle_lower) . " Subscription";

                $insert_invoice = $conn->prepare("
                    INSERT INTO invoices 
                    (members_id, item_type, item_name, gcash_reference, payment_date, end_date) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                // The parameters for the invoice record
                $invoice_params = [
                    $row['members_id'], 
                    $invoice_item_type, 
                    $invoice_item_name, 
                    $row['gcash_reference'], 
                    $start_date, 
                    $end_date
                ];
                
                // Handle binding for potential NULL end_date. 
                // Using call_user_func_array for dynamic binding is safest way for NULLs.
                // NOTE: This assumes your `end_date` in `invoices` is nullable.
                $types = 'isssss'; // i:members_id, s:item_type, s:item_name, s:gcash_ref, s:payment_date, s:end_date
                if ($end_date === null) {
                    $types = 'issssi'; // Change last 's' to 'i' (for integer NULL) if necessary, but 's' usually works too.
                }

                $insert_invoice->bind_param("isssss", ...$invoice_params);
                
                if (!$insert_invoice->execute()) throw new Exception("Invoice table insert failed: " . $insert_invoice->error);
                $insert_invoice->close();
                // 🛑 END CRITICAL FIX

                // C. Delete the temporary record
                $delete = $conn->prepare("DELETE FROM temporary_subscription WHERE temp_id = ?");
                $delete->bind_param("i", $id);
                if (!$delete->execute()) throw new Exception("Temporary record deletion failed: " . $delete->error);
                $delete->close();

                $conn->commit();
                $msg = "Subscription approved successfully. Member access extended and invoice created.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Approval failed: " . $e->getMessage();
            }
        } else {
            $error = "Temporary subscription record not found.";
        }
    } elseif ($action === "reject") {
        // === REJECTION LOGIC (No Change Needed) ===
        $conn->begin_transaction();
        try {
            // 1. Fetch data for the member ID before deletion
            $fetch_member = $conn->prepare("SELECT members_id FROM temporary_subscription WHERE temp_id = ?");
            $fetch_member->bind_param("i", $id);
            $fetch_member->execute();
            $result_member = $fetch_member->get_result();
            $member_row = $result_member->fetch_assoc();
            $fetch_member->close();

            if (!$member_row) {
                throw new Exception("Temporary record not found for rejection.");
            }

            $member_id_rejected = $member_row['members_id'];

            // 2. Delete the temporary record
            $delete = $conn->prepare("DELETE FROM temporary_subscription WHERE temp_id = ?");
            $delete->bind_param("i", $id);
            if (!$delete->execute()) throw new Exception("Temporary record deletion failed.");
            $delete->close();
            
            // 3. Set a SESSION flag for the specific member
            $_SESSION['member_notification'] = [
                'members_id' => $member_id_rejected,
                'type' => 'error',
                'message' => 'Your subscription request was rejected. Please verify your GCash reference number or contact administration for assistance.'
            ];

            $conn->commit();
            $msg = "Subscription rejected and removed. Member will be notified on their next page load.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Rejection failed: " . $e->getMessage();
        }
        // === END REJECTION LOGIC ===
    }

    // Redirect to prevent form resubmission
    $_SESSION['msg'] = $msg;
    $_SESSION['error'] = $error;
    header("Location: pending_subscription.php");
    exit();
}

/* =========================================
 * 4. LOAD DATA FOR DISPLAY
 * ========================================= */

// Handle session messages
if (isset($_SESSION['msg'])) { $msg = $_SESSION['msg']; unset($_SESSION['msg']); }
if (isset($_SESSION['error'])) { $error = $_SESSION['error']; unset($_SESSION['error']); }

// SQL Query to fetch pending subscriptions with member name
$query = "
    SELECT 
        p.temp_id, 
        p.members_id, 
        p.billing_cycle, 
        p.gcash_reference, 
        p.created_at,
        CONCAT(u.first_name, ' ', u.last_name) AS full_name
    FROM temporary_subscription p
    JOIN membership m ON p.members_id = m.members_id
    JOIN users u ON m.user_id = u.id
    ORDER BY p.created_at DESC
";
$result = $conn->query($query);
$pending_subscriptions = [];

if ($result) {
    $today = date("Y-m-d H:i:s"); // Use full timestamp
    foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
        // Calculate the expected end date for display
        $row['expected_end_date'] = calculate_end_date($row['billing_cycle'], $today);
        
        // 🛑 FIX: Ensure 'N/A' is displayed for NULL end dates in the table preview
        if ($row['expected_end_date'] === null) {
            $row['expected_end_date'] = 'N/A (Auto-Renew)'; // More informative
        }
        
        $pending_subscriptions[] = $row;
    }
    $result->free();
} else {
    $error = "Failed to load pending subscriptions: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pending Subscriptions - Admin</title>
<link rel="stylesheet" href="../assets/css/pending_subs.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>

<header class="header">
    <div class="header-content-wrapper">
        <div class="header-flex">
            <div class="logo">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img">
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <div class="profile-dropdown">
                <button class="profile-btn" id="profileButton">
                    <i class="fas fa-user"></i> <i class="fas fa-caret-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="Admin.php"><i class="fas fa-home"></i> Home</a>
                    <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>

                </div>
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
                <li class="active"><a href="pending_subscription.php"><i class="fas fa-hourglass-half"></i> Subscription Pending</a></li>
                <li><a href="coach_update.php"><i class="fas fa-user-tie"></i> Coach Updating</a></li>
                <li><a href="coach_appointmentview.php"><i class="fas fa-chalkboard-teacher"></i> Coach Appointments</a></li>
                <li><a href="promo_event.php"><i class="fas fa-bullhorn"></i> Updating Promo</a></li>
                <li><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations</a></li>
            </ul>
        </aside>
    
        <main class="main-content">
        <div class="main-content-header">
            <h1>Pending Subscriptions</h1>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert success-message" style="margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert error-message" style="margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (count($pending_subscriptions) > 0): ?>
            <div class="content-card"> 
                <div class="table-header">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search by Member ID..." onkeyup="searchMember()">
                        <button onclick="searchMember()"><i class="fas fa-search"></i></button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="subscriptionTable">
                        <thead>
                            <tr>
                                <th>Temp ID</th>
                                <th>Member ID</th>
                                <th>Member Name</th>
                                <th>Plan</th>
                                <th>GCash Reference</th>
                                <th>Date Requested</th>
                                <th>End Date (Expected)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_subscriptions as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['temp_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['members_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($row['billing_cycle'])); ?></td>
                                <td><?php echo htmlspecialchars($row['gcash_reference']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($row['expected_end_date']); ?></td>
                                <td>
                                    <div class="action-buttons-group">
                                        <a href="?action=approve&id=<?php echo $row['temp_id']; ?>" class="action-btn approve-btn" onclick="return handleActionClick(this, 'APPROVE', 'Are you sure you want to approve this subscription and grant access?');"><i class="fas fa-check"></i> Approve</a>
                                        <a href="?action=reject&id=<?php echo $row['temp_id']; ?>" class="action-btn reject-btn" onclick="return handleActionClick(this, 'REJECT', 'Are you sure you want to reject this request and remove it? This will send a notification to the member.');"><i class="fas fa-times"></i> Reject</a>
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
                <h3 class="empty-state-title">No Pending Subscriptions Found</h3>
                <p class="empty-state-text">It looks like there are currently no pending subscriptions needing approval.</p>
                <a href="subscriptionview.php" class="btn-pending-link">
                    <i class="fas fa-users"></i> View Active Subscriptions
                </a>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
    // == Profile Dropdown Handler ==
    document.addEventListener('DOMContentLoaded', () => {
        const profileBtn = document.querySelector('.profile-btn');
        const dropdownMenu = document.querySelector('.dropdown-menu');

        if (profileBtn && dropdownMenu) {
            // Toggle the 'show' class when the button is clicked
            profileBtn.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevents document click from immediately closing it
                dropdownMenu.classList.toggle('show');
            });

            // Close the dropdown if the user clicks outside of it
            document.addEventListener('click', (event) => {
                // Check if the click target is NOT inside the dropdown menu AND NOT the profile button itself
                if (!dropdownMenu.contains(event.target) && !profileBtn.contains(event.target)) {
                    // Only remove the class if it is currently visible
                    if (dropdownMenu.classList.contains('show')) {
                        dropdownMenu.classList.remove('show');
                    }
                }
            });
        }
    });

    // Function to handle action clicks and warn about needed custom modal UI
    function handleActionClick(element, action, message) {
        // Since confirm() is disallowed, this function allows the action to proceed
        // while warning that a proper custom modal UI must be implemented for confirmation.
        console.warn(`[UI ALERT] Custom modal implementation is required for action: ${action}. Message: ${message}`);
        
        // Use a standard JavaScript confirm as a temporary placeholder for user confirmation
        // A true production environment should replace this with a beautiful, custom modal.
        return confirm(message); 
    }

    // Simple search functionality for the table
    function searchMember() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('subscriptionTable');
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            const memberIdCell = tr[i].getElementsByTagName('td')[1]; // Member ID is the second column (index 1)
            if (memberIdCell) {
                const txtValue = memberIdCell.textContent || memberIdCell.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
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