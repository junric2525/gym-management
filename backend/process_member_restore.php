<?php
// FILE: ../backend/process_member_restore.php
require_once 'db.php'; 
session_start(); // Start session to potentially use for logging

// 1. VALIDATION CHECK (Must look for 'history_id' from the deleted members view)
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['history_id'])) {
    // Redirect back to the history view on failure
    header("Location: ../Admin/deleted_members_view.php?status=error&msg=invalid_request"); 
    exit();
}

$history_id = intval($_POST['history_id']); 

$conn->begin_transaction();
$is_successful = false;

try {
    // --- STEP 1: Restore data (INSERT INTO membership) ---
    // SELECTs data using history_id and INSERTs into membership.
    $sql_restore = "
        INSERT INTO membership (
            members_id, user_id, gender, birth_date, address, contact, 
            emergency_name, emergency_number, emergency_relation, 
            medical_conditions, medical_details, medications, medications_details, 
            gcash_reference, validid_path, approved_at, expiration_date
        )
        SELECT 
            original_members_id, user_id, gender, birth_date, address, contact, 
            emergency_name, emergency_number, emergency_relation, 
            medical_conditions, medical_details, medications, medications_details, 
            gcash_reference, validid_path, approved_at, expiration_date
        FROM deleted_members 
        WHERE history_id = ?
    ";
    
    $stmt_restore = $conn->prepare($sql_restore);
    $stmt_restore->bind_param("i", $history_id);
    if (!$stmt_restore->execute()) {
        throw new Exception("Error restoring member to active table: " . $stmt_restore->error);
    }
    $stmt_restore->close();

    // --- STEP 2: Delete the history record ---
    $sql_clean = "DELETE FROM deleted_members WHERE history_id = ?";
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
    // Error redirect points back to the history view
    header("Location: ../Admin/deleted_members_view.php?status=error&msg=" . urlencode("DB Restoration Failed: " . $e->getMessage()));
    exit();
}

if ($is_successful) {
    // SUCCESS REDIRECT: Go back to the history view
    header("Location: ../Admin/deleted_members_view.php?status=restored");
    exit();
}
?>