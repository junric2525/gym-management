<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// FILE: ../backend/process_member_restore.php
require_once 'db.php'; 
session_start(); // Start session to potentially use for logging

// 1. VALIDATION CHECK
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['history_id'])) {
    header("Location: ../Admin/deleted_members_view.php?status=error&msg=invalid_request"); 
    exit();
}

$history_id = intval($_POST['history_id']); 

// --- CRITICAL PRE-CHECK: Retrieve original user_id ---
// We need the user_id from the archived record to check against the active table.
$sql_get_user = "SELECT user_id, original_members_id FROM deleted_members WHERE history_id = ?";
$stmt_get_user = $conn->prepare($sql_get_user);
$stmt_get_user->bind_param("i", $history_id);
$stmt_get_user->execute();
$result_user = $stmt_get_user->get_result();

if ($result_user->num_rows === 0) {
    // If history_id is invalid or record is missing
    header("Location: ../Admin/deleted_members_view.php?status=error&msg=archive_record_not_found"); 
    exit();
}

$archived_data = $result_user->fetch_assoc();
$user_id = $archived_data['user_id'];
$stmt_get_user->close();

// --- CRITICAL DUPLICATE CHECK: Check for an existing ACTIVE membership ---
// If the user already has a membership record, restoration of the old record is blocked.
$sql_check_duplicate = "SELECT members_id FROM membership WHERE user_id = ?";
$stmt_check_duplicate = $conn->prepare($sql_check_duplicate);
$stmt_check_duplicate->bind_param("i", $user_id);
$stmt_check_duplicate->execute();
$result_duplicate = $stmt_check_duplicate->get_result();

if ($result_duplicate->num_rows > 0) {
    // DUPLICATE FOUND: Block restoration
    $active_member = $result_duplicate->fetch_assoc();
    $active_members_id = $active_member['members_id'];
    $stmt_check_duplicate->close();

    $error_msg = "Restore blocked: User (ID: {$user_id}) already has an ACTIVE Membership (ID: {$active_members_id}).";
    header("Location: ../Admin/deleted_members_view.php?status=error&msg=" . urlencode($error_msg)); 
    exit();
}
$stmt_check_duplicate->close();

// ----------------------------------------------------------------------
// No active membership found. Safe to proceed with the transaction.
// ----------------------------------------------------------------------

$conn->begin_transaction();
$is_successful = false;

try {
    // --- STEP 1: Restore data (INSERT INTO membership) ---
    // SELECTs data using history_id and INSERTs into membership.
    $sql_restore = "
        INSERT INTO membership (
            members_id, user_id, gender, birth_date, address, contact, 
            emergency_name, emergency_number, emergency_relation, 
            medical_conditions, medical_details, medications, medications_details, 
            gcash_reference, validid_path, approved_at, expiration_date
        )
        SELECT 
            original_members_id, user_id, gender, birth_date, address, contact, 
            emergency_name, emergency_number, emergency_relation, 
            medical_conditions, medical_details, medications, medications_details, 
            gcash_reference, validid_path, approved_at, expiration_date
        FROM deleted_members 
        WHERE history_id = ?
    ";
    
    $stmt_restore = $conn->prepare($sql_restore);
    $stmt_restore->bind_param("i", $history_id);
    if (!$stmt_restore->execute()) {
        throw new Exception("Error restoring member to active table: " . $stmt_restore->error);
    }
    $stmt_restore->close();

    // --- STEP 2: Delete the history record ---
    $sql_clean = "DELETE FROM deleted_members WHERE history_id = ?";
    $stmt_clean = $conn->prepare($sql_clean);
    $stmt_clean->bind_param("i", $history_id);
    if (!$stmt_clean->execute()) {
        throw new Exception("Error cleaning history record: " . $stmt_clean->error);
    }
    $stmt_clean->close();

    $conn->commit();
    $is_successful = true;

} catch (Exception $e) {
    $conn->rollback();
    // Error redirect points back to the history view
    header("Location: ../Admin/deleted_members_view.php?status=error&msg=" . urlencode("DB Restoration Failed: " . $e->getMessage()));
    exit();
}

if ($is_successful) {
    // SUCCESS REDIRECT: Go back to the history view
    header("Location: ../Admin/deleted_members_view.php?status=restored");
    exit();
}
?>