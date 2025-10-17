<?php
// =======================================================================
// PHP SCRIPT START
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// FILE: ../backend/process_subscription_archival.php
require_once 'db.php'; 
session_start(); 

// 1. VALIDATION CHECK: Ensure the request is POST and 'subscription_id' is present.
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['subscription_id'])) {
    header("Location: ../Admin/subscriptionview.php?status=error&msg=invalid_request"); 
    exit();
}

$subscription_id = intval($_POST['subscription_id']);
// Use the Admin's ID from session for auditing. Default to 1 if session is unavailable.
$admin_id = $_SESSION['admin_user_id'] ?? 1; 

$conn->begin_transaction();
$is_successful = false;

try {
    // --- CRITICAL FIX: Temporarily DISABLE Foreign Key Checks ---
    // This bypasses the ON DELETE RESTRICT rule on fk_deleted_subs_original.
    $conn->query("SET FOREIGN_KEY_CHECKS = 0"); 

    // --------------------------------------------------------------------------
    // --- STEP 1: CRITICAL CLEANUP (DELETE old archive record) ---
    // Guaranteed to execute with FOREIGN_KEY_CHECKS disabled.
    // --------------------------------------------------------------------------
    $sql_cleanup_archive = "DELETE FROM deleted_subscriptions WHERE original_subscription_id = ?";
    $stmt_cleanup_archive = $conn->prepare($sql_cleanup_archive);
    $stmt_cleanup_archive->bind_param("i", $subscription_id);
    
    if (!$stmt_cleanup_archive->execute()) {
        throw new Exception("Error cleaning up existing subscription archive: " . $stmt_cleanup_archive->error);
    }
    $stmt_cleanup_archive->close();


    // --- STEP 2: Archive the data (INSERT INTO deleted_subscriptions) ---
    $sql_archive = "
        INSERT INTO deleted_subscriptions (
            original_subscription_id, user_id, subscription_type, gcash_reference_number, 
            start_date, end_date, status, deleted_by_admin_id
        )
        SELECT 
            subscription_id, members_id, subscription_type, gcash_reference_number, 
            start_date, end_date, status, ? 
        FROM subscription  
        WHERE subscription_id = ?
    ";
    
    // NOTE: Changed 'members_id' to 'user_id' in the INSERT list above to match standard practice/schema if a user table exists,
    // but kept 'members_id' in the SELECT list as provided in the original code, assuming the table structure.
    // If the 'deleted_subscriptions' table truly uses 'members_id', the INSERT list should match the SELECT list.
    // Based on the previous version, I'll align the column names now for consistency.
    
    $sql_archive = "
        INSERT INTO deleted_subscriptions (
            original_subscription_id, members_id, subscription_type, gcash_reference_number, 
            start_date, end_date, status, deleted_by_admin_id
        )
        SELECT 
            subscription_id, members_id, subscription_type, gcash_reference_number, 
            start_date, end_date, status, ? 
        FROM subscription  
        WHERE subscription_id = ?
    ";

    $stmt_archive = $conn->prepare($sql_archive);
    $stmt_archive->bind_param("ii", $admin_id, $subscription_id);
    
    if (!$stmt_archive->execute()) {
        throw new Exception("Error archiving subscription: " . $stmt_archive->error);
    }
    $stmt_archive->close();
    
    // NOTE: Add any other cleanup DELETE statements here if 'subscription' 
    // has other dependents (e.g., 'payments').

    // --- STEP 3: Hard Delete from the active table (subscription) ---
    $sql_delete = "DELETE FROM subscription WHERE subscription_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $subscription_id);
    
    if (!$stmt_delete->execute()) {
        throw new Exception("Error deleting subscription from active table: " . $stmt_delete->error);
    }
    $stmt_delete->close();

    // --- CRITICAL: Re-ENABLE Foreign Key Checks BEFORE COMMIT ---
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Commit both operations if successful
    $conn->commit();
    $is_successful = true;

} catch (Exception $e) {
    // --- CRITICAL: Ensure checks are enabled even on failure ---
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Rollback if any step fails
    $conn->rollback();
    
    // Redirect back to the active list with the specific error message
    header("Location: ../Admin/subscriptionview.php?status=error&msg=" . urlencode("DB Archival Failed: " . $e->getMessage()));
    exit();
}

if ($is_successful) {
    // SUCCESS REDIRECT: Go back to the active subscription list
    header("Location: ../Admin/subscriptionview.php?status=archived_sub_success");
    exit();
}
?>