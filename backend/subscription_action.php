<?php
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

session_start();
// Include the database connection (assuming db.php defines $conn)
include 'db.php'; 

// --- Helper Function (ASSUMED) ---
// This function needs to match the price logic in your application
// It is required here to calculate the expiration date.
function get_plan_duration_in_days($plan_name) {
    // Example logic - adjust this to match your actual pricing/duration rules!
    switch (strtolower($plan_name)) {
        case 'monthly':
        case '1-month plan':
            return 30;
        case 'quarterly':
        case '3-month plan':
            return 90;
        case 'annual':
        case '12-month plan':
            return 365;
        default:
            return 30; // Default to 30 days if plan name is unknown
    }
}

// ✅ Verify admin access
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/Index.html");
    exit();
}

// Check if form is submitted correctly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['subscription_id'])) {
    $action = $_POST['action'];
    $subscription_id = intval($_POST['subscription_id']);

    if ($action === 'delete') {
        // --- 1. DELETE ACTION (Existing Logic) ---
        // We assume you also handle deletion from the invoices table if necessary, 
        // but for now, we'll only delete the primary record.
        $stmt = $conn->prepare("DELETE FROM subscription WHERE subscription_id = ?");
        $stmt->bind_param("i", $subscription_id);

        if ($stmt->execute()) {
            $stmt->close();
            // TODO: Optional: Add logic here to delete related record from 'invoices' table
            header("Location: ../Admin/subscriptionview.php?success=deleted");
            exit();
        } else {
            $error_msg = urlencode("Failed to delete subscription: " . $stmt->error);
            $stmt->close();
            header("Location: ../Admin/subscriptionview.php?error=db_error&msg={$error_msg}");
            exit();
        }
    } 
    
    // --- 2. APPROVE ACTION (NEW LOGIC TO FIX INVOICE HISTORY) ---
    elseif ($action === 'approve') {
        
        // Step A: Fetch required data from the pending subscription record
        $sql_fetch = "SELECT members_id, subscription_type, gcash_reference_number FROM subscription WHERE subscription_id = ?";
        $stmt_fetch = $conn->prepare($sql_fetch);
        $stmt_fetch->bind_param("i", $subscription_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();

        if ($result->num_rows === 0) {
            $error_msg = urlencode("Subscription record not found.");
            header("Location: ../Admin/subscriptionview.php?error=not_found&msg={$error_msg}");
            exit();
        }

        $sub_data = $result->fetch_assoc();
        $members_id = $sub_data['members_id'];
        $plan_name = $sub_data['subscription_type'];
        $gcash_ref = $sub_data['gcash_reference_number'];
        $payment_date = date('Y-m-d H:i:s'); // Set payment date to now

        // Calculate end date based on plan duration
        $duration_days = get_plan_duration_in_days($plan_name);
        $end_date = date('Y-m-d', strtotime("+$duration_days days"));

        $conn->begin_transaction(); // Start transaction for atomicity

        try {
            // Step B: Update the existing record in the old 'subscription' table (status, dates)
            $sql_update_sub = "
                UPDATE subscription 
                SET status = 'active', 
                    start_date = ?, 
                    end_date = ? 
                WHERE subscription_id = ?
            ";
            $stmt_update_sub = $conn->prepare($sql_update_sub);
            $stmt_update_sub->bind_param("ssi", $payment_date, $end_date, $subscription_id);
            $stmt_update_sub->execute();

            if ($stmt_update_sub->affected_rows === 0) {
                throw new Exception("Failed to activate subscription or no change made.");
            }
            $stmt_update_sub->close();


            // Step C: INSERT the approved invoice into the new 'invoices' table
            $item_type = 'Subscription Payment';

            $sql_insert_invoice = "
                INSERT INTO invoices 
                (members_id, item_type, item_name, gcash_reference, payment_date, end_date, subscription_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt_insert_invoice = $conn->prepare($sql_insert_invoice);
            $stmt_insert_invoice->bind_param(
                "isssssi", 
                $members_id, 
                $item_type, 
                $plan_name, 
                $gcash_ref, 
                $payment_date, 
                $end_date,
                $subscription_id // Link it back to the original subscription record
            );
            $stmt_insert_invoice->execute();

            $stmt_insert_invoice->close();
            $conn->commit(); // Commit transaction

            header("Location: ../Admin/subscriptionview.php?success=approved");
            exit();

        } catch (Exception $e) {
            $conn->rollback(); // Rollback on failure
            error_log("Subscription Approval Failed: " . $e->getMessage());
            $error_msg = urlencode("Approval failed. Transaction rolled back: " . $e->getMessage());
            header("Location: ../Admin/subscriptionview.php?error=db_error&msg={$error_msg}");
            exit();
        }

    } else {
        header("Location: ../Admin/subscriptionview.php?error=db_error&msg=Invalid action");
        exit();
    }
} else {
    header("Location: ../Admin/subscriptionview.php?error=db_error&msg=Missing subscription ID or action");
    exit();
}
