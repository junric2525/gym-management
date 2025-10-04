<?php
session_start();
include '../backend/db.php';

// Check for Admin Session
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/Index.html"); 
    exit(); 
}

// PHP logic already exists, keep unchanged
$msg = "";
$error = "";

function calculate_end_date($billing_cycle, $start_date) {
    $end_date = null;
    switch (strtolower($billing_cycle)) {
        case "monthly": $end_date = date("Y-m-d", strtotime("+1 month", strtotime($start_date))); break;
        case "quarterly": $end_date = date("Y-m-d", strtotime("+3 months", strtotime($start_date))); break;
        case "yearly": $end_date = date("Y-m-d", strtotime("+1 year", strtotime($start_date))); break;
    }
    return $end_date;
}

if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === "approve") {
        $query = $conn->prepare("SELECT members_id, billing_cycle, gcash_reference FROM temporary_subscription WHERE temp_id = ?");
        $query->bind_param("i", $id);
        $query->execute();
        $result = $query->get_result();

        if ($row = $result->fetch_assoc()) {
            $conn->begin_transaction(); 
            try {
                $start_date = date("Y-m-d");
                $end_date = calculate_end_date($row['billing_cycle'], $start_date);

                $insert = $conn->prepare("INSERT INTO subscription 
                    (members_id, subscription_type, gcash_reference_number, start_date, end_date, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'active', NOW())");
                $insert->bind_param("issss", $row['members_id'], $row['billing_cycle'], $row['gcash_reference'], $start_date, $end_date);
                if (!$insert->execute()) throw new Exception("Subscription insert failed: " . $insert->error);

                $delete = $conn->prepare("DELETE FROM temporary_subscription WHERE temp_id = ?");
                $delete->bind_param("i", $id);
                if (!$delete->execute()) throw new Exception("Temporary record deletion failed: " . $delete->error);

                $conn->commit();
                $msg = "Subscription approved successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Approval failed: " . $e->getMessage();
            }
        } else {
            $error = "Temporary subscription record not found.";
        }
    } elseif ($action === "reject") {
        try {
            $delete = $conn->prepare("DELETE FROM temporary_subscription WHERE temp_id = ?");
            $delete->bind_param("i", $id);
            $delete->execute();
            $msg = "Subscription rejected and removed.";
        } catch (Exception $e) {
            $error = "Rejection failed: " . $e->getMessage();
        }
    }

    $_SESSION['msg'] = $msg;
    $_SESSION['error'] = $error;
    header("Location: pending_subscription.php");
    exit();
}

if (isset($_SESSION['msg'])) { $msg = $_SESSION['msg']; unset($_SESSION['msg']); }
if (isset($_SESSION['error'])) { $error = $_SESSION['error']; unset($_SESSION['error']); }

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
    $today = date("Y-m-d");
    foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
        $row['expected_end_date'] = calculate_end_date($row['billing_cycle'], $today);
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
<title>Pending Subscriptions - Admin</title>
<link rel="stylesheet" href="../assets/css/pending_subs.css">
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
                    <table>
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
                                <td style="font-weight:bold; color:blue;"><?php echo htmlspecialchars($row['expected_end_date']); ?></td>
                                <td>
                                    <a href="?action=approve&id=<?php echo $row['temp_id']; ?>" class="btn approve"><i class="fas fa-check-circle"></i> Approve</a>
                                    <a href="?action=reject&id=<?php echo $row['temp_id']; ?>" class="btn reject" onclick="return confirm('Are you sure you want to reject this request?');"><i class="fas fa-times-circle"></i> Reject</a>
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
                <h3 class="empty-state-title">No Approved Subscriptions Found</h3>
                <p class="empty-state-text">It looks like there are currently no pending subscriptions.</p>
                <a href="subscriptionview.php" class="btn-pending-link">
                    <i class="fas fa-users"></i> View Subscription
                </a>
            </div>
        <?php endif; ?>
    </main>
</div>

<script src="../assets/js/pending_subs.js">
  
</script>
</body>
</html>