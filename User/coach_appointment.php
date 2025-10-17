<?php
// PHP Configuration and Setup
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Check for login session
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

// --- 1. Fetch User Data (Member ID and Full Name) ---
$member_id = null;
$member_name = '';

// SQL to join users and membership tables to get the required IDs and name
$user_sql = "
    SELECT 
        m.members_id, 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name
    FROM users u
    JOIN membership m ON m.User_id = u.Id
    WHERE u.Id = ?
";

$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $member_id = $user_data['members_id'];
    $member_name = $user_data['full_name'];
} else {
    // Set a friendlier error message for the display card
    $errorMessage = "No active membership found.";
}
$user_stmt->close();


// --- 2. Fetch Coaches from Database ---
$coaches = [];
// Only connect and fetch coaches if there is a member ID to avoid unnecessary queries
if ($member_id) {
    $sql = "SELECT coach_id, name, specialization FROM coaches ORDER BY name ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $coaches[] = $row;
        }
    }
    // Close the result set
    if ($result) {
        $result->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Coaching Session</title>
    <link rel="stylesheet" href="../assets/css/coachappointment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .no-membership-card {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .no-membership-card h2 {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        .no-membership-card .icon {
            color: #ffc107;
            font-size: 4em;
            margin-bottom: 20px;
        }
        .no-membership-card p {
            color: #6c757d;
            font-size: 1.1em;
            line-height: 1.6;
        }
        .no-membership-card .instruction {
            margin-top: 25px;
            padding: 15px;
            border: 1px solid #ffdddd;
            background-color: #fff8f8;
            border-radius: 8px;
            color: #a04000;
        }
    </style>
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

    <main class="container main-content">
        <h2>Coach Appointment</h2>

        <?php if ($member_id): ?>
        
            <?php if ($errorMessage): ?>
                <p style="color: red; padding: 10px; border: 1px solid red;"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>

            <form action="../backend/submit_appointment.php" method="POST" class="appointment-form">
                
                <label for="member_id">Member ID</label>
                <input type="number" id="member_id" name="member_id" 
                       value="<?php echo htmlspecialchars($member_id); ?>" 
                       required readonly>

                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" 
                       value="<?php echo htmlspecialchars($member_name); ?>" 
                       required readonly>

                <label for="coach_id">Select Coach</label>
                <select id="coach_id" name="coach_id" required>
                    <option value="">--Choose a Coach--</option>
                    
                    <?php foreach ($coaches as $coach): ?>
                        <option 
                            value="<?php echo htmlspecialchars($coach['coach_id']); ?>">
                            <?php echo htmlspecialchars($coach['name']); ?> (<?php echo htmlspecialchars($coach['specialization']); ?>)
                        </option>
                    <?php endforeach; ?>
                    
                </select>

                <label for="date">Date</label>
                <input type="date" id="date" name="date" required>

                <label for="time">Time Slot</label>
                <select id="time" name="time" required>
                    <option value="">--Choose a Time Slot--</option>
                    <option value="10:00:00">10:00 AM</option>
                    <option value="13:00:00">1:00 PM</option>
                    <option value="16:00:00">4:00 PM</option>
                </select>
                
                <button type="submit">Book Appointment</button>
            </form>
            
        <?php else: ?>
            <div class="no-membership-card">
                <h2>Access Denied</h2>
                <p>You cannot book a coaching session because your user account is not linked to an active membership.</p>
                <div class="instruction">
                    <p>
                        To use this feature: Please ensure you have completed your membership registration and that it has been approved by the gym administration.
                    </p>
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

    <script>
        // Set the current year in the footer
        document.getElementById("footerYear").textContent = new Date().getFullYear();
        
        // --- Prevent past dates from being selected ---
        const dateInput = document.getElementById("date");

        function setMinDate() {
            const today = new Date().toISOString().split("T")[0];
            if (dateInput) {
                dateInput.setAttribute("min", today);
            }
        }
        setMinDate();
    </script>
</body>
</html>