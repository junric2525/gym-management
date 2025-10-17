<?php
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// Ensure the database connection file
require_once 'db.php'; 
session_start(); // Start the session if not already done

// 🛑 CRITICAL SECURITY CHECK: Ensure only logged-in administrators can run this script.
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { 
    // Redirect non-admins to a safe page (e.g., login or guest index)
    header('Location: ../Guest/Index.html'); 
    exit(); 
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate that the 'temp_id' is present (matching the front-end form name)
    if (!isset($_POST['temp_id']) || empty($_POST['temp_id'])) {
        header("Location: ../Admin/payment_pendingview.php?error=no_id&msg=" . urlencode("Missing required application ID."));
        exit();
    }
    
    // Sanitize the ID
    $member_id = intval($_POST['temp_id']);

    // Get the action from the hidden input field
    $action = $_POST['action'] ?? null;

    // --- ACTION 1: APPROVE ---
    if ($action === 'approve') {
        
        // Start a transaction to ensure both operations succeed or fail together
        $conn->begin_transaction();
        $is_successful = false;

        try {
            // --- STEP A: Fetch the data from membership_temp ---
            $sql_fetch = "SELECT * FROM membership_temp WHERE members_id = ?";
            $stmt_fetch = $conn->prepare($sql_fetch);
            $stmt_fetch->bind_param("i", $member_id);
            $stmt_fetch->execute();
            $result = $stmt_fetch->get_result();
            $pending_member = $result->fetch_assoc();
            $stmt_fetch->close();

            if (!$pending_member) {
                throw new Exception("Member ID ($member_id) not found in pending table.");
            }
            
            // Get user_id for later status update
            $user_id_to_update = $pending_member['user_id']; 

            // --- STEP B: CHECK FOR DUPLICATE GCASH REFERENCE in permanent table ---
            $sql_check = "SELECT members_id FROM membership WHERE gcash_reference = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $pending_member['gcash_reference']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $conn->rollback();
                header("Location: ../Admin/payment_pendingview.php?error=duplicate_gcash&msg=" . urlencode("This GCash reference is already used by an existing member."));
                exit();
            }
            $stmt_check->close();

            // --- STEP C.1: Calculate Dates ---
            $current_time = date('Y-m-d H:i:s'); 
            
            // Calculate the new expiration date (1 year from approval date, using today's date)
            $start_date = new DateTime(date('Y-m-d')); // Today's date (date part only for expiration)
            $expiration_dt = clone $start_date;
            $expiration_dt->add(new DateInterval('P1Y')); 
            $expiration_date_final = $expiration_dt->format('Y-m-d'); 
            // Use the created_at timestamp from the temp table as the payment date for the invoice
            $invoice_payment_date = $pending_member['created_at']; 


            // --- STEP C.2: Insert data into the permanent 'membership' table ---
            $sql_insert = "INSERT INTO membership (
                user_id, gender, contact, emergency_name, emergency_number, 
                emergency_relation, medical_conditions, medical_details, 
                medications, medications_details, gcash_reference, validid_path, 
                expiration_date, approved_at, birth_date, address, renewal_status 
            )
            
            VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, 'Active'
            )";
            
            // Data types: 1 integer, 15 strings/dates
            $stmt_insert = $conn->prepare($sql_insert);
            
            $stmt_insert->bind_param("isssssssssssssss", 
                $pending_member['user_id'], 
                $pending_member['gender'], 
                $pending_member['contact'], 
                $pending_member['emergency_name'], 
                $pending_member['emergency_number'], 
                $pending_member['emergency_relation'], 
                $pending_member['medical_conditions'], 
                $pending_member['medical_details'], 
                $pending_member['medications'], 
                $pending_member['medications_details'], 
                $pending_member['gcash_reference'], 
                $pending_member['validid_path'], 
                $expiration_date_final, // FIXED EXPIRATION DATE 
                $current_time, // approved_at
                $pending_member['birth_date'], 
                $pending_member['address'] 
            );
            
            if (!$stmt_insert->execute()) {
                throw new Exception("Error inserting into membership table: " . $stmt_insert->error);
            }
            // Get the LAST INSERT ID, which is the permanent members_id
            $permanent_members_id = $conn->insert_id; 
            $stmt_insert->close();
            
            
            // 🛑 --- STEP C.3: CRITICAL FIX: INSERT INTO 'invoices' TABLE --- 🛑
            $invoice_item_type = "Membership Fee";
            $invoice_item_name = "Initial Membership Registration"; 

            $sql_insert_invoice = "
                INSERT INTO invoices (
                    members_id, item_type, item_name, gcash_reference, payment_date, end_date
                ) VALUES (?, ?, ?, ?, ?, ?)
            ";
            
            $stmt_insert_invoice = $conn->prepare($sql_insert_invoice);
            
            $stmt_insert_invoice->bind_param(
                "isssss", 
                $permanent_members_id, 
                $invoice_item_type, 
                $invoice_item_name, 
                $pending_member['gcash_reference'], 
                $invoice_payment_date, // Use the date/time the application was submitted
                $expiration_date_final // Using the calculated expiration date
            );

            if (!$stmt_insert_invoice->execute()) {
                 // Rollback if the invoice fails
                throw new Exception("Invoice creation failed (Check invoice table columns): " . $stmt_insert_invoice->error);
            }
            $stmt_insert_invoice->close();
            
            
            // --- STEP D: Update the user's status to 'Active' ---
            $sql_update_user_active = "UPDATE users SET membership_status = 'Active' WHERE id = ?";
            $stmt_update_user_active = $conn->prepare($sql_update_user_active);
            $stmt_update_user_active->bind_param("i", $user_id_to_update);
            
            if (!$stmt_update_user_active->execute()) {
                throw new Exception("Error updating user status to Active: " . $stmt_update_user_active->error);
            }
            $stmt_update_user_active->close();


            // --- STEP E: Delete data from the temporary table (Cleanup) ---
            $sql_delete = "DELETE FROM membership_temp WHERE members_id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $member_id);
            
            if (!$stmt_delete->execute()) {
                throw new Exception("Error deleting from temp table: " . $stmt_delete->error);
            }
            $stmt_delete->close();

            // All steps succeeded
            $conn->commit();
            $is_successful = true;

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = urlencode($e->getMessage());
            error_log("Approval Error: " . $e->getMessage()); // Log the error on the server
            header("Location: ../Admin/payment_pendingview.php?error=db_error&msg={$error_message}");
            exit();
        }

        // Redirect after successful transaction
        if ($is_successful) {
            header("Location: ../Admin/payment_pendingview.php?success=approved");
            exit();
        }

    } 
    
    // --- ACTION 2: REJECT (with File Deletion) ---
    elseif ($action === 'reject') {
        
        $is_successful = false;
        
        // 1. START TRANSACTION 
        $conn->begin_transaction();
        
        try {
            // A. Fetch the file path AND user_id from the temporary table
            $sql_fetch_path = "SELECT user_id, validid_path FROM membership_temp WHERE members_id = ?";
            $stmt_fetch = $conn->prepare($sql_fetch_path);
            $stmt_fetch->bind_param("i", $member_id);
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $temp_data = $result_fetch->fetch_assoc();
            $stmt_fetch->close();
            
            // Check if the record exists
            if (!$temp_data) {
                 throw new Exception("Temporary membership record not found.");
            }
            $user_id_to_update = $temp_data['user_id'];


            // B. Update the user's membership status to 'Rejected'
            $sql_update_user = "UPDATE users SET membership_status = 'Rejected' WHERE id = ?";
            $stmt_update_user = $conn->prepare($sql_update_user);
            $stmt_update_user->bind_param("i", $user_id_to_update);
            
            if (!$stmt_update_user->execute()) {
                 throw new Exception("Error updating user status to Rejected: " . $stmt_update_user->error);
            }
            $stmt_update_user->close();


            // C. Delete the data from the temporary table
            $sql_delete = "DELETE FROM membership_temp WHERE members_id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $member_id);
            
            if (!$stmt_delete->execute()) {
                 throw new Exception("Error deleting from temp table: " . $stmt_delete->error);
            }
            $stmt_delete->close();

            // D. Commit the transaction (DB deletion is complete)
            $conn->commit();
            
            // E. Delete the physical file *after* DB success (CRITICAL FIX)
            if ($temp_data && !empty($temp_data['validid_path'])) {
                $relative_file_path = $temp_data['validid_path'];
                
                // CRITICAL FIX: Construct the absolute server path relative to this script's directory.
                // Assuming $relative_file_path starts from the correct uploads folder relative to this script.
                // Adjust '../' based on the actual file structure. This example assumes 'handle_approval.php'
                // is in a 'backend' folder and the files are in '../uploads/...' or similar.
                // A common correct structure is: __DIR__ . '/../' . $relative_file_path;
                $absolute_file_path = __DIR__ . '/../' . $relative_file_path; 

                // Check if the file exists before attempting to delete it
                if (file_exists($absolute_file_path)) {
                    // Attempt to delete the file. We log an error if it fails, but don't stop the rejection.
                    if (!unlink($absolute_file_path)) {
                        error_log("WARNING: Failed to delete rejected file: " . $absolute_file_path);
                    }
                } else {
                    error_log("WARNING: File not found for deletion: " . $absolute_file_path);
                }
            }
            
            $is_successful = true;

        } catch (Exception $e) {
             // Rollback transaction on DB error
             $conn->rollback();
             $error_message = urlencode($e->getMessage());
             error_log("Rejection Error: " . $e->getMessage()); // Log the error on the server
             header("Location: ../Admin/payment_pendingview.php?error=db_error&msg={$error_message}");
             exit();
        }
        
        // Redirect after successful operation
        if ($is_successful) {
             header("Location: ../Admin/payment_pendingview.php?success=rejected");
             exit();
        }
    } 
}

// If accessed without a proper POST request, redirect
header("Location: ../Admin/payment_pendingview.php");
exit();
?>