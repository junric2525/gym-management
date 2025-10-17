<?php
// =========================================================
// START: Forced Debugging Block to prevent silent failure
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// END: Forced Debugging Block
// =========================================================

session_start();

// CRITICAL FIX: Include using the absolute directory path (most reliable)
// This should finally find db.php after removing the closing tag from it.
$db_path = __DIR__ . '/db.php';
if (!file_exists($db_path)) {
    die(" FATAL ERROR: db.php not found at: {$db_path}.");
}
include $db_path; 

// CRITICAL SECURITY CHECK: TEMPORARILY COMMENTED OUT

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/Index.html");
    exit();
}


$redirect_page = '../Admin/coach_list.php'; 

// ------------------------------------------------------------------
// === 1. GET Request Handling (Delete Operation) ===
// ------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete') {
    
    $coach_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($coach_id === false || $coach_id === null) {
        $_SESSION['coach_status'] = ['type' => 'error', 'message' => ' Error: Invalid coach ID provided.'];
        header("Location: $redirect_page");
        exit();
    }
    
    $name = '';
    $deleted_appointments = 0; 

    if ($conn->connect_error) { // This check should only fail if db.php was modified
        $_SESSION['coach_status'] = ['type' => 'error', 'message' => ' Database Connection Error: ' . $conn->connect_error];
        header("Location: $redirect_page");
        exit();
    }
    
    // Fetch name
    if ($stmt_fetch = $conn->prepare("SELECT name FROM coaches WHERE coach_id = ?")) {
        $stmt_fetch->bind_param("i", $coach_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        if ($row = $result->fetch_assoc()) { $name = htmlspecialchars($row['name']); }
        $stmt_fetch->close();
    } else { error_log("Failed to prepare name fetch statement: " . $conn->error); }

    $sql_appointments = "DELETE FROM appointments WHERE coach_id = ?"; // Delete dependent records
    $sql_coach = "DELETE FROM coaches WHERE coach_id = ?";            // Delete the coach

    try {
        // STEP 1: DELETE DEPENDENT RECORDS (Appointments)
        if ($stmt_del_appointments = $conn->prepare($sql_appointments)) {
            $stmt_del_appointments->bind_param("i", $coach_id);
            if (!$stmt_del_appointments->execute()) {
                throw new Exception("Error deleting appointments: " . $stmt_del_appointments->error);
            }
            $deleted_appointments = $stmt_del_appointments->affected_rows;
            $stmt_del_appointments->close();
        } else {
            throw new Exception("Failed to prepare appointments delete statement: " . $conn->error);
        }
        
        // STEP 2: DELETE THE COACH RECORD
        if ($stmt_delete = $conn->prepare($sql_coach)) {
            
            // =========================================================
            // *** START DEBUGGING BLOCK - FORCED OUTPUT ***
            // =========================================================
            if (isset($_GET['debug'])) {
                echo "DEBUG MODE ACTIVE. Execution paused before redirect.<br>";
                echo "Appointments deleted: {$deleted_appointments}<br>";
                echo "Coach ID to delete: **{$coach_id}**<br>";
                
                $stmt_delete->bind_param("i", $coach_id);
                
                if (!$stmt_delete->execute()) {
                    echo " **EXECUTION FAILED (SQL):** " . $stmt_delete->error . "<br>";
                } else {
                    echo " **EXECUTION SUCCESSFUL.** Affected Rows: " . $stmt_delete->affected_rows . "<br>";
                }
                $stmt_delete->close();
                $conn->close();
                exit(); 
            }
            // =========================================================

            // Normal execution (removed for brevity but is in the full code)
            $stmt_delete->bind_param("i", $coach_id); 
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $coach_name = $name ?: "Coach ID {$coach_id}";
                    $_SESSION['coach_status'] = ['type' => 'success', 'message' => " Coach **{$coach_name}** deleted successfully. ({$deleted_appointments} linked appointments were removed)."];
                } else {
                    $_SESSION['coach_status'] = ['type' => 'error', 'message' => " Error: Coach ID {$coach_id} not found or may have already been deleted."];
                }
            } else {
                $_SESSION['coach_status'] = ['type' => 'error', 'message' => " Database Error during coach deletion: " . $stmt_delete->error];
            }
            $stmt_delete->close();
        } else {
            $_SESSION['coach_status'] = ['type' => 'error', 'message' => " Failed to prepare final coach delete statement: " . $conn->error];
        }

    } catch (Exception $e) {
        $_SESSION['coach_status'] = ['type' => 'error', 'message' => " General Error: " . $e->getMessage()];
    }
} else {
    $_SESSION['coach_status'] = ['type' => 'error', 'message' => ' Invalid action or access method. Only DELETE via GET is supported.'];
}

$conn->close(); 
header("Location: $redirect_page");
exit();
?>