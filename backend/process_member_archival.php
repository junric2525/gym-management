<?php
// FILE: ../backend/process_member_archival.php
require_once 'db.php'; 
session_start(); // Assuming admin ID is stored in the session

// 1. VALIDATION CHECK (Must look for 'members_id' from the active list)
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['members_id'])) {
    // Redirect back to the active list on failure
    header("Location: ../Admin/membership_manage.php?status=error&msg=invalid_request"); 
    exit();
}

$member_id = intval($_POST['members_id']);
// Use the actual logged-in Admin's ID
$admin_id = $_SESSION['admin_user_id'] ?? 1; // Fallback to 1 if session ID is not set

$conn->begin_transaction();
$is_successful = false;

try {
    // --- STEP 1: Archive the data (INSERT INTO deleted_members) ---
    // (Ensure this SELECT matches the column order of your INSERT)
    $sql_archive = "
        INSERT INTO deleted_members (
            original_members_id, user_id, gender, birth_date, address, contact, 
            emergency_name, emergency_number, emergency_relation, 
            medical_conditions, medical_details, medications, medications_details, 
            gcash_reference, validid_path, approved_at, expiration_date, 
            deleted_by_admin_id
        )
        SELECT 
            members_id, user_id, gender, birth_date, address, contact, 
            emergency_name, emergency_number, emergency_relation, 
            medical_conditions, medical_details, medications, medications_details, 
            gcash_reference, validid_path, approved_at, expiration_date, 
            ? 
        FROM membership 
        WHERE members_id = ?
    ";
    
    $stmt_archive = $conn->prepare($sql_archive);
    $stmt_archive->bind_param("ii", $admin_id, $member_id);
    if (!$stmt_archive->execute()) {
        throw new Exception("Error archiving member: " . $stmt_archive->error);
    }
    $stmt_archive->close();

    // --- STEP 2: Hard Delete from the active table (membership) ---
    $sql_delete = "DELETE FROM membership WHERE members_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $member_id);
    if (!$stmt_delete->execute()) {
        throw new Exception("Error deleting member from active table: " . $stmt_delete->error);
    }
    $stmt_delete->close();

    $conn->commit();
    $is_successful = true;

} catch (Exception $e) {
    // CRITICAL FIX: The entire error redirect must be INSIDE the catch block.
    $conn->rollback();
    header("Location: ../Admin/membership_manage.php?status=error&msg=" . urlencode("DB Archival Failed: " . $e->getMessage()));
    exit();
}

if ($is_successful) {
    // SUCCESS REDIRECT: Go back to the active list
    header("Location: ../Admin/membership_manage.php?status=deleted_archived");
    exit();
}
?>
