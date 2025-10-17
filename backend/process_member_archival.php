<?php
// =======================================================================
// PHP SCRIPT START
// =======================================================================

// Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// FILE: ../backend/process_member_archival.php
require_once 'db.php'; 
session_start();

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
    // --- CRITICAL FIX: Temporarily DISABLE Foreign Key Checks ---
    // This bypasses the RESTRICT rule for cleanup (Step 1) and final DELETE (Step 5).
    $conn->query("SET FOREIGN_KEY_CHECKS = 0"); 

    // --------------------------------------------------------------------------
    // --- STEP 1: FIX CRITICAL FOREIGN KEY (DELETE old archive record) ---
    // This MUST run first. It removes any old, blocking record.
    // --------------------------------------------------------------------------
    $sql_cleanup_archive = "DELETE FROM deleted_members WHERE original_members_id = ?";
    $stmt_cleanup_archive = $conn->prepare($sql_cleanup_archive);
    $stmt_cleanup_archive->bind_param("i", $member_id);
    if (!$stmt_cleanup_archive->execute()) {
        throw new Exception("Error cleaning up existing archival records: " . $stmt_cleanup_archive->error);
    }
    $stmt_cleanup_archive->close();
    
    // --- STEP 2: Archive the data (INSERT new archive record) ---
    // Transfers the member's data to the archival table.
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
        throw new Exception("Error archiving member data: " . $stmt_archive->error);
    }
    $stmt_archive->close();

    // --------------------------------------------------------------------------
    // --- STEP 3 & 4: Clean up other dependencies (appointments, invoices) ---
    // (You must ensure ALL dependent tables are cleaned here)
    // --------------------------------------------------------------------------
    
    // CLEANUP APPOINTMENTS
    $sql_delete_appointments = "DELETE FROM appointments WHERE members_id = ?";
    $stmt_delete_appointments = $conn->prepare($sql_delete_appointments);
    $stmt_delete_appointments->bind_param("i", $member_id);
    if (!$stmt_delete_appointments->execute()) {
        throw new Exception("Error deleting member appointments: " . $stmt_delete_appointments->error);
    }
    $stmt_delete_appointments->close();
    
    // CLEANUP INVOICES
    $sql_delete_invoices = "DELETE FROM invoices WHERE members_id = ?";
    $stmt_delete_invoices = $conn->prepare($sql_delete_invoices);
    $stmt_delete_invoices->bind_param("i", $member_id);
    if (!$stmt_delete_invoices->execute()) {
        throw new Exception("Error deleting member invoices: " . $stmt_delete_invoices->error);
    }
    $stmt_delete_invoices->close();
    
    // --- STEP 5: Hard Delete from the active table (membership) ---
    $sql_delete = "DELETE FROM membership WHERE members_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $member_id);
    if (!$stmt_delete->execute()) {
        throw new Exception("Error deleting member from active table: " . $stmt_delete->error);
    }
    $stmt_delete->close();
    
    // --- CRITICAL: Re-ENABLE Foreign Key Checks BEFORE COMMIT ---
    $conn->query("SET FOREIGN_KEY_CHECKS = 1"); 

    $conn->commit();
    $is_successful = true;

} catch (Exception $e) {
    // --- CRITICAL: Ensure checks are enabled even on failure ---
    $conn->query("SET FOREIGN_KEY_CHECKS = 1"); 
    $conn->rollback();
    
    $full_error_msg = "Transaction failed on member ID " . $member_id . ": " . $e->getMessage();
    error_log($full_error_msg); 
    
    header("Location: ../Admin/membership_manage.php?status=error&msg=" . urlencode($full_error_msg));
    exit();
}

if ($is_successful) {
    header("Location: ../Admin/membership_manage.php?status=deleted_archived");
    exit();
}
?>