<?php
session_start();
include 'db.php'; 

header('Content-Type: application/json');

// 1. Basic Security Check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// 2. Validate Input
$log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);

if ($log_id === false || $log_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Log ID provided.']);
    exit();
}

// Get the current server time for the manual checkout
$current_time = date('Y-m-d H:i:s');

// 3. Database Operation (UPDATE)
// Note the WHERE clause: only update logs that are currently clocked IN (time_out IS NULL)
$sql = "UPDATE attendance_logs SET time_out = ?, scan_type = 'OUT (Manual)' WHERE log_id = ? AND time_out IS NULL";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("si", $current_time, $log_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Log ID {$log_id} manually timed out.",
                'time_out' => $current_time // Return the time for JS update
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => "Log ID {$log_id} not found or already timed out."]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database execution error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
}

$conn->close();
?>