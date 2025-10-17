<?php
session_start();
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');


// Include the database connection file (which sets up the $conn mysqli object)
include '../backend/db.php'; 

// CRITICAL SECURITY CHECK: Ensure only logged-in admins can access this page
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}

$message = '';

// ------------------------------------------------------------------
// === 2. HANDLING FORM SUBMISSION (INSERT NEW COACH) ===
// ------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_coach'])) {
    
    // 2a. Sanitize and retrieve text data from the form
    $name = trim($_POST['name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');
    
    // Check for required fields
    if (empty($name) || empty($gender) || empty($specialization)) {
        $message = "❌ Error: All fields (Name, Gender, Specialization) are required.";
    } else {
        
        // 2b. Prepare SQL Query (INSERT) - Removed 'picture_url'
        $sql = "INSERT INTO coaches (name, gender, specialization) 
                VALUES (?, ?, ?)";
        $types = "sss"; // 3 strings
        $params = [$name, $gender, $specialization]; 
        
        try {
            $stmt = $conn->prepare($sql);
            
            // Call bind_param dynamically with the correct types and parameters
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                $message = "✅ New coach **{$name}** created successfully! (ID: {$new_id})";
                
                // Clear the data for the form after successful insertion
                $name = $gender = $specialization = '';
            } else {
                $message = "❌ Database Error: " . $stmt->error;
            }
            $stmt->close();

        } catch (Exception $e) {
            $message = "❌ General Error: " . $e->getMessage();
        }
    }
}

// -----------------------------------------------------------------
// === 3. PREPARE FORM VALUES (for post-submission sticky form behavior) ===
// -----------------------------------------------------------------
// We are using the POST values for sticky form submission on error, otherwise they'll be empty.
$current_name = $name ?? ($_POST['name'] ?? '');
$current_gender = $gender ?? ($_POST['gender'] ?? '');
$current_specialization = $specialization ?? ($_POST['specialization'] ?? '');

$conn->close(); // Close the database connection once processing is complete
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Coach Creation</title>
    <link rel="stylesheet" href="../assets/css/coach_update.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        .form-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px; /* Adjust spacing as needed */
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn-primary-link {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background-color: #f97316; /* Using the primary color from the provided CSS */
            color: white;
            border-radius: 6px;
            font-weight: 500;
            transition: background-color 0.3s, transform 0.2s;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .btn-primary-link:hover {
            background-color: orange;
            transform: translateY(-1px);
        }

        .btn-primary-link i {
            margin-right: 8px;
        }
        
        /* Ensure H2 is correctly styled when inside .form-header-flex */
        .form-header-flex h2 {
            margin: 0; /* Remove default margin that might disrupt alignment */
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
            <div class="profile-dropdown">
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
                <li><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations </a></li>
            </ul>
        </aside>


    <div class="form-container">
        <div class="form-header-flex">
            <h2> Adding Coach </h2>
            <a href="coach_list.php" class="btn-primary-link">
                <i class="fas fa-eye"></i> View All Coaches
            </a>
        </div>
        
        <?php 
            if ($message) {
                $class = 'warning';
                if (strpos($message, '✅') !== false) {
                    $class = 'success';
                } elseif (strpos($message, '❌') !== false) {
                    $class = 'error';
                }
                echo "<div class='message {$class}'>{$message}</div>";
            }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <input type="hidden" name="create_coach" value="1">

            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($current_name); ?>" required>

            <label for="gender">Gender:</label>
            <select id="gender" name="gender" required>
                <option value="" disabled <?php echo (empty($current_gender)) ? 'selected' : ''; ?>>Select Gender</option>
                <option value="Male" <?php echo ($current_gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($current_gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo ($current_gender == 'Other') ? 'selected' : ''; ?>>Other</option>
                <option value="Prefer Not To Say" <?php echo ($current_gender == 'Prefer Not To Say') ? 'selected' : ''; ?>>Prefer Not To Say</option>
            </select>

            <label for="specialization">Specialization:</label>
            <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($current_specialization); ?>" required>
            
            <input type="submit" value="Sign up New Coach">
        </form>
    </div>
</body>
</html>