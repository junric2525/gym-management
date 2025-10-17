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

// --- CONNECT DB ---
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// --- GET LOGGED-IN USER ---
$user_id = $_SESSION['user_id'] ?? null; 
$is_authenticated = !empty($user_id);
$can_submit = false;
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
            $can_submit = true; // approved!
        } else {
            $message = "<div class='message error-message'>
                        Membership Pending. Your registration is awaiting admin approval.
                        </div>";
        }
    } else {
        $message = "<div class='message error-message'>
                    No membership record found. Please register first.
                    </div>";
    }
    $stmt->close();
} else {
    $message = "<div class='message error-message'>
                Authentication required. Please log in to proceed.
                </div>";
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $can_submit) {
    $billing_cycle = $_POST['billing-cycle'] ?? 'monthly';
    $gcash_ref = $_POST['gcash_reference'] ?? '';

    // Amount
    $amount = ($billing_cycle == 'monthly') ? 1000.00 : 130.00;

    // --- NEW VALIDATION LOGIC ---
    $is_valid = true;
    
    // REQUIRE GCash Ref ONLY for Monthly
    if ($billing_cycle === 'monthly') {
        if (empty($gcash_ref) || strlen($gcash_ref) !== 13 || !ctype_digit($gcash_ref)) {
            $message .= "<div class='message error-message'>
                        Invalid GCash Reference Number for Monthly Plan. Must be 13 digits.
                        </div>";
            $is_valid = false;
        }
    }
    // Note: Daily subscriptions ($billing_cycle === 'daily') will automatically pass this check 
    // even if $gcash_ref is empty, which is what you want.

    if ($is_valid) { // Proceed with database insertion
        $status = 'pending_confirmation';
        // For 'daily' payments, it's possible to set a default $gcash_ref like 'N/A' 
        // if the database column does not allow NULL. Using the empty string from $gcash_ref
        // is generally fine if the column allows NULL or an empty string.

        $sql = "INSERT INTO temporary_subscription 
                (members_id, billing_cycle, amount, gcash_reference, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdss", $members_id, $billing_cycle, $amount, $gcash_ref, $status);

        if ($stmt->execute()) {
            $message = "<div class='message success-message'>
                        Subscription initiated for Member ID: <strong>$members_id</strong>! 
                        Payment of ₱" . number_format($amount, 2) . " ($billing_cycle) is pending confirmation. 
                        " . ($billing_cycle === 'monthly' ? "GCash Ref: <strong>$gcash_ref</strong>." : "No GCash reference required for Daily Pass.") . "
                        </div>";
        } else {
            $message .= "<div class='message error-message'>
                          Error initiating payment. Database error: " . $stmt->error . "
                          </div>";
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charles Gym Subscription</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/gym-management/assets/css/subscription.css">
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

    <div class="pricing-container">
        <form action="subscription.php" method="POST" class="plan-card">
            <?php echo $message; ?>

            <h3>Subscription Plan</h3>

            <?php if ($can_submit): ?>
                <div class="price-options">
                    <input type="radio" id="monthly" name="billing-cycle" value="monthly" checked>
                    <label for="monthly">Monthly Access (₱1000)</label>

                    <input type="radio" id="daily" name="billing-cycle" value="daily">
                    <label for="daily">Daily Pass/Walk in (₱130)</label>
                </div>

                <p class="price" id="current-price">₱1000</p>
                <p class="cycle-info" id="cycle-label">per month</p>

                <ul class="features">
                    <li><i class="fa-solid fa-dumbbell"></i> Full Access to Gym Equipment</li>
                    <li><i class="fa-solid fa-door-closed"></i></i> Full Access to Locker</li>  
                    <li><i class="fa-solid fa-shower"></i> Full Access to Shower</li>
                </ul>

                <div class="gcash-details">
                    <p>GCash Number: <strong>#09153161742</strong></p>
                    <p>Account Name: <strong>CHARLES GYM ACCOUNT</strong></p>
                </div>

                <div class="payment-details">
                    <h4>Payment Confirmation</h4>
                    <h3>If You choose daily plan you don't have to type Gcash Reference</h3>
                    <p>Please send payment via GCash, then enter the 13-digit Reference Number below:</p>
                    <input 
                        type="text" 
                        id="gcash_reference" 
                        name="gcash_reference" 
                        placeholder="Enter your 13-digit GCash Reference Number" 
                        required 
                        maxlength="13" 
                        autocomplete="off"
                    >
                </div>
                
                <button type="submit" class="subscribe-button">Submit Payment Reference</button>
            <?php else: ?>
                <div style="padding: 20px; background-color: #f7f7f7; border-radius: 8px; margin-top: 20px;">
                    <p>Your subscription form will appear here once your membership application is approved.</p>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-about">
                <h3>CHARLES GYM</h3>
                <p>World-class fitness training in a supportive and motivating environment.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="#">Home</a>
                <a href="#">About Us</a>
                <a href="#">Services</a>
                <a href="faq.html">FAQ</a>
                <a href="terms_condition.html">Terms</a>
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

    <script src="../assets/js/subscription.js" defer></script>
</body>
</html>
