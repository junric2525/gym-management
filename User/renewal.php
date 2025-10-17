<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// PHP SCRIPT START
session_start();

// --- 0. Initial Setup and Login Check ---
// Check login and ensure user ID exists
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Database connection
if (file_exists('../backend/db.php')) {
    require_once '../backend/db.php'; 
    if (!isset($conn) || $conn->connect_error) {
        die("FATAL ERROR: Could not connect to the database: " . $conn->connect_error);
    }
} else {
    die("FATAL ERROR: db.php not found. Cannot connect to database.");
}

$user_id = $_SESSION['user_id'];
$errorMessage = '';
$isMember = false; // Flag to control the display of the form

// --- 1. Find the unique members_id associated with the logged-in user ---
$members_id_query = $conn->prepare("SELECT members_id FROM membership WHERE user_id = ?");
$members_id_query->bind_param("i", $user_id);
$members_id_query->execute();
$members_id_result = $members_id_query->get_result();

$members_id = null;
if ($members_id_row = $members_id_result->fetch_assoc()) {
    $members_id = $members_id_row['members_id'];
    $isMember = true; // User is confirmed as a member
}
$members_id_query->close();

// *** CRITICAL CHECK MODIFIED HERE ***
// If not a member, set the errorMessage to display on the page
if (!$isMember) {
    $errorMessage = "You must have an active membership linked to your account before you can proceed with renewal. Please contact gym administration.";
}
// *** END CRITICAL CHECK ***

if ($isMember && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate the GCash reference input
    $gcashReference = filter_input(INPUT_POST, 'gcash_reference', FILTER_SANITIZE_STRING);
    // Remove all non-digit characters for robust checking
    $cleanReference = preg_replace('/[^0-9]/', '', $gcashReference);

    if (empty($cleanReference)) {
        $errorMessage = "GCash Reference Number is required to process your renewal.";
    } elseif (strlen($cleanReference) !== 13) { 
        // Strict check for exactly 13 digits
        $errorMessage = "The GCash Reference Number must be exactly 13 digits.";
    } else {
        // 2. Update the membership table: set status to 'Pending' and store the reference
        $update_sql = "UPDATE membership SET renewal_status = 'Pending', gCash_reference = ? WHERE members_id = ?";
        $stmt = $conn->prepare($update_sql);
        
        if ($stmt) {
            $stmt->bind_param("si", $cleanReference, $members_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    $successMessage = "Your membership renewal has been submitted for approval (Reference: {$cleanReference}). Please wait for an admin to verify your payment.";
                    
                    // Redirect to profile with success message
                    header("Location: profile.php?message=" . urlencode($successMessage) . "&status=success");
                    exit();
                } else {
                    $errorMessage = "Your renewal status is already marked as pending, or no updates were made. Please wait for admin approval.";
                }
            } else {
                // Database execution failed
                $errorMessage = "Database error: Could not submit renewal request. Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errorMessage = "SQL preparation error: " . $conn->error;
        }
    }
}

// Close connection if it was opened successfully
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
// PHP SCRIPT END
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Renewal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap">
    <link rel="stylesheet" href="../assets/css/renewal.css"> 
</head>
<body>
    
    <header class="header">
        <div class="header-flex">
            <div class="logo">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <nav class="nav-desktop">
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../Guest/index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <?php if ($isMember): ?>
            <div class="renewal-card">
                <div class="plan-header">
                    <h2>Membership Renewal</h2>
                </div>
                
                <?php if ($errorMessage): ?>
                    <div class="message error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="price-display">
                    <span class="price-large">₱600.00</span> 
                    <span class="price-period">Yearly</span>
                </div>
                
                <div class="payment-confirmation">
                    <h3 style="color: #666;">Payment Confirmation</h3>
                    
                    <p class="gcash-instructions">
                        Please send payment via GCash, then enter the <strong>13-digit Reference Number</strong> below:
                    </p>

                    <div class="gcash-details">
                        <p>Renewal Fee: <strong>₱600.00</strong></p> 
                        <p>GCash Number: <strong>#09515948029</strong></p>
                        <p>Account Name: <strong>CHARLES GYM ACCOUNT</strong></p>
                    </div>
                    
                    <form action="renewal.php" method="POST" id="renewalForm">
                        <div class="form-group">
                            <input type="text" id="gcash_reference" name="gcash_reference" placeholder="e.g., 1234567890123" required maxlength="13" autocomplete="off">
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Submit Payment Reference
                        </button>
                    </form>
                </div>
            </div>
        <?php else: // Display a non-member error message that looks like the form area ?>
            <div class="renewal-card">
                <div class="plan-header">
                    <h2>Renewal Unavailable</h2>
                </div>
                <div class="message error-message" style="margin: 20px 0; padding: 20px; text-align: center; font-size: 1.1em;">
                    <i class="fas fa-user-times fa-3x" style="color: #dc3545; margin-bottom: 15px;"></i>
                    <p><strong><?php echo htmlspecialchars($errorMessage); ?></strong></p>
                    <p style="margin-top: 15px; font-size: 0.9em;">If you believe this is an error, please contact the gym administration for membership linkage.</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-about">
                <h3>CHARLES GYM</h3>
                <p>World-class fitness training in a supportive and motivating environment.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="#home">Home</a>
                <a href="#about">About Us</a>
                <a href="#services">Services</a>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-map"></i> Unit 21, Landsdale Tower, QC</p>
                <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                <p><i class="fa-brands fa-google"></i> charlesgym@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
    </footer>


    <script>
        // Set the current year in the footer dynamically
        document.getElementById('footerYear').textContent = new Date().getFullYear();
    </script>
    <script src="../assets/js/renewal.js"></script> 
</body>
</html>