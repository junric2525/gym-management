<?php
session_start();
// Include the database connection file
require_once 'db.php'; 

// CRITICAL SECURITY CHECK: Ensure only logged-in admins can access this script
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}

// ----------------------------------------------------------------------
// 1. Process Actions (Accept, Cancel, Delete)
// ----------------------------------------------------------------------

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    // Sanitize and validate the ID
    // (int) cast is fast, but we'll use a prepared statement for the query
    $appointment_id = (int)$_GET['id'];
    $message = '';
    $success = false;

    // Check if the appointment ID is valid before proceeding
    if ($appointment_id <= 0) {
        $_SESSION['appointment_status'] = ['type' => 'error', 'message' => 'Invalid Appointment ID provided.'];
        header("Location: ../Admin/coach_appointmentview.php");
        exit();
    }

    if ($action === 'accept' || $action === 'cancel') {
        // --- LOGIC FOR ACCEPT/CANCEL (UPDATE) ---
        
        $new_status = ($action === 'accept') ? 'Accepted' : 'Cancelled';
        $message = "Appointment ID **#{$appointment_id}** has been **{$new_status}**.";

        // Prepare and execute the update query using prepared statements
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        
        // 'si' stands for String ($new_status) and Integer ($appointment_id)
        $stmt->bind_param("si", $new_status, $appointment_id);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $message = "Database error processing appointment: " . $stmt->error;
        }
        $stmt->close();
        
    } elseif ($action === 'delete') {
        // --- LOGIC FOR DELETE ---

        // Prepare the DELETE statement
        $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        
        // 'i' stands for Integer ($appointment_id)
        $stmt->bind_param("i", $appointment_id);

        // Execute the statement
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = "Appointment ID **#{$appointment_id}** has been successfully **deleted**.";
                $success = true;
            } else {
                $message = "Error: Appointment ID **#{$appointment_id}** not found or already deleted.";
            }
        } else {
            $message = "Database error during deletion: " . $stmt->error;
        }
        $stmt->close();
        
    } else {
        // Invalid action
        $message = 'Invalid action specified.';
    }

    $conn->close();

    // Set the session message based on success/failure
    $_SESSION['appointment_status'] = [
        'type' => $success ? 'success' : 'error',
        'message' => $message
    ];
    
    // Redirect back to the view page
    header("Location: ../Admin/coach_appointmentview.php");
    exit();
}

// ----------------------------------------------------------------------
// 2. Fallback if accessed directly without action/id parameters
// ----------------------------------------------------------------------

if (isset($conn)) {
    $conn->close();
}
header("Location: ../Admin/coach_appointmentview.php");
exit();
?>
