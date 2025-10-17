<?php
// =======================================================================
// PHP SCRIPT START - DATA RETRIEVAL AND VARIABLE DEFINITION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// 1. START SESSION AND CHECK LOGIN
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id']; 

// Use a unique session key based on user_id for the admin-set notification
$notification_key = "member_notification_{$user_id}";

// 🎯 CHECK FOR ADMIN-SET CANCELLATION NOTIFICATION
$notification_title = null;
$notification_text = null;
$notification_detail = null;
$temp_notification = null;

if (isset($_SESSION[$notification_key])) {
    $temp_notification = $_SESSION[$notification_key];
    
    // Set variables for JavaScript access
    $notification_title = htmlspecialchars($temp_notification['title'] ?? 'Notification');
    $notification_text = htmlspecialchars($temp_notification['message'] ?? 'An update occurred.');
    $notification_detail = htmlspecialchars($temp_notification['detail'] ?? '');
    
    // IMPORTANT: Clear the session variable immediately after retrieving it 
    // so the message doesn't pop up on every page refresh.
    unset($_SESSION[$notification_key]);
}


// 2. INCLUDE DATABASE CONNECTION
if (file_exists('../backend/db.php')) {
    require_once '../backend/db.php';
    if (!isset($conn) || $conn->connect_error) {
        error_log("Database Connection FAILED: " . ($conn->connect_error ?? "Connection object missing."));
        die("FATAL ERROR: Could not connect to the database. Please try again later.");
    }
} else {
    die("FATAL ERROR: db.php not found at ../backend/db.php");
}


/**
 * Calculates the current age based on the date of birth string.
 * @param string|null $birthDate The date of birth (e.g., '1990-01-15').
 * @return int|string The calculated age or 'N/A'.
 */
function calculateAge($birthDate) {
    if (!$birthDate || $birthDate === '0000-00-00') {
        return 'N/A';
    }
    try {
        $dob = new DateTime($birthDate);
        $now = new DateTime();
        $interval = $now->diff($dob);
        return $interval->y;
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}


// --- INITIALIZE ALL VARIABLES ---
$displayMemberID = "N/A (User ID: {$user_id})"; 
$member_id_for_query = null; 
$qr_code_data = "N/A"; // Default value for QR data
$displayName = "N/A";
$displayEmail = "N/A";
$displayContact = "N/A";
$displayDob = "N/A";
$displayAge = "N/A"; 
$displayAddress = "N/A";
$displayEmergencyName = "N/A";
$displayEmergencyNum = "N/A";
$displayMedConditions = "N/A";
$displayMedDetails = "N/A";
$displayMedications = "N/A";
$displayMedsDetails = "N/A";
$displayEmergencyRelation = "N/A"; 

$startDate = "N/A";
$expiryDate = "N/A";
$membershipStatus = "Inactive";
$hasMembership = false;
$subscription = null; 
$renewalStatus = null; 
$gCashReference = "N/A"; 
$userMembershipStatus = "N/A";
$allAppointments = [];


// 3. FETCH PERSONAL PROFILE DATA (Including Membership ID and User Status)
try {
    $sql_profile = "SELECT
        m.members_id,
        u.membership_status,
        CONCAT(u.first_name, ' ', u.last_name) AS full_name,
        u.email,
        m.contact,
        m.birth_date AS date_of_birth,
        m.address,
        m.emergency_name AS emergency_contact_name,
        m.emergency_number AS emergency_contact_number,
        m.emergency_relation AS emergency_contact_relation,
        m.medical_conditions,
        m.medical_details,
        m.medications,
        m.medications_details
        FROM users u
        LEFT JOIN membership m ON u.id = m.user_id
        WHERE u.id = ?"; 

    $stmt_profile = $conn->prepare($sql_profile);
    if ($stmt_profile === false) {
        error_log("SQL PREPARE ERROR (Profile): MySQL Error: " . $conn->error);
        die("An error occurred while fetching profile data. Please try again.");
    }

    $stmt_profile->bind_param("i", $user_id); 
    $stmt_profile->execute();
    $result_profile = $stmt_profile->get_result();

    if ($result_profile->num_rows > 0) {
        $row = $result_profile->fetch_assoc();

        $userMembershipStatus = htmlspecialchars($row['membership_status'] ?? "N/A"); 

        if (!empty($row['members_id'])) {
            $member_id_for_query = $row['members_id'];
            $displayMemberID = htmlspecialchars($row['members_id']);
            // ⭐ FIX APPLIED: Set the QR code data to be ONLY the numeric ID
            $qr_code_data = $member_id_for_query; 
        }
        
        $displayName = htmlspecialchars($row['full_name'] ?? "N/A");
        $displayEmail = htmlspecialchars($row['email'] ?? "N/A");
        $displayContact = htmlspecialchars($row['contact'] ?? "N/A");
        $displayDob = htmlspecialchars($row['date_of_birth'] ?? "N/A");
        
        // Calculation: Calculate Age
        $displayAge = calculateAge($row['date_of_birth'] ?? null); 
        
        $displayAddress = htmlspecialchars($row['address'] ?? "N/A");
        $displayEmergencyName = htmlspecialchars($row['emergency_contact_name'] ?? "N/A");
        $displayEmergencyNum = htmlspecialchars($row['emergency_contact_number'] ?? "N/A");
        $displayEmergencyRelation = htmlspecialchars($row['emergency_contact_relation'] ?? "N/A");
        $displayMedConditions = htmlspecialchars($row['medical_conditions'] ?? "N/A");
        $displayMedDetails = htmlspecialchars($row['medical_details'] ?? "N/A");
        $displayMedications = htmlspecialchars($row['medications'] ?? "N/A");
        $displayMedsDetails = htmlspecialchars($row['medications_details'] ?? "N/A");
    }
    $stmt_profile->close();

} catch (Exception $e) {
    error_log("PHP EXCEPTION during profile fetch: " . $e->getMessage());
    die("An unexpected error occurred. Please contact support.");
}


// 4. FETCH MEMBERSHIP AND SUBSCRIPTION DATA
if ($member_id_for_query) { 
    try {
        $query_id = $member_id_for_query;

        // A. Fetch Membership Status
        $sql_membership = "SELECT approved_at, expiration_date, 'Active' AS status, renewal_status, gCash_reference FROM membership WHERE members_id = ?";

        $stmt_membership = $conn->prepare($sql_membership);
        if ($stmt_membership === false) {
            error_log("SQL PREPARE ERROR (Membership Status): MySQL Error: " . $conn->error);
        } else {
            $stmt_membership->bind_param("i", $query_id);
            $stmt_membership->execute();
            $result_membership = $stmt_membership->get_result();

            if ($result_membership->num_rows > 0) {
                $membership_row = $result_membership->fetch_assoc();

                $startDate = htmlspecialchars($membership_row['approved_at']);
                $expiryDate = htmlspecialchars($membership_row['expiration_date']);
                $membershipStatus = "Active"; 
                $hasMembership = true;

                $renewalStatus = htmlspecialchars($membership_row['renewal_status'] ?? null);
                $gCashReference = htmlspecialchars($membership_row['gCash_reference'] ?? "N/A");
            }
            $stmt_membership->close();
        }

        // B. Fetch Latest Subscription Details
        $sql_subscription = "SELECT subscription_type, start_date, end_date, status FROM subscription WHERE members_id = ? ORDER BY start_date DESC LIMIT 1";

        $stmt_subscription = $conn->prepare($sql_subscription);
        if ($stmt_subscription === false) {
            error_log("SQL PREPARE ERROR (Subscription): MySQL Error: " . $conn->error);
        } else {
            $stmt_subscription->bind_param("i", $query_id);
            $stmt_subscription->execute();
            $result_subscription = $stmt_subscription->get_result();

            if ($result_subscription->num_rows > 0) {
                $subscription = $result_subscription->fetch_assoc();
            }
            $stmt_subscription->close();
        }

    } catch (Exception $e) {
        error_log("PHP EXCEPTION during status fetch: " . $e->getMessage());
    }
}


// 5. FETCH ALL COACH APPOINTMENT DATA
if ($member_id_for_query) { 
    try {
        $query_id = $member_id_for_query;

        $sql_appointments = "
            SELECT 
                DATE(a.appointment_datetime) AS appointment_date, 
                TIME_FORMAT(a.appointment_datetime, '%h:%i %p') AS appointment_time, 
                a.status,
                COALESCE(c.name, 'Coach N/A') AS coach_name,
                COALESCE(c.specialization, 'N/A') AS specialization 
            FROM appointments a
            LEFT JOIN coaches c ON a.coach_id = c.coach_id 
            WHERE a.members_id = ? AND a.status != 'Deleted'
            ORDER BY a.appointment_datetime ASC 
        ";

        $stmt_app = $conn->prepare($sql_appointments);
        if ($stmt_app === false) {
            error_log("SQL PREPARE ERROR (Appointment): MySQL Error: " . $conn->error);
        } else {
            $stmt_app->bind_param("i", $query_id);
            $stmt_app->execute();
            $result_app = $stmt_app->get_result();

            if ($result_app->num_rows > 0) {
                while ($row = $result_app->fetch_assoc()) {
                    $allAppointments[] = $row; // Store all statuses
                }
            }
            $stmt_app->close();
        }
    } catch (Exception $e) {
        error_log("PHP EXCEPTION during appointment fetch: " . $e->getMessage());
    }
}


// Close the connection
@$conn->close();

// =======================================================================
// PHP SCRIPT END - FINAL STATUS CALCULATION
// =======================================================================

// --- STATUS HELPER FUNCTIONS ---
function get_status_class($status) {
    switch (strtolower($status)) {
        case 'accepted':
        case 'active':
        case 'approved':
            return 'status-active';
        case 'cancelled':
        case 'rejected':
        case 'expired':
            return 'status-expired';
        case 'pending':
        default:
            return 'status-pending';
    }
}

function get_status_icon($status) {
    switch (strtolower($status)) {
        case 'accepted':
        case 'active':
        case 'approved':
            return '<i class="fas fa-check-circle"></i>';
        case 'cancelled':
        case 'rejected':
        case 'expired':
            return '<i class="fas fa-times-circle"></i>';
        case 'pending':
        default:
            return '<i class="fas fa-hourglass-half"></i>';
    }
}
// -------------------------------


$isExpired = false;
$isPendingRenewal = false;
$isApplicationRejected = ($userMembershipStatus === 'Rejected' && !$hasMembership); 

// 1. Check for expiration
if ($expiryDate !== 'N/A') {
    $currentTimestamp = time();
    $expiryTimestamp = strtotime($expiryDate);

    if ($expiryTimestamp < $currentTimestamp) {
        $isExpired = true;
        $membershipStatus = "Expired"; 
    }
}

// 2. Check for pending renewal status (highest priority status)
if ($renewalStatus === 'Pending') {
    $isPendingRenewal = true;
    $membershipStatus = "Pending Renewal"; 
}

// 3. Final status overrides
if ($renewalStatus === 'Rejected') {
    if (!$isExpired) {
        $membershipStatus = "Active (Renewal Rejected)";
    } else {
        $membershipStatus = "Expired (Renewal Failed)";
    }
} elseif ($isApplicationRejected) {
    $membershipStatus = "Application Rejected";
} 

// 4. Determine CSS class based on final status
$status_class = get_status_class($membershipStatus);

$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Member Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap">
    <link rel="stylesheet" href="/gym-management/assets/css/profile.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        .modal {
            display: none; /* Hidden by default */
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); /* Black w/ opacity */
            padding-top: 100px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* 5% from the top and centered */
            padding: 30px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 400px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            text-align: center;
        }
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close-btn:hover, .close-btn:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-icon {
            font-size: 40px;
            color: #dc3545; /* Red for cancellation/error */
            margin-bottom: 10px;
        }
        .modal-content h2 {
            margin-top: 0;
            color: #333;
        }
        /* Styling for the appointment listings */
        .appointment-listing {
            border-left: 5px solid var(--primary-color);
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .appointment-cancelled {
             border-left-color: #dc3545; /* Red border for cancelled */
             background-color: #fcebeb;
        }
        .appointment-pending {
             border-left-color: #ffc107; /* Yellow border for pending */
             background-color: #fff8e1;
        }
        
        /* === MODIFIED GRID & QR CODE STYLES === */
        
        /* The main details grid now only uses 2 columns (Membership + Appointments) */
        .details-grid {
             display: grid;
             grid-template-columns: repeat(2, 1fr); 
             gap: 20px;
             margin-bottom: 20px; /* Space before the new QR section */
        }
        
        /* New section for the QR code, spanning the full width */
        .full-width-section {
            width: 100%;
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #ffffff;
            text-align: center;
        }

        #memberQrCode {
            /* Center the QR code within its section */
            width: 200px; 
            height: 200px; 
            margin: 15px auto 10px auto;
            
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 5px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        /* Subscription details are moved back into Membership Status */
        .subscription-box {
            border-top: 1px solid #eee;
            margin-top: 15px;
            padding-top: 10px;
        }

        /* Mobile adjustments (if profile.css doesn't already handle this) */
        @media (max-width: 992px) {
            .details-grid {
                grid-template-columns: 1fr; /* Stack columns vertically on mobile */
            }
        }
    </style>
</head>
<body>
    
<div id="notificationModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('notificationModal').style.display='none'">&times;</span>
        <div class="modal-icon"><i class="fas fa-times-circle"></i></div>
        <h2 id="modalTitle"></h2>
        <p id="modalMessage"></p>
        <small id="modalDetail"></small>
        <button class="btn btn-primary mt-3" onclick="document.getElementById('notificationModal').style.display='none'">Acknowledge</button>
    </div>
</div>
<header class="header">
        <div class="container header-flex">

            <div class="logo">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
                <h1 class="logo-text">Charles Gym</h1>
            </div>

            <nav class="nav-desktop">
                <a href="user.php#home"><i class="fas fa-home"></i> Home</a>
                <a href="user.php#services"><i class="fas fa-dumbbell"></i> Services</a>
                <a href="member_register.php"><i class="fas fa-id-card"></i> Membership Registration</a>
                <a href="user.php#about"><i class="fas fa-info-circle"></i> About Us</a>

                <div class="profile-dropdown">
                    <button class="profile-btn">
                        <i class="fas fa-user"></i> <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </nav>

            <button onclick="toggleMenu()" class="menu-btn" aria-label="Toggle Mobile Menu">
                <i class="fas fa-bars"></i>
            </button>

            <div id="mobileMenu" class="nav-mobile">
                <a href="user.php#home"><i class="fas fa-home"></i> Home</a>
                <a href="user.php#services"><i class="fas fa-dumbbell"></i> Services</a>
                <a href="member_register.php"><i class="fas fa-id-card"></i> Membership Registration</a>
                <a href="user.php#about"><i class="fas fa-info-circle"></i> About Us</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
</header>

    <main class="profile-container" id="profile">
        <div class="profile-card">
            <h1 class="section-title"><i class="fas fa-user-circle"></i> My Profile</h1>
            
            <div class="quick-actions">
                <button id="burgerBtn" class="burger-btn"><i class="fas fa-bars"></i> Menu </button>
                <nav id="burgerNav" class="burger-nav">
                    <a href="subscription.php"><i class="fas fa-credit-card"></i> View Subscriptions</a>
                    <a href="renewal.php"><i class="fas fa-sync-alt"></i> Renew Membership</a>
                    <a href="coach_appointment.php"><i class="fas fa-calendar-alt"></i> Coach Appointment</a> 
                    <a href="gym_evalchoice.php"><i class="fas fa-dumbbell"></i> Evaluation</a>
                    <a href="invoice.php"><i class="fas fa-file-invoice"></i> View Invoices</a>
                </nav>
            </div>

            <?php if ($message): ?>
                <div class="message success-message">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($isApplicationRejected): ?>
                <div class="message error-message">
                    <i class="fas fa-times-circle"></i> Membership Application Rejected: Your membership application was rejected by the administration. You need to complete a new registration with correct information/payment to proceed.
                </div>
            <?php elseif (!$hasMembership && $userMembershipStatus !== 'Pending'): ?>
                <div class="message warning-message">
                    <i class="fas fa-exclamation-triangle"></i> You haven't completed your Membership Registration. Complete it to receive your official Member ID and benefits!
                </div>
            <?php endif; ?>
            
            <a href="edit_profile.php" class="btn-edit"><i class="fas fa-edit"></i> Update Personal Details</a>

            <div class="profile-info-section">
                <h3>Personal Information</h3>
                <div class="info-grid">
                    <p><strong>Member ID:</strong> <span><?php echo $displayMemberID; ?></span></p>
                    <p><strong>Name:</strong> <span><?php echo $displayName; ?></span></p>
                    <p><strong>Email:</strong> <span><?php echo $displayEmail; ?></span></p>
                    <p><strong>Contact:</strong> <span><?php echo $displayContact; ?></span></p>
                    <p><strong>Date of Birth:</strong> <span><?php echo $displayDob; ?></span></p>
                    <p><strong>Age:</strong> <span><?php echo $displayAge; ?></span></p>
                    <p><strong>Address:</strong> <span><?php echo $displayAddress; ?></span></p>
                </div>
            </div>

            <div class="profile-info-section">
                <h3>Emergency & Medical Details</h3>
                <div class="info-grid">
                    <p><strong>Emergency Contact:</strong> <span><?php echo $displayEmergencyName; ?></span></p>
                    <p><strong>Contact Number:</strong> <span><?php echo $displayEmergencyNum; ?></span></p>
                    <p><strong>Medical Conditions:</strong> <span><?php echo $displayMedConditions; ?></span></p>
                    <p><strong>Medical Details:</strong> <span><?php echo $displayMedDetails; ?></span></p>
                    <p><strong>Medications:</strong> <span><?php echo $displayMedications; ?></span></p>
                    <p><strong>Medication Details:</strong> <span><?php echo $displayMedsDetails; ?></span></p>
                </div>
            </div>

            <div class="combined-details">
                <h3>Membership, Subscription & Appointments</h3>
                <div class="details-grid">
                    
                    <div class="inner-box">
                        <h4>Membership Status</h4>

                        <div class="status-alert <?php echo $status_class; ?>">
                            <p><strong>Current Membership Status:</strong> <?php echo $membershipStatus; ?></p>
                            <?php if ($isExpired && !$isPendingRenewal): ?>
                                <p>Your membership has expired. Click below to renew and maintain access!</p>
                                <a href="renewal.php" class="btn-renew-action">
                                    <i class="fas fa-sync-alt"></i> Renew Membership Now
                                </a>
                            <?php elseif ($isPendingRenewal): ?>
                                <p class="pending-msg">
                                    <?php echo get_status_icon('Pending'); ?> Renewal payment submitted! We are verifying your GCash transaction. <br>
                                    Reference: <strong><?php echo $gCashReference; ?></strong>. Your membership will be active shortly after admin approval.
                                </p>
                            <?php elseif (!$hasMembership && $userMembershipStatus === 'Pending'): ?>
                                <p class="pending-msg">
                                    <?php echo get_status_icon('Pending'); ?> Your initial application is pending review. Please wait for an administrator to approve your details and payment.
                                </p>
                            <?php elseif (!$hasMembership && $userMembershipStatus === 'Rejected'): ?>
                                <p class="error">
                                    <?php echo get_status_icon('Rejected'); ?> Your application was rejected. Please re-register.
                                </p>
                            <?php elseif (!$hasMembership): ?>
                                <p class="warning">
                                    <i class="fas fa-exclamation-triangle"></i> You are registered as a user, but do not have an active membership record.
                                </p>
                            <?php endif; ?>
                        </div>
                        <p><strong>Start Date:</strong> <span><?php echo $startDate; ?></span></p>
                        <p><strong>Expiry Date:</strong> <span><?php echo $expiryDate; ?></span></p>
                        <p><strong> Status:</strong>
                            <span class="<?php echo $status_class; ?> status-tag">
                                <?php echo $membershipStatus; ?>
                            </span>
                        </p>
                        
                        <div class="subscription-box">
                            <h4>Latest Subscription</h4>
                            <?php if ($subscription): ?>
                            <p><strong>Type:</strong> <span><?php echo htmlspecialchars($subscription['subscription_type']); ?></span></p>
                                <p><strong>Period Start:</strong> <span><?php echo htmlspecialchars($subscription['start_date']); ?></span></p>
                                <p><strong>Period End:</strong> <span><?php echo $subscription['end_date'] ? htmlspecialchars($subscription['end_date']) : 'N/A'; ?></span></p>
                                <p><strong> Status:</strong>
                                    <span class="status-tag <?php echo get_status_class($subscription['status']); ?>">
                                        <?php echo get_status_icon($subscription['status']) . " " . htmlspecialchars($subscription['status']); ?>
                                    </span>
                                </p>
                            <?php else: ?>
                                <p class="warning"><i class="fas fa-exclamation-circle"></i> No active subscription found.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="inner-box coach-appointments-box">
                        <h4>Coach Appointments</h4>
                        
                        <?php if (!empty($allAppointments)): ?>
                            <?php foreach ($allAppointments as $appointment): 
                                $app_status = htmlspecialchars($appointment['status'] ?? 'Unknown');
                                $app_status_class = get_status_class($app_status);
                                $coach_name = htmlspecialchars($appointment['coach_name'] ?? 'Coach N/A');
                            ?>
                            <div class="appointment-listing appointment-<?php echo strtolower($app_status); ?>">
                                <p><strong>Status:</strong> 
                                    <span class="status-tag <?php echo $app_status_class; ?>">
                                        <?php echo get_status_icon($app_status) . " " . $app_status; ?>
                                    </span>
                                </p>
                                <p><strong>Coach:</strong> <span><?php echo $coach_name; ?></span></p>
                                <p><strong>Specialization:</strong> <span><?php echo htmlspecialchars($appointment['specialization'] ?? 'N/A'); ?></span></p> 
                                <p><strong>Date:</strong> <span><?php echo htmlspecialchars($appointment['appointment_date'] ?? 'N/A'); ?></span></p>
                                <p><strong>Time:</strong> <span><?php echo htmlspecialchars($appointment['appointment_time'] ?? 'N/A'); ?></span></p>
                            </div>
                            <?php endforeach; ?>
                            
                        <?php elseif (!$member_id_for_query): ?>
                            <p class="warning"><i class="fas fa-exclamation-circle"></i> Complete registration to book appointments.</p>
                        <?php else: ?>
                            <p class="info-message"><i class="fas fa-info-circle"></i> You have no appointments currently. </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="full-width-section">
                    <h4><i class="fas fa-qrcode"></i> Attendance Monitoring QR Code</h4>
                    
                    <?php if ($member_id_for_query): ?>
                        <h4 style="color: var(--primary-color);">Scan for Check-in/Check-out</h4>
                        <div id="memberQrCode"></div>
                       <input type="hidden" id="qr_data_source" value="<?php echo htmlspecialchars($qr_code_data); ?>" readonly>
                    <?php else: ?>
                        <div class="message warning-message" style="margin-top: 20px; font-size: 0.9em;">
                            <i class="fas fa-exclamation-triangle"></i> Complete your membership registration to generate your permanent QR code for attendance.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
    </main>

    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-about">
                <h3>CHARLES GYM</h3>
                <p>World-class fitness training in a supportive and motivating environment.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="User.php#home">Home</a>
                <a href="User.php#about">About Us</a>
                <a href="User.php#services">Services</a>
                <a href="faq.html">FAQ</a>
                <a href="terms_condition.html">Terms</a>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-map-marker-alt"></i> Unit 21, Landsdale Tower, QC</p>
                <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                <p><i class="fa-brands fa-google"></i> charlesgym@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
    </footer>

    <script src="../assets/js/profile.js"></script>
    <script>
      // QR Code Generation Logic
const qrDataSource = document.getElementById('qr_data_source');
const qrContainer = document.getElementById('memberQrCode');

if (qrDataSource && qrContainer) {
    const memberIdData = qrDataSource.value;
    
    if (memberIdData && memberIdData !== 'N/A') {
        
        // Use a short delay to ensure the DOM is fully settled
        setTimeout(() => {
            
            //  FIX: Absolutely clear all existing children/content
            qrContainer.innerHTML = ''; 
            
            new QRCode(qrContainer, {
                text: memberIdData, // e.g., "47"
                width: 200, 
                height: 200, 
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            console.log(`QR code generated for ${memberIdData} after timeout.`);
            
        }, 100); // 100 milliseconds delay
    }
}
    </script>
</body>
</html>