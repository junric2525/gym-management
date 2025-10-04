<?php
// FILE: ../backend/process_subscription_restore.php - MODIFIED FOR AJAX

require_once 'db.php'; 
session_start();

// Set the header to indicate a JSON response
header('Content-Type: application/json');

// 1. VALIDATION CHECK 
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['history_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request method or missing ID."]);
    exit();
}

$history_id = intval($_POST['history_id']); 

$conn->begin_transaction();
$is_successful = false;
$error_message = '';

try {
    // --- STEP 1: Restore data (INSERT INTO subscription) ---
    // FIX: Removed 'subscription_id' from INSERT list and 'original_subscription_id' from SELECT list.
    // This allows MySQL to auto-assign a new, unique 'subscription_id'.
    $sql_restore = "
        INSERT INTO subscription (
            members_id, 
            subscription_type, 
            gcash_reference_number, 
            start_date, 
            end_date, 
            status
        ) 
        SELECT 
            members_id, 
            subscription_type, 
            gcash_reference_number, 
            start_date, 
            end_date, 
            status
        FROM deleted_subscriptions 
        WHERE history_id = ?
    ";
    
    $stmt_restore = $conn->prepare($sql_restore);
    $stmt_restore->bind_param("i", $history_id);
    if (!$stmt_restore->execute()) {
        throw new Exception("Error restoring subscription to active table: " . $stmt_restore->error);
    }
    $stmt_restore->close();

    // --- STEP 2: Delete the history record ---
    $sql_clean = "DELETE FROM deleted_subscriptions WHERE history_id = ?";
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
    $error_message = "DB Restoration Failed: " . $e->getMessage();
}

if ($is_successful) {
    // SUCCESS JSON RESPONSE
    echo json_encode(["status" => "success", "history_id" => $history_id, "message" => "Subscription successfully restored."]);
} else {
    // ERROR JSON RESPONSE
    echo json_encode(["status" => "error", "message" => $error_message]);
}
exit(); // Ensure nothing else is outputted
?>