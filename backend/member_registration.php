<?php
session_start();
// Assuming this script is in the 'backend' folder and 'db.php' is also there.
include __DIR__ . "/db.php"; 

// CRITICAL: Check for successful database connection
if (!isset($conn) || $conn->connect_error) {
    error_log("DB Connection Error: " . (isset($conn) ? $conn->connect_error : "Connection object not set."));
    // NOTE: Replace alert() with a custom modal in production environments.
    echo "<script>alert('A system error occurred. Please try again later.'); window.history.back();</script>";
    exit;
}

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    // NOTE: Replace alert() with a custom modal in production environments.
    echo "<script>alert('You must log in first.'); window.location.href='../Guest/Index.html';</script>";
    exit;
}

$userId = $_SESSION['user_id'];

// Double-check user exists in users table (This is good practice)
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // NOTE: Replace alert() with a custom modal in production environments.
    echo "<script>alert('User not found.'); window.location.href='../Guest/Index.html';</script>";
    exit;
}
$stmt->close();


// 2. DUPLICATE MEMBERSHIP CHECK START

// 2.1. Check the permanent 'membership' table (for approved members)
$stmt_check = $conn->prepare("SELECT members_id FROM membership WHERE user_id = ?");
$stmt_check->bind_param("i", $userId);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // NOTE: Replace alert() with a custom modal in production environments.
    echo "<script>alert('🛑 You are already an approved member. Cannot submit a new application.'); window.location.href='../User/User.php';</script>";
    exit;
}
$stmt_check->close();

// 2.2. Check the temporary 'membership_temp' table (for pending applications)
$stmt_check_temp = $conn->prepare("SELECT members_id FROM membership_temp WHERE user_id = ?");
$stmt_check_temp->bind_param("i", $userId);
$stmt_check_temp->execute();
$result_check_temp = $stmt_check_temp->get_result();

if ($result_check_temp->num_rows > 0) {
    // NOTE: Replace alert() with a custom modal in production environments.
    echo "<script>alert('⚠️ You have a pending membership application. Please wait for admin approval.'); window.location.href='../User/User.php';</script>";
    exit;
}
$stmt_check_temp->close();

// 3. Collect and Sanitize POST Data
$firstName          = $_POST['firstName'] ?? '';
$lastName           = $_POST['lastName'] ?? '';
$email              = $_POST['email'] ?? ''; 
$gender             = $_POST['gender'] ?? '';
$birthDate          = $_POST['birthDate'] ?? ''; 
$address            = $_POST['address'] ?? '';
$contact            = $_POST['contact'] ?? '';

$emergencyName      = $_POST['emergencyName'] ?? '';
$emergencyNumber    = $_POST['emergencyNumber'] ?? '';
$emergencyRelation  = $_POST['emergencyRelation'] ?? '';
$medicalConditions  = $_POST['medicalConditions'] ?? 'no'; 
$medicalDetails     = $_POST['medicalDetails'] ?? ''; 
$medications        = $_POST['medications'] ?? 'no';
$medicationsDetails = $_POST['medicationsDetails'] ?? ''; 
$gcashReference     = $_POST['gcashReference'] ?? ''; 

// Combine names for the single 'user_name' column in the 'users' table
// NOTE: This variable is now unused but the initial logic is kept for reference.
$userName = trim($firstName . ' ' . $lastName); 


// 4. Input Validation 
if (empty(trim($firstName)) || empty(trim($lastName))) {
    echo "<script>alert('First Name and Last Name are required.'); window.history.back();</script>";
    exit;
}
if (empty(trim($email)) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('A valid Email is required.'); window.history.back();</script>";
    exit;
}
if (empty(trim($birthDate))) {
    echo "<script>alert('Please provide your Birth Date.'); window.history.back();</script>";
    exit;
}
if (empty(trim($address))) {
    echo "<script>alert('Please provide your Address.'); window.history.back();</script>";
    exit;
}
if (empty(trim($contact))) {
    echo "<script>alert('Please provide your Contact Number.'); window.history.back();</script>";
    exit;
}
if ($gender !== "Male" && $gender !== "Female") {
    echo "<script>alert('Please select your gender.'); window.history.back();</script>";
    exit;
}
// 4.3. GCash Reference Number validation
if (!preg_match('/^\d{13}$/', $gcashReference)) {
    echo "<script>alert('GCash Reference Number must be exactly 13 digits.'); window.history.back();</script>";
    exit;
}


// 5. Update user profile data in the 'users' table
// FIX: Removed profile-specific fields (gender, birth_date, address, contact) from the 'users' update
// as they are causing 'Unknown column' errors, suggesting they belong only in the
// 'membership_temp' or a dedicated 'profiles' table. We keep name and email updates.
$stmt_update_user = $conn->prepare("
    UPDATE users SET
        first_name = ?, last_name = ?, email = ?
    WHERE id = ?
");

// FIX: Updated bind parameters and type string to match the reduced number of columns.
$stmt_update_user->bind_param(
    "sssi", // 3 's' for strings (name, email) + 1 'i' for userId
    $firstName,
    $lastName,
    $email,
    $userId
);

if (!$stmt_update_user->execute()) {
    error_log("User profile update failed: " . $stmt_update_user->error);
    echo "<script>alert('❌ Error updating your profile before submission. Please try again.'); window.history.back();</script>";
    exit;
}
$stmt_update_user->close();


// 6. Handle File Upload (No changes here, logic is sound)
$validIdPath = null;
if (isset($_FILES['validIdUpload']) && $_FILES['validIdUpload']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['validIdUpload']['tmp_name'];
    $fileName    = basename($_FILES['validIdUpload']['name']);
    $fileSize    = $_FILES['validIdUpload']['size'];
    $fileType    = mime_content_type($fileTmpPath);

    // Size check (5MB max)
    if ($fileSize > 5 * 1024 * 1024) {
        echo "<script>alert('File is too large. Max 5MB allowed.'); window.history.back();</script>";
        exit;
    }

    // File type check
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($fileType, $allowedTypes)) {
        echo "<script>alert('Invalid file type. Only JPG, PNG, or PDF allowed.'); window.history.back();</script>";
        exit;
    }

    // Save file
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $newFileName = uniqid("validID_", true) . "_" . $fileName;
    $destPath    = $uploadDir . $newFileName;

    if (!move_uploaded_file($fileTmpPath, $destPath)) {
        error_log("File upload failed for user " . $userId);
        echo "<script>alert('File upload failed.'); window.history.back();</script>";
        exit;
    }
    $validIdPath = "uploads/" . $newFileName;
}

// 7. Insert into membership_temp (pending verification)
// FIX: Removed first_name, last_name, email, gender, birth_date, address as they belong only in the 'users' table.
// The membership_temp table only stores the application-specific details.
$stmt = $conn->prepare("
    INSERT INTO membership_temp 
    (user_id, contact, 
     emergency_name, emergency_number, emergency_relation, 
     medical_conditions, medical_details, medications, medications_details, 
     gcash_reference, validid_path, status, expiration_date, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW())
");


// 8. Bind and Execute
$stmt->bind_param(
    "issssssssss", // 1 'i' for userId, 10 's' for strings
    $userId,
    $contact,
    $emergencyName, 
    $emergencyNumber, 
    $emergencyRelation, 
    $medicalConditions, 
    $medicalDetails, 
    $medications,
    $medicationsDetails, 
    $gcashReference, 
    $validIdPath 
);

if ($stmt->execute()) {
    // NOTE: Replace alert() with a custom modal in production environments.
    echo "<script>alert('✅ Membership application submitted! Waiting for admin verification.'); window.location.href='../User/User.php';</script>";
} else {
    // Log the error for debugging
    error_log("Membership insert error: " . $stmt->error);
    // NOTE: Replace alert() with a custom modal in production environments.
    echo "<script>alert('❌ Error submitting application. Please check your form data or contact support.'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
?>
