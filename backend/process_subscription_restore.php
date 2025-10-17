<?php
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// FILE: ../backend/process_subscription_restore.php - Conditional Duplication Check for Monthly/Daily Subscriptions (Final Version)

require_once 'db.php'; // Include your database connection file
session_start();

// Set the header to indicate a JSON response
header('Content-Type: application/json');

// 1. INITIAL VALIDATION CHECK 
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['history_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request method or missing ID."]);
    exit();
}

$history_id = intval($_POST['history_id']); 

$conn->begin_transaction();
$is_successful = false;
$error_message = '';

try {
    // --- STEP 1: Retrieve all necessary data (members_id, type, dates) ---
    $sql_get_data = "
        SELECT members_id, subscription_type, start_date, end_date
        FROM deleted_subscriptions 
        WHERE history_id = ?";
        
    $stmt_get_data = $conn->prepare($sql_get_data);
    $stmt_get_data->bind_param("i", $history_id);
    $stmt_get_data->execute();
    $result_data = $stmt_get_data->get_result();

    if ($result_data->num_rows === 0) {
        throw new Exception("Archive record not found."); 
    }

    $archived_data = $result_data->fetch_assoc();
    $members_id = $archived_data['members_id'];
    $sub_type = $archived_data['subscription_type'];
    $restored_start_date = $archived_data['start_date'];
    $restored_end_date = $archived_data['end_date'];
    $stmt_get_data->close();
    
    // --- STEP 2: CONDITIONAL DUPLICATION CHECK ---
    
    // !!! CRITICAL FIX: Convert the retrieved subscription type to lowercase for case-insensitive comparison !!!
    $check_sub_type = strtolower($sub_type); 

    if ($check_sub_type == 'monthly') {
        // --- LOGIC A: Monthly Plan (Date Overlap Check) ---
        
        $sql_check_overlap = "
            SELECT subscription_id, start_date, end_date
            FROM subscription 
            WHERE 
                members_id = ? AND 
                status = 'active' AND
                (
                    (DATE(?) >= start_date) AND (DATE(?) <= end_date)
                )";

        $stmt_check_overlap = $conn->prepare($sql_check_overlap);
        $stmt_check_overlap->bind_param("iss", $members_id, $restored_end_date, $restored_start_date);
        $stmt_check_overlap->execute();
        $result_overlap = $stmt_check_overlap->get_result();

        if ($result_overlap->num_rows > 0) {
            $overlapping_sub = $result_overlap->fetch_assoc();
            $overlapping_id = $overlapping_sub['subscription_id'];
            $overlapping_start = $overlapping_sub['start_date'];
            $overlapping_end = $overlapping_sub['end_date'];
            
            throw new Exception("Overlap found! Active Monthly Sub ID {$overlapping_id} ({$overlapping_start} to {$overlapping_end}) conflicts with restored dates.");
        }
        $stmt_check_overlap->close();
        
    } elseif ($check_sub_type == 'daily') {
        // --- LOGIC B: Daily Plan (Active Status/Members ID Check) ---
        
        $sql_check_active = "
            SELECT subscription_id
            FROM subscription 
            WHERE 
                members_id = ? AND 
                status = 'active'"; 

        $stmt_check_active = $conn->prepare($sql_check_active);
        $stmt_check_active->bind_param("i", $members_id);
        $stmt_check_active->execute();
        $result_active = $stmt_check_active->get_result();

        if ($result_active->num_rows > 0) {
            $active_id = $result_active->fetch_assoc()['subscription_id'];
            throw new Exception("Restore blocked: Member already has an ACTIVE Daily pass (ID: {$active_id}).");
        }
        $stmt_check_active->close();
        
    } else {
        // Safety catch for truly unknown types
        // Using $sub_type (original value) in the error message for easy debugging
        throw new Exception("Unknown subscription type: " . htmlspecialchars($sub_type) . ". Restoration aborted.");
    }
    
    // ----------------------------------------------------------------------
    // NO DUPLICATION FOUND: Proceed with the database transaction.
    // ----------------------------------------------------------------------

    // --- STEP 3: Restore data (INSERT INTO subscription) ---
    $sql_restore = "
        INSERT INTO subscription (
            members_id, subscription_type, gcash_reference_number, start_date, 
            end_date, status, created_at
        ) 
        SELECT 
            ds.members_id, ds.subscription_type, ds.gcash_reference_number, ds.start_date, 
            ds.end_date, 'active' AS status, NOW() AS created_at
        FROM deleted_subscriptions ds 
        WHERE ds.history_id = ?
    ";
    
    $stmt_restore = $conn->prepare($sql_restore);
    $stmt_restore->bind_param("i", $history_id);
    if (!$stmt_restore->execute()) {
        throw new Exception("Error restoring subscription to active table: " . $stmt_restore->error);
    }
    $stmt_restore->close();

    // --- STEP 4: Delete the history record ---
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
    // If any exception was thrown (including duplication/overlap), rollback
    $conn->rollback();
    $error_message = "Restoration Failed: " . $e->getMessage();
}

// 5. Final JSON Response
if ($is_successful) {
    echo json_encode(["status" => "success", "history_id" => $history_id, "message" => "Subscription successfully restored."]);
} else {
    echo json_encode(["status" => "error", "message" => $error_message]);
}
exit();
?>