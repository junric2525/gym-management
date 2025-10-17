<?php
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// subscription.php
session_start();

// --- DB CONFIG ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "gym";

$message = '';
$can_access_evaluation = false; // New flag for access control

// --- CONNECT DB ---
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// --- GET LOGGED-IN USER ---
$user_id = $_SESSION['user_id'] ?? null;
$is_authenticated = !empty($user_id);
$members_id = null;

// --- MEMBERSHIP CHECK ---
if ($is_authenticated) {
    $sql = "SELECT members_id, approved_at
            FROM membership
            WHERE user_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $members_id = $row['members_id'];
        if (!empty($row['approved_at'])) {
            $can_access_evaluation = true; // Approved and active!
        } else {
            // Found membership but not yet approved
            $message = "<div class='message error-message'>
                            Membership Pending. Your registration is awaiting admin approval.
                        </div>";
        }
    } else {
        // No membership record found
        $message = "<div class='message error-message'>
                        No membership record found. Please register first.
                    </div>";
    }
    $stmt->close();
} else {
    // Not logged in
    $message = "<div class='message error-message'>
                Authentication required. Please log in to proceed.
                </div>";
}
// Renaming $can_submit to $can_access_evaluation for clarity on this page.
// The core logic remains: it's only true if there's an approved_at timestamp.
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Evaluation</title>
    <link rel="stylesheet" href="../assets/css/gymevalchoice.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container header-flex">
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

    <?php if ($can_access_evaluation): ?>
    <div class="evaluation-container">
        <div class="eval-left">
            <img src="../assets/img/logo.png" alt="Charles Gym Logo" class="logo">
            <h1>Charles Gym</h1>
            <p>Evaluation</p>
        </div>
        <div class="eval-right">
            <button class="coach-btn">Coach Evaluation</button>
            <button class="gym-btn">Gym Evaluation</button>
        </div>
    </div>
    <?php else: ?>
    <div class="evaluation-container">
        <div class="eval-right access-denied-box">
            <h2 style="color: red; margin-bottom: 10px;">Access Denied</h2>
            <p style="margin-bottom: 20px;">You cannot access the evaluation features because your user account is not linked to an active membership.</p>
            <div style="padding: 15px; border: 1px solid #f5c6cb; background-color: #f8d7da; color: #721c24; border-radius: 5px;">
                <strong>To use this feature:</strong> Please ensure you have completed your membership registration and that it has been approved by the gym administration.
            </div>
            <?php echo $message; // Displaying specific error message (Pending/No Record/Logged Out) ?>
        </div>
    </div>
    <?php endif; ?>

    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-about">
                <h3>CHARLES GYM</h3>
                <p>World-class fitness training in a supportive and motivating environment.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="#">Home</a>
                <a href="#">About Us</a>
                <a href="#">Services</a>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-map-marker-alt"></i> Unit 21, Landsdale Tower, QC</p>
                <p> <i class="fas fa-phone"></i> (555) 123-4567</p>
                <p><i class="fa-brands fa-google"></i> charlesgym@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
    </footer>

    <script src="../assets/js/gymevalchoice.js"></script>
</body>
</html>