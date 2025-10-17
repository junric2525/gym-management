<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// FILE: ../backend/process_member_permanent_delete.php

// 1. Database Connection and Session Check
require_once 'db.php';
session_start();

// 🛑 CRITICAL AUTHORIZATION CHECK:
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { 
    // Redirect non-admins to a safe page (e.g., login or guest index)
    header('Location: ../Guest/Index.html'); 
    exit(); 
}

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Input Validation
    if (isset($_POST['history_id']) && !empty($_POST['history_id'])) {
        $history_id = $_POST['history_id'];
        
        // --- STEP 1: RETRIEVE FILE PATH BEFORE DELETION ---
        $path_query = "SELECT validid_path FROM deleted_members WHERE history_id = ?";
        
        if ($path_stmt = $conn->prepare($path_query)) {
            $path_stmt->bind_param("i", $history_id);
            $path_stmt->execute();
            $path_stmt->bind_result($validid_path);
            $path_stmt->fetch();
            $path_stmt->close();

            // Check if a path was found and is not empty
            if (!empty($validid_path)) {
                
                // --- STEP 2: CONSTRUCT FULL FILE PATH AND DELETE THE FILE ---
                // The script is in 'backend/', and the path in the DB is e.g. 'uploads/filename.png'.
                // We construct the full server path relative to the current script's location.
                // The file should be located at: [current_directory]/[validid_path] 
                // e.g., 'backend/uploads/filename.png'
                $file_to_delete = __DIR__ . '/' . $validid_path; // __DIR__ is '/path/to/backend'

                // Check if the file exists on the server before attempting deletion
                if (file_exists($file_to_delete)) {
                    // Attempt to permanently delete the file
                    if (unlink($file_to_delete)) {
                        // File deleted successfully. Proceed with DB record deletion.
                    } else {
                        // Log file deletion failure (Optional: redirect with a file error status)
                        // For safety, we proceed with DB deletion even if file deletion fails, 
                        // but a proper application would log this or alert the admin.
                        error_log("Failed to unlink file: " . $file_to_delete);
                    }
                } else {
                    // File not found on server (might already be deleted or path is wrong).
                    // Log the event but still proceed with DB deletion.
                    error_log("File not found on server, skipping unlink: " . $file_to_delete);
                }
            } 
            // If validid_path is empty, no file needed to be deleted.
        } 
        // If path query failed, we continue with the DB deletion attempt.


        // --- STEP 3: DELETE THE DATABASE RECORD ---
        $sql = "DELETE FROM deleted_members WHERE history_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind the history_id parameter (assuming it's an integer 'i')
            $stmt->bind_param("i", $history_id); 

            // 4. Execute the Statement
            if ($stmt->execute()) {
                // Success: Redirect back to the view with a success status
                header("Location: ../Admin/deleted_members_view.php?status=deleted");
                exit();
            } else {
                // Error: Database execution failed
                $error_msg = $stmt->error;
                header("Location: ../Admin/deleted_members_view.php?status=error&msg=" . urlencode("Database execution failed: " . $error_msg));
                exit();
            }

            // Close the statement
            $stmt->close();
        } else {
            // Error in preparing the statement
            $error_msg = $conn->error;
            header("Location: ../Admin/deleted_members_view.php?status=error&msg=" . urlencode("Failed to prepare statement: " . $error_msg));
            exit();
        }
    } else {
        // Missing ID
        header("Location: ../Admin/deleted_members_view.php?status=error&msg=" . urlencode("Missing archive ID for deletion."));
        exit();
    }
} else {
    // Not a POST request
    header("Location: ../Admin/deleted_members_view.php?status=error&msg=" . urlencode("Invalid request method."));
    exit();
}

$conn->close();
?>