<?php
// =======================================================================
// PHP SCRIPT START - DATA FETCH FOR EDIT FORM
// =======================================================================

session_start();

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Guest/index.php");
    exit;
}

// 2. Database Connection
if (file_exists('../backend/db.php')) {
    require_once '../backend/db.php'; 
    if (!isset($conn) || $conn->connect_error) {
        error_log("DB Connection Error: " . ($conn->connect_error ?? "Connection object missing."));
        die("System error: Could not connect to the database.");
    }
} else {
    die("FATAL ERROR: db.php not found.");
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
        // Handle invalid date format if necessary
        return 'Invalid Date';
    }
}


$userId = $_SESSION['user_id'];
$formData = [];
$membersId = null;
$displayAge = "N/A"; // Initialize new variable for age

// 3. Fetch Full User Profile Data
// This query joins the users table (for name/email) with the membership table (for all other details).
// We prioritize the data from the 'membership' table if it exists.
$sql_fetch = "
    SELECT 
        u.first_name, 
        u.last_name, 
        u.email,
        m.members_id, 
        m.gender,
        m.birth_date,
        m.address,
        m.contact,
        m.emergency_name,
        m.emergency_number,
        m.emergency_relation,
        m.medical_conditions,
        m.medical_details,
        m.medications,
        m.medications_details
    FROM users u 
    LEFT JOIN membership m ON u.id = m.user_id 
    WHERE u.id = ?
";

$stmt = $conn->prepare($sql_fetch);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: User profile data not found.");
}

$row = $result->fetch_assoc();

// Store the fetched data for pre-filling the form
$formData = array_map('htmlspecialchars', $row);

// ⭐ New Step: Calculate Age
if (!empty($row['birth_date'])) {
    $displayAge = calculateAge($row['birth_date']);
}


$membersId = $formData['members_id']; // Keep this ID, we need it for updates.

$stmt->close();
$conn->close();

// Check if a membership record exists (meaning they have completed registration)
$isMember = !empty($membersId);

// =======================================================================
// PHP SCRIPT END - HTML FORM DISPLAY START
// =======================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/memberregister.css"> 
</head>
<body>

    <header class="header">
        <div class="container header-flex">
            <div class="logo">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <nav class="nav-desktop">
                <a href="User.php"><i class="fas fa-home"></i> Home</a>
                <a href="member_register.php"><i class="fas fa-id-card"></i> Membership Registration</a>
                <div class="profile-dropdown">
                    <button class="profile-btn"><i class="fas fa-user"></i> <i class="fas fa-caret-down"></i></button>
                    <div class="dropdown-menu">
                        <a href="Profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </nav>
            <button onclick="toggleMenu()" class="menu-btn"><i class="fas fa-bars"></i></button>
        </div>
        <div id="mobileMenu" class="nav-mobile"> 
            <a href="User.php"><i class="fas fa-home"></i> Home</a>
            <a href="member_register.php"><i class="fas fa-id-card"></i> Membership Registration</a>
            <a href="Profile.php"><i class="fas fa-user"></i> </a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <main class="form-container">
        <h1><i class="fas fa-edit"></i> Edit My Profile</h1>
        
        <?php if (!$isMember): ?>
            <div >
                ⚠️ Some fields are missing because you haven't completed the initial yet. Please complete that first.
            </div>
        <?php endif; ?>

        <form id="editForm" action="../backend/update_profile.php" method="POST">
            
            <input type="hidden" name="userId" value="<?php echo $userId; ?>">
            <input type="hidden" name="membersId" value="<?php echo $membersId; ?>">

            <fieldset class="form-section fade-in">
                <legend class="section-title">1. Personal & Contact Details</legend>
                
                <div class="form-grid grid-2">
                    <div class="form-group">
                        <label for="firstName">First Name (Read Only)</label>
                        <input name="firstName" id="firstName" type="text" value="<?php echo $formData['first_name']; ?>" readonly />
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name (Read Only)</label>
                        <input name="lastName" id="lastName" type="text" value="<?php echo $formData['last_name']; ?>" readonly />
                    </div>
                    <div class="form-group">
                        <label for="email">Email (Read Only)</label>
                        <input name="email" id="email" type="email" value="<?php echo $formData['email']; ?>" readonly />
                    </div>

                    <div class="form-group">
                        <label for="birthDate">Date of Birth (Read Only)</label>
                        <input name="birthDate" id="birthDate" type="date" value="<?php echo $formData['birth_date']; ?>" readonly />
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age (Read Only)</label>
                        <input name="age" id="age" type="text" value="<?php echo $displayAge; ?>" readonly />
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="radio-group-flex">
                            <label><input type="radio" name="gender" value="Male" <?php echo ($formData['gender'] === 'Male' ? 'checked' : ''); ?> required /> Male</label>
                            <label><input type="radio" name="gender" value="Female" <?php echo ($formData['gender'] === 'Female' ? 'checked' : ''); ?> /> Female</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <input name="contact" id="contact" type="tel" value="<?php echo $formData['contact']; ?>" 
                               placeholder="e.g., 09xxxxxxxxx" pattern="[0-9]{11}" maxlength="11" inputmode="numeric" required />
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea name="address" id="address" rows="2" placeholder="Street, Barangay, City/Province" required><?php echo $formData['address']; ?></textarea>
                </div>
            </fieldset>

            <fieldset class="form-section fade-in">
                <legend class="section-title">2. Emergency Contact</legend>
                <div class="form-grid grid-3">
                    <div class="form-group">
                        <label for="emergencyName">Full Name</label>
                        <input name="emergencyName" id="emergencyName" type="text" value="<?php echo $formData['emergency_name']; ?>" placeholder="Emergency Contact's Name" required />
                    </div>
                    <div class="form-group">
                        <label for="emergencyNumber">Contact Number</label>
                        <input name="emergencyNumber" id="emergencyNumber" type="tel" value="<?php echo $formData['emergency_number']; ?>" 
                               placeholder="Emergency Contact's Number" pattern="[0-9]{11}" maxlength="11" inputmode="numeric" required />
                    </div>
                    <div class="form-group">
                        <label for="emergencyRelation">Relation</label>
                        <input name="emergencyRelation" id="emergencyRelation" type="text" value="<?php echo $formData['emergency_relation']; ?>" placeholder="e.g., Parent, Sibling" required />
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-section fade-in">
                <legend class="section-title">3. Health Declaration</legend>
                
                <div class="form-group question-group">
                    <label>Do you have any medical conditions?</label>
                    <div class="radio-group-flex">
                        <label><input type="radio" name="medicalConditions" value="yes" <?php echo ($formData['medical_conditions'] === 'yes' ? 'checked' : ''); ?> /> Yes</label>
                        <label><input type="radio" name="medicalConditions" value="no" <?php echo ($formData['medical_conditions'] !== 'yes' ? 'checked' : ''); ?> /> No</label>
                    </div>
                    <textarea 
                        id="medicalDetails" 
                        name="medicalDetails" 
                        rows="2"
                        placeholder="Please specify your illness (e.g., Asthma, High Blood Pressure, Diabetes)" 
                        style="margin-top:10px; display: <?php echo ($formData['medical_conditions'] === 'yes' ? 'block' : 'none'); ?>;"
                    ><?php echo $formData['medical_details']; ?></textarea>
                </div>

                <div class="form-group question-group">
                    <label>Are you taking any medications?</label>
                    <div class="radio-group-flex">
                        <label><input type="radio" name="medications" value="yes" <?php echo ($formData['medications'] === 'yes' ? 'checked' : ''); ?> /> Yes</label>
                        <label><input type="radio" name="medications" value="no" <?php echo ($formData['medications'] !== 'yes' ? 'checked' : ''); ?> /> No</label>
                    </div>
                    <textarea 
                        id="medicationsDetails" 
                        name="medicationsDetails" 
                        rows="2"
                        placeholder="Please specify your medications" 
                        style="margin-top:10px; display: <?php echo ($formData['medications'] === 'yes' ? 'block' : 'none'); ?>;"
                    ><?php echo $formData['medications_details']; ?></textarea>
                </div>
            </fieldset>

            <div class="form-actions fade-in">
                <button type="submit" class="btn-edit"><i class="fas fa-save"></i> Save Changes</button>
                <a href="Profile.php" class="btn cancel-btn"><i class="fas fa-times-circle"></i> Cancel</a>
            </div>
        </form>
    </main>

    <footer class="footer">
        </footer>

    <script src="../assets/js/edit_profile.js"></script> 
</body>
</html>