<?php
// --------------------------------------------
// CONFIGURATION AND SETUP
// --------------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();

include 'db.php'; 
date_default_timezone_set('Asia/Manila');

// --- Helper function to send JSON response and exit ---
function sendResponse($success, $message, $data = []) {
    global $conn;
    if (isset($conn)) {
        if (is_object($conn) && $conn->ping()) {
            $conn->close();
        }
    }
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// --- Input Variables ---
// CRITICAL FIX: Use trim() immediately on input variables
$members_id = trim($_POST['members_id'] ?? ''); 
$action = $_REQUEST['action'] ?? '';
$now = date('Y-m-d H:i:s');

// --- Initial Validation ---
if (empty($action)) {
    sendResponse(false, '❌ Invalid request. Action is missing.');
}

if (!isset($conn) || $conn->connect_error) {
    sendResponse(false, 'Database connection failed: ' . ($conn->connect_error ?? 'Check db.php'));
}

// ----------------------------------------------------
// A. FETCH TODAY'S ATTENDANCE LOGS (Action: get_logs)
// ----------------------------------------------------
if ($action === 'get_logs') {
    $sql = "
        SELECT 
            a.log_id,
            -- REMOVED a.members_id AS member_id,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            DATE_FORMAT(a.time_in, '%h:%i:%s %p') AS time_in,
            DATE_FORMAT(a.time_out, '%h:%i:%s %p') AS time_out,
            a.scan_type
        FROM attendance_logs a
        JOIN membership m ON a.members_id = m.members_id
        JOIN users u ON m.user_id = u.id
        WHERE DATE(a.time_in) = CURDATE()
        ORDER BY a.time_in DESC
    ";
    // The change is in the SELECT clause above: 'a.members_id AS member_id,' has been removed.

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("SQL Prepare Error (get_logs): " . $conn->error);
        sendResponse(false, 'Database Error: Could not prepare log query.');
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    sendResponse(true, 'Logs retrieved.', ['logs' => $logs]);
}

// ----------------------------------------------------
// B. TIME IN/OUT LOGIC (Requires members_id)
// ----------------------------------------------------

// ⭐ REVERTED FIX: Use ctype_digit for strict numeric ID checking
if (empty($members_id) || !ctype_digit($members_id)) { 
    sendResponse(false, ' Invalid request. Member ID must be a whole number.');
}

// Cast to int before binding for type safety
$members_id = (int)$members_id;


// Validate Member ID and retrieve Full Name
$check_sql = "
    SELECT u.first_name, u.last_name
    FROM membership m
    JOIN users u ON m.user_id = u.id
    WHERE m.members_id = ? 
    AND TRIM(LOWER(u.membership_status)) = 'active'
";
$check_stmt = $conn->prepare($check_sql);
if ($check_stmt === false) {
    error_log("SQL Prepare Error (Member Check): " . $conn->error);
    sendResponse(false, 'Database Error: Could not prepare member check query.');
}
// ⭐ REVERTED FIX: Bind as integer ("i")
$check_stmt->bind_param("i", $members_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$member = $result->fetch_assoc();
$check_stmt->close();

if (!$member) {
    sendResponse(false, ' Member ID not found or membership is not active.');
}
$member_name = trim($member['first_name'] . ' ' . $member['last_name']);

// --------------------------------------------
// TIME IN LOGIC
// --------------------------------------------
if ($action === 'time_in') {
    $check_in_sql = "
        SELECT log_id 
        FROM attendance_logs 
        WHERE members_id = ? AND DATE(time_in) = CURDATE() AND time_out IS NULL 
        ORDER BY time_in DESC LIMIT 1
    ";
    $check_in_stmt = $conn->prepare($check_in_sql);
    if ($check_in_stmt === false) {
        error_log("SQL Prepare Error (Time In): " . $conn->error);
        sendResponse(false, 'Database Error: Fatal prepare error on Time In check.');
    }
    // ⭐ REVERTED FIX: Bind as integer ("i")
    $check_in_stmt->bind_param("i", $members_id);
    $check_in_stmt->execute();
    $existing_log = $check_in_stmt->get_result();
    $check_in_stmt->close();

    if ($existing_log->num_rows > 0) {
        sendResponse(false, " **{$member_name}** is already Timed In. Please Time Out first.");
    }

    $insert_sql = "
        INSERT INTO attendance_logs (members_id, time_in, scan_type) 
        VALUES (?, ?, 'IN')
    ";
    $insert_stmt = $conn->prepare($insert_sql);
    // ⭐ REVERTED FIX: Bind members_id as integer ("i")
    $insert_stmt->bind_param('is', $members_id, $now);

    if ($insert_stmt->execute()) {
        $insert_stmt->close();
        sendResponse(true, " **{$member_name}**, Time In recorded. Welcome!");
    } else {
        $insert_stmt->close();
        sendResponse(false, 'Database error during Time In recording: ' . $conn->error);
    }
}

// --------------------------------------------
// TIME OUT LOGIC
// --------------------------------------------
elseif ($action === 'time_out') {
    $find_log_sql = "
        SELECT log_id 
        FROM attendance_logs 
        WHERE members_id = ? AND DATE(time_in) = CURDATE() AND time_out IS NULL 
        ORDER BY time_in DESC LIMIT 1
    ";
    $find_log_stmt = $conn->prepare($find_log_sql);
    if ($find_log_stmt === false) {
        error_log("SQL Prepare Error (Time Out): " . $conn->error);
        sendResponse(false, 'Database Error: Fatal prepare error on Time Out check.');
    }
    // ⭐ REVERTED FIX: Bind as integer ("i")
    $find_log_stmt->bind_param("i", $members_id);
    $find_log_stmt->execute();
    $result_log = $find_log_stmt->get_result();
    $log_entry = $result_log->fetch_assoc();
    $find_log_stmt->close();

    if (!$log_entry) {
        sendResponse(false, " **{$member_name}** has no active Time In log today.");
    }

    $log_id = $log_entry['log_id'];

    $update_sql = "
        UPDATE attendance_logs 
        SET time_out = ?, scan_type = 'OUT' 
        WHERE log_id = ?
    ";
    $update_stmt = $conn->prepare($update_sql);
    // 's' for $now (string), 'i' for $log_id (integer)
    $update_stmt->bind_param('si', $now, $log_id);

    if ($update_stmt->execute()) {
        $update_stmt->close();
        sendResponse(true, " **{$member_name}**, Time Out recorded. Goodbye!");
    } else {
        $update_stmt->close();
        sendResponse(false, 'Database error during Time Out recording: ' . $conn->error);
    }
}

// --------------------------------------------
// UNKNOWN ACTION
// --------------------------------------------
else {
    sendResponse(false, 'Unknown action specified.');
}
?>