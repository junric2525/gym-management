<?php
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

session_start();
include __DIR__ . "/db.php"; 

// CRITICAL: Check for successful database connection
if (!isset($conn) || $conn->connect_error) {
    error_log("DB Connection Error on update: " . ($conn->connect_error ?? "Connection object not set."));
    die("A system error occurred. Please try again later.");
}

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $_POST['userId']) {
    echo "<script>alert('Authentication failed.'); window.location.href='../Guest/Index.html';</script>";
    exit;
}

// 2. Collect Data
$userId             = $_POST['userId'];
$membersId          = $_POST['membersId'];

// Personal Details
$contact            = $_POST['contact'] ?? '';
$birthDate          = $_POST['birthDate'] ?? '';
$gender             = $_POST['gender'] ?? '';
$address            = $_POST['address'] ?? '';

// Emergency
$emergencyName      = $_POST['emergencyName'] ?? '';
$emergencyNumber    = $_POST['emergencyNumber'] ?? '';
$emergencyRelation  = $_POST['emergencyRelation'] ?? '';

// Medical
$medicalConditions  = $_POST['medicalConditions'] ?? 'no';
$medicalDetails     = ($medicalConditions === 'yes') ? ($_POST['medicalDetails'] ?? '') : '';
$medications        = $_POST['medications'] ?? 'no';
$medicationsDetails = ($medications === 'yes') ? ($_POST['medicationsDetails'] ?? '') : '';


// 3. Update 'membership' table (Assuming the record already exists from registration)
if (!empty($membersId)) {
    $sql_update_membership = "
        UPDATE membership SET
            contact = ?,
            birth_date = ?,
            gender = ?,
            address = ?,
            emergency_name = ?,
            emergency_number = ?,
            emergency_relation = ?,
            medical_conditions = ?,
            medical_details = ?,
            medications = ?,
            medications_details = ?
        WHERE members_id = ? AND user_id = ?
    ";

    $stmt_membership = $conn->prepare($sql_update_membership);
    
    $stmt_membership->bind_param(
        "ssssssssssssi", // 12 strings, 1 int
        $contact,
        $birthDate,
        $gender,
        $address,
        $emergencyName,
        $emergencyNumber,
        $emergencyRelation,
        $medicalConditions,
        $medicalDetails,
        $medications,
        $medicationsDetails,
        $membersId, // WHERE condition 1
        $userId     // WHERE condition 2
    );

    if (!$stmt_membership->execute()) {
        error_log("Membership update failed: " . $stmt_membership->error);
        echo "<script>alert('❌ Error updating profile details. Please contact support.'); window.history.back();</script>";
        exit;
    }
    $stmt_membership->close();
}


$conn->close();

// Success redirect
echo "<script>alert('✅ Profile updated successfully!'); window.location.href='../User/Profile.php';</script>";
exit;
?>
