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

$user_id = $_SESSION['user_id']; // This is the user_id from the session, which is linked to a member.
$member_id = null; // Variable to hold the actual members_id
$coaches = []; // Array to hold the list of coaches
$message = '';
$messageType = ''; // 'success' or 'error'

// --- 1. Fetch Member ID ---
// Get the members_id associated with the logged-in user_id
$member_id_stmt = $conn->prepare("SELECT members_id FROM membership WHERE user_id = ?");
$member_id_stmt->bind_param("i", $user_id);
$member_id_stmt->execute();
$member_result = $member_id_stmt->get_result();

if ($member_row = $member_result->fetch_assoc()) {
    $member_id = $member_row['members_id'];
} else {
    $message = "Error: Could not find member ID for the logged-in user.";
    $messageType = 'error';
}
$member_id_stmt->close();

// --- 2. Fetch Coaches ---
$coaches_stmt = $conn->prepare("SELECT coach_id, name, specialization FROM coaches ORDER BY name");
$coaches_stmt->execute();
$coaches_result = $coaches_stmt->get_result();
$coaches = $coaches_result->fetch_all(MYSQLI_ASSOC);
$coaches_stmt->close();


// --- 3. Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $member_id !== null) {
    // Sanitize and validate input
    $coach_id = filter_input(INPUT_POST, 'coach_id', FILTER_VALIDATE_INT);
    $behavior_rating = filter_input(INPUT_POST, 'behavior_rating', FILTER_VALIDATE_INT);
    $teaching_rating = filter_input(INPUT_POST, 'teaching_rating', FILTER_VALIDATE_INT);
    $communication_rating = filter_input(INPUT_POST, 'communication_rating', FILTER_VALIDATE_INT);
    $opinion = trim($_POST['opinion']); // Opinion field

    // Simple validation
    if ($coach_id && $behavior_rating && $teaching_rating && $communication_rating) {
        // Prepare SQL INSERT statement
        $insert_stmt = $conn->prepare("
            INSERT INTO coach_evaluations (member_id, coach_id, behavior_rating, teaching_rating, communication_rating, opinion)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        // Bind parameters: (i = integer, s = string)
        $insert_stmt->bind_param(
            "iiiiis", 
            $member_id, 
            $coach_id, 
            $behavior_rating, 
            $teaching_rating, 
            $communication_rating, 
            $opinion
        );

        if ($insert_stmt->execute()) {
            $message = "Thank you! Your evaluation has been submitted successfully.";
            $messageType = 'success';
        } else {
            // Check for specific error like duplicate evaluation, etc.
            $message = "Error submitting evaluation: " . $conn->error;
            $messageType = 'error';
        }
        $insert_stmt->close();

    } else {
        $message = "Error: Please fill in all required fields (Coach and Ratings).";
        $messageType = 'error';
    }
}

$conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coach Evaluation</title>
  <link rel="stylesheet" href="../assets/css/coachevaluation.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .message {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-weight: bold;
    }
    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
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
    <div class="form-container" id="formContainer">
      <span class="close-btn" id="closeForm">&times;</span>
      <h2>Coach Evaluation Form</h2>

      <?php if ($message): ?>
          <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>
      
      <form id="coachEvaluationForm" method="POST" action="">
        <label for="idNumber"> Member ID:</label>
        <input 
            type="text" 
            id="idNumber" 
            value="<?php echo htmlspecialchars($member_id ?? 'Loading...'); ?>" 
            readonly 
            disabled>
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

        <label>Behavior</label>
        <div class="star-rating">
          <input type="radio" id="b_star5" name="behavior_rating" value="5" required><label for="b_star5">&#9733;</label>
          <input type="radio" id="b_star4" name="behavior_rating" value="4"><label for="b_star4">&#9733;</label>
          <input type="radio" id="b_star3" name="behavior_rating" value="3"><label for="b_star3">&#9733;</label>
          <input type="radio" id="b_star2" name="behavior_rating" value="2"><label for="b_star2">&#9733;</label>
          <input type="radio" id="b_star1" name="behavior_rating" value="1"><label for="b_star1">&#9733;</label>
        </div>
        
        <label>Teaching</label>
        <div class="star-rating">
          <input type="radio" id="t_star5" name="teaching_rating" value="5" required><label for="t_star5">&#9733;</label>
          <input type="radio" id="t_star4" name="teaching_rating" value="4"><label for="t_star4">&#9733;</label>
          <input type="radio" id="t_star3" name="teaching_rating" value="3"><label for="t_star3">&#9733;</label>
          <input type="radio" id="t_star2" name="teaching_rating" value="2"><label for="t_star2">&#9733;</label>
          <input type="radio" id="t_star1" name="teaching_rating" value="1"><label for="t_star1">&#9733;</label>
        </div>

        <label>Communication</label>
        <div class="star-rating">
          <input type="radio" id="c_star5" name="communication_rating" value="5" required><label for="c_star5">&#9733;</label>
          <input type="radio" id="c_star4" name="communication_rating" value="4"><label for="c_star4">&#9733;</label>
          <input type="radio" id="c_star3" name="communication_rating" value="3"><label for="c_star3">&#9733;</label>
          <input type="radio" id="c_star2" name="communication_rating" value="2"><label for="c_star2">&#9733;</label>
          <input type="radio" id="c_star1" name="communication_rating" value="1"><label for="c_star1">&#9733;</label>
        </div>


        <label for="opinion">Opinion:</label>
        <textarea id="opinion" name="opinion" rows="5" placeholder="Write your opinion here..."></textarea> <button type="submit">Submit Evaluation</button>
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

  <script src="../assets/js/coachevaluation.js"></script>
</body>
</html>