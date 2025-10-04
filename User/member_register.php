<?php
session_start();
// IMPORTANT: Adjust the path to db.php based on your actual file structure.
// This path assumes member_registration.php is in 'User/' and db.php is in 'backend/'.
include "../backend/db.php"; 

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Guest/Index.html");
    exit;
}

$userId = $_SESSION['user_id'];
$userData = [
    'first_name' => '',
    'last_name' => '',
    'email' => ''
];

// 2. Retrieve User Data
if (isset($conn) && $conn->connect_error) {
    // Log error, but proceed with empty data to avoid crashing the form
    error_log("DB Connection Error on form display: " . $conn->connect_error);
} else if (isset($conn)) {
    // Prepare statement to select first name, last name, and email
    $stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Use htmlspecialchars for security when displaying data
        $userData['first_name'] = htmlspecialchars($row['first_name']);
        $userData['last_name'] = htmlspecialchars($row['last_name']);
        $userData['email'] = htmlspecialchars($row['email']);
    }
    $stmt->close();
    $conn->close(); 
}

// Now, $userData contains the data for display.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Registration</title>
    <link rel="stylesheet" href="../assets/css/memberregister.css">
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
                <a href="user.php#home"><i class="fas fa-home"></i> Home</a>
                <a href="user.php#services"><i class="fas fa-dumbbell"></i> Services</a>
                <a href="member_registration.php"><i class="fas fa-id-card"></i> Membership Registration</a>
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

            <button onclick="toggleMenu()" class="menu-btn">
                <i class="fas fa-user"></i>
            </button>

        </div>

        <div id="mobileMenu" class="nav-mobile">
            <a href="user.php#home"><i class="fas fa-home"></i> Home</a>
            <a href="user.php#services"><i class="fas fa-dumbbell"></i> Services</a>
            <a href="member_registration.php"><i class="fas fa-id-card"></i> Membership Registration</a>
            <a href="user.php#about"><i class="fas fa-info-circle"></i> About Us</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../Guest/index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <main class="form-container">
        <h1>Membership Registration</h1>

        <form id="registrationForm" action="../backend/member_registration.php" method="POST" enctype="multipart/form-data">

            <fieldset class="form-section fade-in">
                <legend class="section-title"><i class="fas fa-user-circle"></i> 1. Personal & Contact Details</legend>
                
                <div class="form-grid grid-2">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input name="firstName" id="firstName" type="text" 
                               value="<?php echo $userData['first_name']; ?>" 
                               required readonly />
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input name="lastName" id="lastName" type="text" 
                               value="<?php echo $userData['last_name']; ?>" 
                               required readonly />
                    </div>
                    <div class="form-group">
                        <label for="birthDate">Date of Birth</label>
                        <input name="birthDate" id="birthDate" type="date" required />
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="radio-group-flex">
                            <label><input type="radio" name="gender" value="Male" required /> Male</label>
                            <label><input type="radio" name="gender" value="Female" /> Female</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input name="email" id="email" type="email" 
                           value="<?php echo $userData['email']; ?>" 
                           required readonly />
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea name="address" id="address" rows="2" placeholder="Street, Barangay, City/Province" required></textarea>
                </div>
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input name="contact" id="contact" type="tel" placeholder="e.g., 09xxxxxxxxx" pattern="[0-9]{11}" maxlength="11" inputmode="numeric" required />
                </div>
            </fieldset>

            <fieldset class="form-section fade-in">
                <legend class="section-title"><i class="fas fa-first-aid"></i> 2. Emergency Contact</legend>
                <div class="form-grid grid-3">
                    <div class="form-group">
                        <label for="emergencyName">Full Name</label>
                        <input name="emergencyName" id="emergencyName" type="text" placeholder="Emergency Contact's Name" required />
                    </div>
                    <div class="form-group">
                        <label for="emergencyNumber">Contact Number</label>
                        <input name="emergencyNumber" id="emergencyNumber" type="tel" placeholder="Emergency Contact's Number" pattern="[0-9]{11}" maxlength="11" inputmode="numeric" required />
                    </div>
                    <div class="form-group">
                        <label for="emergencyRelation">Relation</label>
                        <input name="emergencyRelation" id="emergencyRelation" type="text" placeholder="e.g., Parent, Sibling" required />
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-section fade-in">
                <legend class="section-title"><i class="fas fa-heartbeat"></i> 3. Health Declaration</legend>

                <div class="form-group question-group">
                    <label>Do you have any medical conditions?</label>
                    <div class="radio-group-flex">
                        <label><input type="radio" name="medicalConditions" value="yes" /> Yes</label>
                        <label><input type="radio" name="medicalConditions" value="no" checked /> No</label>
                    </div>
                    <textarea 
                        id="medicalDetails" 
                        name="medicalDetails" 
                        rows="2"
                        placeholder="Please specify your illness (e.g., Asthma, High Blood Pressure, Diabetes)" 
                        style="display:none; margin-top:10px;"
                    ></textarea>
                </div>

                <div class="form-group question-group">
                    <label>Are you taking any medications?</label>
                    <div class="radio-group-flex">
                        <label><input type="radio" name="medications" value="yes" /> Yes</label>
                        <label><input type="radio" name="medications" value="no" checked /> No</label>
                    </div>
                    <textarea 
                        id="medicationsDetails" 
                        name="medicationsDetails" 
                        rows="2"
                        placeholder="Please specify your medications" 
                        style="display:none; margin-top:10px;"
                    ></textarea>
                </div>
            </fieldset>
                    
            <fieldset class="form-section payment-section fade-in">
                <legend class="section-title"><i class="fas fa-dollar-sign"></i> 4. Payment & Verification</legend>
                    
                
                
                <div class="form-group">
                    <label for="gcashReference"><h3>GCash Number: #09314813756</h3></label>             
                    <label for="gcashReference">GCash Reference Number (13 Digits)</label>
                    <input name="gcashReference" id="gcashReference" type="text" placeholder="Enter GCash Reference Number" pattern="\d{13}" maxlength="13" inputmode="numeric" required />
                </div>
                
                <div class="form-group file-upload-group">
                    <label for="validIdUpload">Upload Valid ID (For Verification)
                        <i class="fas fa-info-circle info-icon" 
                            title="Accepted IDs: Driver’s License, Passport, National ID, PhilHealth, SSS, Voter’s ID, UMID, Postal ID"></i>
                    </label>
                    <input type="file" id="validIdUpload" name="validIdUpload" accept="image/*,.pdf" required />
                    <small>Accepted file types: JPG, PNG, PDF. Max size: 5MB.</small>
                </div>
            </fieldset>

            <div class="form-group checkbox fade-in">
                <label>
                    <input name="agree" type="checkbox" required />
                    I agree to the <a href="#" id="termscondition">terms and conditions</a>.
                </label>
            </div>

            <div class="form-actions fade-in">
                <button id="submit" type="submit"><i class="fas fa-paper-plane"></i> Submit Application</button>
                <a href="User.php" class="btn cancel-btn"><i class="fas fa-times-circle"></i> Cancel</a>
            </div>

            <p id="successMessage" class="success-message">Form submitted successfully!</p>
        </form>
    </main>

    <div id="termsModal" class="modal">
        <div class="modal-content">
            <span id="closeTermsBtn" class="close-btn" aria-label="Close Terms">&times;</span>
            <h2>Terms and Conditions</h2>
            <div class="terms-content">
                <p><strong>1.</strong> You should always consult your doctor before starting an exercise program...</p>
                <p><strong>2.</strong> I declare that I have consulted a healthcare professional...</p>
                <p><strong>3.</strong> I understand that there is a risk of injury associated with physical activity...</p>
                <p><strong>4.</strong> I hereby assume full responsibility for any and all injuries...</p>
            </div>
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
                <a href="User.php#home">Home</a>
                <a href="User.php#about">About Us</a>
                <a href="User.php#services">Services</a>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p> <i class="fas fa-map"></i> Unit 21, Landsdale Tower, QC</p>
                <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                <p><i class="fa-brands fa-google"></i> charlesgym@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
    </footer>

    <script src="../assets/js/memberregister.js"></script>
</body>
</html>