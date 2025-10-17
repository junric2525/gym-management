<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

session_start();
// IMPORTANT: Adjust the path to your database connection file if necessary
include 'db.php'; 

// --- Configuration ---
$redirect_url = "../Admin/subscriptionview.php";

// ✅ 1. VERIFY ADMIN ACCESS
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/Index.html");
    exit();
}

// ✅ 2. VALIDATE INPUT
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'], $_POST['subscription_id'])) {
    header("Location: {$redirect_url}?error=invalid_request&msg=Missing action or ID.");
    exit();
}

$action = $_POST['action'];
$subscription_id = intval($_POST['subscription_id']);

// --- Start Transaction for safety ---
$conn->begin_transaction(); 

try {
    // --- RENEW/APPROVAL ACTION ---
    if ($action === 'renew') {
        // 3. FETCH TEMPORARY SUBSCRIPTION DETAILS 
        $sql_fetch = "SELECT members_id, billing_cycle, gcash_reference 
                      FROM temporary_subscription 
                      WHERE subscription_id = ?";
        $stmt_fetch = $conn->prepare($sql_fetch);
        $stmt_fetch->bind_param("i", $subscription_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Temporary subscription record not found.");
        }

        $temp_sub = $result->fetch_assoc();
        $members_id = $temp_sub['members_id'];
        $billing_cycle = $temp_sub['billing_cycle'];
        $gcash_ref = $temp_sub['gcash_reference'];
        $stmt_fetch->close();
        
        // 4. CALCULATE NEW EXPIRATION DATE & PHP PERIOD STRING
        if ($billing_cycle === 'monthly') {
            $interval = 'INTERVAL 1 MONTH';
            $period_string = '+1 month';
        } elseif ($billing_cycle === 'daily') {
            $interval = 'INTERVAL 1 DAY';
            $period_string = '+1 day';
        } else {
            throw new Exception("Invalid billing cycle specified: " . $billing_cycle);
        }
        
        // 5. UPDATE MEMBER EXPIRATION DATE in the 'membership' table
        $sql_update = "UPDATE membership 
                       SET expiration_date = DATE_ADD(
                               COALESCE(expiration_date, NOW()), 
                               $interval
                           )
                       WHERE members_id = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $members_id);

        if (!$stmt_update->execute()) {
            throw new Exception("Database error while updating membership: " . $stmt_update->error);
        }
        $stmt_update->close();

        
     // -------------------------------------------------------------------------
     // ✅ STEP 6: INSERT INTO THE 'subscription' TABLE (Focus on the correct $sub_type)
     // -------------------------------------------------------------------------
        // Calculate precise start and end dates for the historical record
        $start_date = date('Y-m-d'); 
        $end_date = date('Y-m-d', strtotime($start_date . $period_string));

        // Create the subscription type string (e.g., 'monthly' or 'daily')
        // This is the variable that MUST NOT include $members_id.
        $sub_type_final = $billing_cycle; // Assigns only 'monthly' or 'daily'

        $sql_insert_sub = "INSERT INTO subscription 
                         (members_id, subscription_type, gcash_reference_number, start_date, end_date, status, created_at)
                         VALUES (?, ?, ?, ?, ?, 'active', NOW())";
                         
        $stmt_insert_sub = $conn->prepare($sql_insert_sub);
        // Ensure that the SECOND parameter is the clean $sub_type_final variable.
        $stmt_insert_sub->bind_param("issss", 
            $members_id, 
            $sub_type_final, // <-- THIS IS THE CORRECT, CLEAN VARIABLE
            $gcash_ref, 
            $start_date, 
            $end_date
        );

        if (!$stmt_insert_sub->execute()) {
            throw new Exception("Database error while inserting into subscription table: " . $stmt_insert_sub->error);
        }
        $stmt_insert_sub->close();
     // -------------------------------------------------------------------------


        // 7. DELETE RECORD FROM TEMPORARY TABLE (Completion)
        $sql_delete = "DELETE FROM temporary_subscription WHERE subscription_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $subscription_id);

        if (!$stmt_delete->execute()) {
            throw new Exception("Database error while deleting temporary record: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // 8. Commit transaction and redirect
        $conn->commit();
        header("Location: {$redirect_url}?success=renewed&member_id={$members_id}");
        exit();
    } 
    
    // --- REJECT/DELETE ACTION ---
    elseif ($action === 'reject') {
        // ... (Rejection logic remains the same)
        $sql_delete = "DELETE FROM temporary_subscription WHERE subscription_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $subscription_id);

        if (!$stmt_delete->execute()) {
             throw new Exception("Database error while deleting temporary record: " . $stmt_delete->error);
        }
        $stmt_delete->close();
        
        $conn->commit();
        header("Location: {$redirect_url}?success=rejected");
        exit();
    }
    
    // --- INVALID ACTION ---
    else {
        throw new Exception("Invalid action specified.");
    }

} catch (Exception $e) {
    // 9. Rollback and Handle Errors
    $conn->rollback();
    $error_msg = urlencode("Error: " . $e->getMessage());
    header("Location: {$redirect_url}?error=db_error&msg={$error_msg}");
    exit();
}

$conn->close();
?>