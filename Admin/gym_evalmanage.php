<?php
// PHP Configuration and Setup
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// --- Initialization ---
$message = '';
$messageType = '';
$evaluations = []; // Used the correct variable name
$conn = null; // Initialize connection variable
$db_error = false;

// =======================================================================
// 1. Database Connection Handling
// =======================================================================
if (file_exists('../backend/db.php')) {
    include '../backend/db.php'; 
    
    // Check if $conn was set and if the connection is successful
    if (!isset($conn) || $conn->connect_error) {
        $message = "FATAL ERROR: Could not connect to the database.";
        $messageType = 'error';
        $conn = null; // Ensure $conn is null if connection failed
        $db_error = true;
    }
} else {
    $message = "FATAL ERROR: db.php not found. Cannot connect to database.";
    $messageType = 'error';
    $db_error = true;
}

// =======================================================================
// 2. CRITICAL SECURITY CHECK & Session Access
// =======================================================================
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Non-admin users are redirected to the guest index
    header("Location: ../Guest/index.php");
    exit(); 
}

// Safely fetch admin user details for display (though not used in HTML, good practice)
$current_user_id = $_SESSION['user_id'] ?? 'N/A'; 
$current_user_full_name = $_SESSION['full_name'] ?? 'Admin';


// =======================================================================
// 3. DELETE EVALUATION LOGIC (Single Delete and Delete All)
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$db_error) {
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete_evaluation') {
        // --- Single Delete Logic ---
        $evaluation_id = filter_input(INPUT_POST, 'evaluation_id', FILTER_VALIDATE_INT);

        if ($evaluation_id) {
            $sql_delete = "DELETE FROM gym_evaluations WHERE evaluation_id = ?";
            
            if ($conn && $stmt = $conn->prepare($sql_delete)) { 
                $stmt->bind_param("i", $evaluation_id); 
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $message = "Evaluation ID **{$evaluation_id}** successfully deleted. 🗑️";
                        $messageType = 'success';
                    } else {
                        $message = "Evaluation ID **{$evaluation_id}** not found or already deleted.";
                        $messageType = 'error';
                    }
                } else {
                    error_log("SQL Delete Error: " . $stmt->error);
                    $message = "Error deleting evaluation: " . $stmt->error;
                    $messageType = 'error';
                }
                $stmt->close();
            } else {
                $message = "Database connection or prepare error during deletion.";
                $messageType = 'error';
            }
        } else {
            $message = "Invalid Evaluation ID submitted.";
            $messageType = 'error';
        }

    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_all_evaluations') {
        // --- Delete All Logic ---
        if ($conn) {
            // Using a simple query for mass deletion, assuming admin authority is checked.
            // A transaction would be better practice here but keeping it simple for now.
            $sql_delete_all = "DELETE FROM gym_evaluations";
            
            if ($conn->query($sql_delete_all)) {
                $count = $conn->affected_rows;
                $message = "Successfully deleted **{$count}** evaluations from the system. 🗑️";
                $messageType = 'success';
            } else {
                error_log("SQL Delete All Error: " . $conn->error);
                $message = "FATAL ERROR: Could not delete all evaluations: " . $conn->error;
                $messageType = 'error';
            }
        } else {
            $message = "Database connection error. Cannot perform mass deletion.";
            $messageType = 'error';
        }
    }
}


// --- Fetch Evaluation Data (If connection is good) ---
// Note: This runs after any deletion logic to refresh the table view.
if (isset($conn) && $conn->connect_error === null) {
    // Corrected SQL Query: Using CONCAT() to combine first_name and last_name
    $sql = "
        SELECT 
            ge.evaluation_id, 
            ge.cleanliness_rating, 
            ge.equipment_rating, 
            ge.staff_rating, 
            ge.opinion_text, 
            ge.submission_date AS submitted_at,
            m.members_id, 
            -- Combine first_name and last_name with a space and alias as 'name'
            CONCAT(u.first_name, ' ', u.last_name) AS name 
        FROM 
            gym_evaluations ge
        JOIN 
            membership m ON ge.member_id = m.members_id
        JOIN 
            users u ON m.user_id = u.id   -- NOTE: The 'users' table primary key is 'id'
        ORDER BY 
            ge.submission_date DESC;
    ";
    
    $result = $conn->query($sql);
    if ($result) {
        if ($result->num_rows > 0) {
            // Fetch all rows into the $evaluations array
            while ($row = $result->fetch_assoc()) {
                $evaluations[] = $row;
            }
        } else {
            // Only set a message if there wasn't an earlier error or a delete message
            if (empty($message)) {
                $message = "No gym evaluations found yet.";
                $messageType = 'success';
            }
        }
        $result->free();
    } else {
        error_log("SQL Error on evaluation fetch: " . $conn->error);
        // Only override previous message if no delete message was set
        if (empty($message)) {
             $message = "Error fetching evaluations: " . $conn->error;
             $messageType = 'error';
        }
    }
    
    // Close the connection after all database operations are complete
    if ($conn && !$conn->connect_error) { 
        $conn->close(); 
    }
}


// --- Helper function (from original code) ---
function display_message($msg, $type) {
    if (!empty($msg)) {
        $class = $type === 'success' ? 'success' : 'error';
        echo "<div class='message-box {$class}'>";
        echo htmlspecialchars($msg);
        echo "<span class='close-message-btn' onclick='this.parentNode.style.display=\"none\";'>&times;</span>";
        echo "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gym Evaluations (Admin)</title>
    <link rel="stylesheet" href="../assets/css/gym_evalmanage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    
</head>
<body>

    <header class="header">
        <div class="header-content-wrapper header-flex">
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
                <li><a href="coach_appointmentview.php"><i class="fas fa-chalkboard-teacher"></i> Coach Appointments</a></li>
                <li><a href="promo_event.php"><i class="fas fa-bullhorn"></i> Updating Promo</a></li>
                <li class="active"><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations</a></li>
            </ul>
        </aside>

        <div class="admin-container"> 
            
            <h1>Gym Evaluation Management</h1>
            <?php display_message($message, $messageType); ?>
            
            <div class="action-bar">
                
                <a href="../backend/generate_gymeval.php" class="action-btn export-pdf-btn" target="_blank" title="Generate and download a PDF report">
                <i class="fas fa-file-pdf"></i> Download as PDF
                </a>

                <a href="coach_evalmanage.php" class="coach-eval-btn">
                    <i class="fas fa-chalkboard-teacher"></i> View Coach Evaluations
                </a>

                <form action="gym_evalmanage.php" method="POST" onsubmit="return confirm('WARNING! Are you absolutely sure you want to DELETE ALL gym evaluations? This cannot be undone.');">
                    <input type="hidden" name="action" value="delete_all_evaluations">
                    <button type="submit" class="delete-all-btn">
                        <i class="fas fa-trash-alt"></i> Delete All
                    </button>
                </form>
            </div>
            <?php if (!empty($evaluations)): ?>
            <table class="eval-table"> 
                <thead>
                    <tr>
                        <th>Eval ID</th>
                        <th>Member ID</th>
                        <th>Member Name</th>
                        <th>Cleanliness</th>
                        <th>Equipment</th>
                        <th>Staff</th>
                        <th>Opinion</th>
                        <th>Submitted At</th>
                        <th>Action</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evaluations as $eval): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($eval['evaluation_id']); ?></td>
                        <td><?php echo htmlspecialchars($eval['members_id']); ?></td>
                        <td><?php echo htmlspecialchars($eval['name']); ?></td>
                        <td class="rating-cell"><?php echo htmlspecialchars($eval['cleanliness_rating']); ?> / 5</td>
                        <td class="rating-cell"><?php echo htmlspecialchars($eval['equipment_rating']); ?> / 5</td>
                        <td class="rating-cell"><?php echo htmlspecialchars($eval['staff_rating']); ?> / 5</td>
                        <td title="<?php echo htmlspecialchars($eval['opinion_text']); ?>"><?php echo nl2br(htmlspecialchars($eval['opinion_text'])); ?></td> 
                        <td><?php echo htmlspecialchars($eval['submitted_at']); ?></td>
                        
                        <td>
                            <form action="gym_evalmanage.php" method="POST" onsubmit="return confirm('Are you sure you want to delete Evaluation ID: <?php echo htmlspecialchars($eval['evaluation_id']); ?>? This action cannot be undone.');">
                                <input type="hidden" name="action" value="delete_evaluation">
                                <input type="hidden" name="evaluation_id" value="<?php echo htmlspecialchars($eval['evaluation_id']); ?>">
                                <button type="submit" class="delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>There are no evaluations to display.</p>
            <?php endif; ?>
        
        </div>
        </div>

</body>
</html>