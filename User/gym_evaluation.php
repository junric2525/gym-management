<?php
// PHP Configuration and Setup
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// --- Initialization ---
$message = '';
$messageType = ''; // 'success' or 'error'

// Check for login session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// --- Database Connection ---
if (file_exists('../backend/db.php')) {
    require_once '../backend/db.php'; 
    if (!isset($conn) || $conn->connect_error) {
        $message = "FATAL ERROR: Could not connect to the database: " . $conn->connect_error;
        $messageType = 'error';
    }
} else {
    $message = "FATAL ERROR: db.php not found. Cannot connect to database.";
    $messageType = 'error';
}

// --- 1. HANDLE FORM SUBMISSION (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($conn) && $conn->connect_error === null) {
    
    // 1a. Sanitize and Validate Input
    $cleanliness = filter_input(INPUT_POST, 'cleanlinessRating', FILTER_VALIDATE_INT);
    $equipment = filter_input(INPUT_POST, 'equipmentRating', FILTER_VALIDATE_INT);
    $staff = filter_input(INPUT_POST, 'staffRating', FILTER_VALIDATE_INT);
    $opinion = trim($_POST['gymOpinion'] ?? '');
    
    // Check if ratings are valid (1-5)
    if ($cleanliness === false || $equipment === false || $staff === false ||
        $cleanliness < 1 || $cleanliness > 5 || 
        $equipment < 1 || $equipment > 5 || 
        $staff < 1 || $staff > 5) {
        
        $message = "Invalid rating data provided. All ratings must be between 1 and 5.";
        $messageType = 'error';
        // Note: Execution continues to the display section below
    } else {
        // 1b. Fetch the Secure Member ID (Must re-fetch the ID needed for the FK insertion)
        $member_id = null;
        $member_id_stmt = $conn->prepare("SELECT members_id FROM membership WHERE user_id = ?");
        $member_id_stmt->bind_param("i", $user_id);
        $member_id_stmt->execute();
        $member_result = $member_id_stmt->get_result();

        if ($member_row = $member_result->fetch_assoc()) {
            $member_id = $member_row['members_id'];
        }
        $member_id_stmt->close();
        
        if (is_null($member_id)) {
            $message = "Error: Could not securely verify member ID for the logged-in user. Evaluation not saved.";
            $messageType = 'error';
        } else {
            // 1c. Insert Data into the gym_evaluations Table
            $insert_stmt = $conn->prepare("INSERT INTO gym_evaluations 
                (member_id, cleanliness_rating, equipment_rating, staff_rating, opinion_text) 
                VALUES (?, ?, ?, ?, ?)");
                
            $insert_stmt->bind_param("iiiis", 
                $member_id, 
                $cleanliness, 
                $equipment, 
                $staff, 
                $opinion
            );
            
            if ($insert_stmt->execute()) {
                $message = "Thank you! Your evaluation has been submitted successfully.";
                $messageType = 'success';
            } else {
                error_log("SQL Error on evaluation submission: " . $insert_stmt->error);
                $message = "An error occurred while saving your evaluation: " . $insert_stmt->error;
                $messageType = 'error';
            }
            $insert_stmt->close();
        }
    }
}

// --- 2. FETCH MEMBER ID FOR DISPLAY (If connection is good) ---
$member_id_display = ''; 
if (isset($conn) && $conn->connect_error === null) {
    $member_id_stmt = $conn->prepare("SELECT members_id FROM membership WHERE user_id = ?");
    $member_id_stmt->bind_param("i", $user_id);
    $member_id_stmt->execute();
    $member_result = $member_id_stmt->get_result();

    if ($member_row = $member_result->fetch_assoc()) {
        $member_id_display = htmlspecialchars($member_row['members_id']);
    } else if (empty($message)) {
        // Only set this error if no other error has occurred
        $message = "Error: Could not find member ID for the logged-in user. You cannot submit an evaluation.";
        $messageType = 'error';
    }
    $member_id_stmt->close(); 
    $conn->close(); // Close connection after all database operations
}

// --- 3. HELPER FUNCTION FOR MESSAGE DISPLAY ---
function display_message($msg, $type) {
    if (!empty($msg)) {
        $class = $type === 'success' ? 'success' : 'error';
        echo "<div id='messageBox' class='message-box {$class}'>";
        echo htmlspecialchars($msg);
        echo "<span class='close-message-btn' onclick='document.getElementById(\"messageBox\").style.display=\"none\";'>&times;</span>";
        echo "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Evaluation</title>
    <link rel="stylesheet" href="../assets/css/gymevaluation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .message-box {
            padding: 15px;
            margin: 10px auto;
            border-radius: 8px;
            font-weight: bold;
            max-width: 500px;
            position: relative;
            text-align: center;
            z-index: 1000;
        }
        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .close-message-btn {
            position: absolute;
            top: 5px;
            right: 15px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
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

    <div class="main-wrapper">

        <div class="form-container">
            <span class="close-btn" onclick="closeForm()">&times;</span>
            <?php display_message($message, $messageType); ?>

            <h2>Gym Evaluation Form</h2>
            <form id="gymEvaluationForm" method="POST" action="gym_evaluation.php">
                <label for="gymIdNumber">Member ID:</label>
                <input 
                    type="text" 
                    id="gymIdNumber" 
                    name="gymIdNumber" 
                    value="<?php echo $member_id_display; ?>" 
                    readonly 
                    required
                >

                <label>Cleanliness and Hygiene</label>
                <div class="star-rating">
                    <input type="radio" id="clean5" name="cleanlinessRating" value="5" required><label for="clean5">&#9733;</label>
                    <input type="radio" id="clean4" name="cleanlinessRating" value="4"><label for="clean4">&#9733;</label>
                    <input type="radio" id="clean3" name="cleanlinessRating" value="3"><label for="clean3">&#9733;</label>
                    <input type="radio" id="clean2" name="cleanlinessRating" value="2"><label for="clean2">&#9733;</label>
                    <input type="radio" id="clean1" name="cleanlinessRating" value="1"><label for="clean1">&#9733;</label>
                </div>

                <label>Equipment Quality</label>
                <div class="star-rating">
                    <input type="radio" id="equip5" name="equipmentRating" value="5" required><label for="equip5">&#9733;</label>
                    <input type="radio" id="equip4" name="equipmentRating" value="4"><label for="equip4">&#9733;</label>
                    <input type="radio" id="equip3" name="equipmentRating" value="3"><label for="equip3">&#9733;</label>
                    <input type="radio" id="equip2" name="equipmentRating" value="2"><label for="equip2">&#9733;</label>
                    <input type="radio" id="equip1" name="equipmentRating" value="1"><label for="equip1">&#9733;</label>
                </div>

                <label>Staff and Service</label>
                <div class="star-rating">
                    <input type="radio" id="staff5" name="staffRating" value="5" required><label for="staff5">&#9733;</label>
                    <input type="radio" id="staff4" name="staffRating" value="4"><label for="staff4">&#9733;</label>
                    <input type="radio" id="staff3" name="staffRating" value="3"><label for="staff3">&#9733;</label>
                    <input type="radio" id="staff2" name="staffRating" value="2"><label for="staff2">&#9733;</label>
                    <input type="radio" id="staff1" name="staffRating" value="1"><label for="staff1">&#9733;</label>
                </div>

                <label for="gymOpinion">Opinion:</label>
                <textarea id="gymOpinion" name="gymOpinion" rows="5" placeholder="Write your opinion here..." required></textarea>

                <button type="submit" <?php echo empty($member_id_display) ? 'disabled' : ''; ?>>
                    Submit Evaluation
                </button>
                <?php if (empty($member_id_display)): ?>
                    <p style="color:red; font-size: 0.9em; text-align: center;">(Form disabled: Member ID could not be loaded.)</p>
                <?php endif; ?>
            </form>
        </div>
    </div>

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
                <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                <p><i class="fa-brands fa-google"></i> charlesgym@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
    </footer>

    <script>
        // Placeholder for JS functions like closeForm() and footer year
        function closeForm() {
            console.log('Form close function called.');
            // Implement navigation or hiding logic here
        }
        document.getElementById('footerYear').textContent = new Date().getFullYear();
    </script>
    <script src="../assets/js/gymevaluation.js"></script>
</body>
</html>