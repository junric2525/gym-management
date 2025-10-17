<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// FILE: ../backend/delete_subscription.php - Permanently deletes SUBSCRIPTION History Record (Purge)

require_once 'db.php'; 
session_start();

// Set the header to indicate a JSON response
header('Content-Type: application/json');

// 1. VALIDATION AND SECURITY 🛡️
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

// Expecting 'history_id' from the deleted_subscriptions table
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['history_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request method or missing history ID."]);
    exit();
}

$history_id = intval($_POST['history_id']); 

$is_successful = false;
$error_message = '';

try {
    // --- STEP 1: PERMANENTLY DELETE the SUBSCRIPTION history record ---
    
    // ACTION: DELETE from the 'deleted_subscriptions' table
    $sql_purge = "DELETE FROM deleted_subscriptions WHERE history_id = ?";
    
    $stmt_purge = $conn->prepare($sql_purge);
    $stmt_purge->bind_param("i", $history_id);

    if (!$stmt_purge->execute()) {
        throw new Exception("Error purging subscription history record: " . $stmt_purge->error);
    }

    // Check if a row was actually deleted (This is what failed previously)
    if ($stmt_purge->affected_rows === 0) {
        throw new Exception("History ID not found or already purged.");
    }
    
    $stmt_purge->close();

    $is_successful = true;

} catch (Exception $e) {
    // If an error occurs, capture the message
    $error_message = "DB Purge Failed: " . $e->getMessage();
} finally {
    // Ensure the connection is closed
    if (isset($conn)) $conn->close();
}

// --- JSON RESPONSE ---
if ($is_successful) {
    echo json_encode([
        "status" => "success", 
        "history_id" => $history_id, 
        "message" => "Subscription history successfully purged."
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => $error_message
    ]);
}
exit();
?>