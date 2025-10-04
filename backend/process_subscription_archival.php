<?php
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
    // --- STEP 1: Archive the data (INSERT INTO deleted_subscriptions) ---
    // Columns match your subscription and deleted_subscriptions tables:
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

    // --- STEP 2: Hard Delete from the active table (subscription) ---
    $sql_delete = "DELETE FROM subscription WHERE subscription_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $subscription_id);
    
    if (!$stmt_delete->execute()) {
        throw new Exception("Error deleting subscription from active table: " . $stmt_delete->error);
    }
    $stmt_delete->close();

    // Commit both operations if successful
    $conn->commit();
    $is_successful = true;

} catch (Exception $e) {
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