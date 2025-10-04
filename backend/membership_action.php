<?php
// Include the database connection file
require_once 'db.php'; 

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // FIX 1: Validate that the 'temp_id' is present (matching the front-end form name)
    if (!isset($_POST['temp_id']) || empty($_POST['temp_id'])) {
        header("Location: ../Admin/payment_pendingview.php?error=no_id");
        exit();
    }
    
    // Sanitize the ID using the correct POST key 'temp_id'
    $member_id = intval($_POST['temp_id']);

    // Get the action from the hidden input field
    $action = $_POST['action'] ?? null;

    // FIX 2: ACTION 1: APPROVE (Checking for 'action' value)
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

            // --- STEP B: CHECK FOR DUPLICATE GCASH REFERENCE ---
            $sql_check = "SELECT members_id FROM membership WHERE gcash_reference = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $pending_member['gcash_reference']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $conn->rollback();
                header("Location: ../Admin/payment_pendingview.php?error=duplicate_gcash");
                exit();
            }
            $stmt_check->close();

            // --- STEP C: Insert data into the permanent 'membership' table ---
            $current_time = date('Y-m-d H:i:s'); 
            
            $sql_insert = "INSERT INTO membership (
                user_id, gender, contact, emergency_name, emergency_number, 
                emergency_relation, medical_conditions, medical_details, 
                medications, medications_details, gcash_reference, validid_path, 
                expiration_date, approved_at, birth_date, address 
            )
            
            VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?
            )";
            
            // Data types: We now have 16 parameters (1 integer, 15 strings/dates)
            $types = "isssssssssssssss"; 
            
            $stmt_insert = $conn->prepare($sql_insert);
            
            // The bind_param sequence matches the column list in $sql_insert:
            $stmt_insert->bind_param($types, 
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
                $pending_member['expiration_date'], 
                $current_time, // approved_at
                $pending_member['birth_date'], 
                $pending_member['address']    
            );
            
            if (!$stmt_insert->execute()) {
                throw new Exception("Error inserting into membership table: " . $stmt_insert->error);
            }
            $stmt_insert->close();


            // --- STEP D: Delete data from the temporary table ---
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
            header("Location: ../Admin/payment_pendingview.php?error=db_error&msg={$error_message}");
            exit();
        }

        // Redirect after successful transaction
        if ($is_successful) {
            header("Location: ../Admin/payment_pendingview.php?success=approved");
            exit();
        }

    } 
    
    // FIX 3: ACTION 2: REJECT (Checking for 'action' value)
    elseif ($action === 'reject') {
        
        // Prepare the DELETE statement for the temporary table
        $sql = "DELETE FROM membership_temp WHERE members_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            
            $stmt->bind_param("i", $member_id);
            
            if ($stmt->execute()) {
                // Success: redirect back to the payment pending page
                header("Location: ../Admin/payment_pendingview.php?success=rejected");
                exit();
            } else {
                // Failure
                $error_message = urlencode("Rejection failed: " . $stmt->error);
                header("Location: ../Admin/payment_pendingview.php?error=db_error&msg={$error_message}");
                exit();
            }
            
            $stmt->close();
        } else {
             $error_message = urlencode("Failed to prepare rejection statement: " . $conn->error);
             header("Location: ../Admin/payment_pendingview.php?error=db_error&msg={$error_message}");
             exit();
        }
    }
}

// If accessed without a proper POST request, redirect
header("Location: ../Admin/payment_pendingview.php");
exit();
?>