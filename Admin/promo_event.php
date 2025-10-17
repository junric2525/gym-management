<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// ===================================================================
// 1. INITIAL SETUP AND SECURITY
// ===================================================================

session_start();
// Include the database connection file (which sets up the $conn mysqli object)
include '../backend/db.php'; 

// CRITICAL SECURITY CHECK: Ensure only logged-in admins can access this page
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}

// Define configuration variables
$promo_id = 1; 
$upload_dir_relative_to_root = "uploads/promo/";
$message = ''; 
$error = false;

// 2. Define the absolute physical path for saving the file
// realpath() correctly resolves the directory structure.
$target_dir = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR . $upload_dir_relative_to_root; 

// ===================================================================
// 2. DIRECTORY CHECK AND PRE-FLIGHT ERROR HANDLING
// ===================================================================

// Attempt to create directory if missing
if (!is_dir($target_dir)) {
    // Attempt to create the directory recursively with full permissions (0777)
    if (mkdir($target_dir, 0777, true)) {
        $message = "SUCCESS: The missing upload directory was automatically created.";
        $error = false;
    } else {
        // Failed to create directory
        $message = "FATAL ERROR: The upload directory does not exist and could not be created. Expected path: " . htmlspecialchars($target_dir);
        $error = true;
    }
}

// Check for write permission only if no fatal error occurred yet
if (!$error && !is_writable($target_dir)) {
    $message = "FATAL ERROR: Target upload directory exists but is not writable. Check folder permissions.";
    $error = true;
}

// ===================================================================
// 3. HANDLE FORM SUBMISSION (UPLOAD LOGIC) - CORRECTED
// ===================================================================

if (isset($_POST["submit"]) && !empty($_FILES["promoImage"]["name"]) && !$error) {
    
    // --- 3.1. Image Validation and Preparation ---
    $file_name = basename($_FILES["promoImage"]["name"]);
    $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // CRITICAL: Check file extension validity first
    if (empty($imageFileType)) {
        $message = "File does not have a recognized extension (JPG, PNG, GIF required).";
        $error = true;
    }

    // Check if image file is an actual image
    $check = @getimagesize($_FILES["promoImage"]["tmp_name"]);
    if(!$error && $check === false) { 
        $message = "File is not a valid image.";
        $error = true;
    }

    // Check file size (e.g., limit to 5MB)
    if (!$error && $_FILES["promoImage"]["size"] > 5000000) {
        $message = "Sorry, your file is too large (max 5MB).";
        $error = true;
    }

    // Allow certain file formats
    if(!$error && !in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
        $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $error = true;
    }
    
    // Define final file paths ONLY IF NO VALIDATION ERRORS OCCURRED
    if (!$error) {
        // Use a fixed, canonical filename for the single banner
        $new_filename = 'current_promo.' . $imageFileType;
        $final_target_file = $target_dir . $new_filename; 
    }
    
    // --- 3.2. Upload and Database Update (using REPLACE INTO for UPSERT) ---
    if (!$error) {
        
        // Attempt to move the uploaded file
        if (move_uploaded_file($_FILES["promoImage"]["tmp_name"], $final_target_file)) {
            
            $db_path = $upload_dir_relative_to_root . $new_filename;
            
            // REPLACE INTO provides a clean UPSERT: it deletes the old row and inserts the new one if ID=1 exists.
            $sql_replace = "REPLACE INTO promotions (id, promo_image_path) VALUES (?, ?)";
            
            if ($stmt_replace = $conn->prepare($sql_replace)) {
                $stmt_replace->bind_param("is", $promo_id, $db_path); 

                if ($stmt_replace->execute()) {
                    // Check if a row was affected at all (1 for Insert, 2 for Replace)
                    if ($conn->affected_rows >= 1) { 
                        $message = "The promotion image has been **updated successfully!** 🎉";
                    } else {
                        $message = "SUCCESS: Image file uploaded, but the database path was already correct (no change needed).";
                    }
                } else {
                    $message = "Error replacing database record: " . $stmt_replace->error;
                    $error = true;
                }
                $stmt_replace->close();
            } else {
                   $message = "Database prepare error: " . $conn->error;
                   $error = true;
            }

        } else {
            // Report PHP upload error code for better debugging
            $upload_error_code = $_FILES["promoImage"]["error"];
            $message = "Sorry, there was an error moving the uploaded file (Code: {$upload_error_code}). Check server logs/permissions. Target: " . htmlspecialchars($final_target_file ?? 'N/A');
            $error = true;
        }
    }
}

// ===================================================================
// 4. FETCH CURRENT IMAGE PATH AND CLEANUP 
// ===================================================================

$current_path = '';
// Only query if connection is still open. We rely on the script ending to close the connection.
if ($conn) { 
    $result = $conn->query("SELECT promo_image_path FROM promotions WHERE id = 1");
    if ($result && $row = $result->fetch_assoc()) {
        $current_path = $row['promo_image_path'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Update Promotion Image</title>
    <link rel="stylesheet" href="../assets/css/promo_event.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                <li class="active"><a href="promo_event.php"><i class="fas fa-bullhorn"></i> Updating Promo</a></li>
                <li><a href="coach_evalmanage.php"><i class="fas fa-chart-line"></i> Evaluations</a></li>
            </ul>
        </aside>

        <main>
            <h2>Admin: Update Promotion Image</h2>

            

            <form action="promo_event.php" method="post" enctype="multipart/form-data">
                <label for="promoImage">Select new image to upload (JPG, PNG, GIF, max 5MB):</label>
                <input type="file" name="promoImage" id="promoImage" required>
                <br><br>
                <input type="submit" value="Upload & Update Promo" name="submit">
            </form>
        </main>
    </div>
    
    </body>
</html>