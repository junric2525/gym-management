<?php
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// subscription_review_action.php
session_start();
include 'db.php'; // Make sure path is correct

// 1. ✅ Verify admin access
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/Index.html");
    exit();
}

// 2. Validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'], $_POST['temp_id'])) {
    header("Location: ../Admin/renewal_pending_view.php?error=Invalid access");
    exit();
}

$action = $_POST['action'];
$membership_temp_id = intval($_POST['temp_id']);
$redirect_url = "../Admin/renewal_pending_view.php";

// Only proceed with REJECT action for this script
if ($action !== 'reject') {
    header("Location: {$redirect_url}?error=Invalid action type");
    exit();
}

// =======================================================================
// 3. START TRANSACTION FOR REJECTION
// =======================================================================
$conn->begin_transaction();
$error = false;
$file_to_delete = null;
$member_id_to_update = null;

try {
    // A. Fetch necessary data (file path and permanent member ID)
    $sql_fetch = "SELECT member_id_fk, validid_path FROM membership_temp WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("i", $membership_temp_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Record not found in temporary table.");
    }
    $temp_row = $result->fetch_assoc();
    $file_to_delete = $temp_row['validid_path'];
    $member_id_to_update = $temp_row['member_id_fk'];
    $stmt_fetch->close();

    // B. Delete the uploaded file if path is valid
    $full_file_path = __DIR__ . "/../backend/uploads/" . basename($file_to_delete);
    
    // Check if the file exists and is not a malicious path traversal attempt
    if (!empty($file_to_delete) && file_exists($full_file_path)) {
        if (!unlink($full_file_path)) {
            // Log error, but proceed with DB cleanup if file system fails
            error_log("Failed to delete file: " . $full_file_path);
        }
    }

    // C. Update the permanent membership record's renewal status
    $sql_update_membership = "UPDATE membership SET renewal_status = 'Rejected' WHERE members_id = ?";
    $stmt_update = $conn->prepare($sql_update_membership);
    $stmt_update->bind_param("i", $member_id_to_update);
    if (!$stmt_update->execute()) {
        throw new Exception("Failed to update membership status: " . $stmt_update->error);
    }
    $stmt_update->close();
    
    // D. Delete the record from the temporary table
    $sql_delete_temp = "DELETE FROM membership_temp WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete_temp);
    $stmt_delete->bind_param("i", $membership_temp_id);
    if (!$stmt_delete->execute()) {
        throw new Exception("Failed to delete temp record: " . $stmt_delete->error);
    }
    $stmt_delete->close();

    // If all steps succeed, commit
    $conn->commit();
    header("Location: {$redirect_url}?success=renewal_rejected");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $error_msg = urlencode("Rejection failed: " . $e->getMessage());
    header("Location: {$redirect_url}?error=transaction_failed&msg={$error_msg}");
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>