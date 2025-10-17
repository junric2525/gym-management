<?php
session_start();
include 'db.php'; // Include your database connection

header('Content-Type: application/json');

// 1. Basic Security Check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// 2. Validate and Sanitize Inputs
$log_id = filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
$time_in_str = filter_input(INPUT_POST, 'time_in', FILTER_SANITIZE_STRING);
$time_out_str = filter_input(INPUT_POST, 'time_out', FILTER_SANITIZE_STRING);

if ($log_id === false || $log_id <= 0 || empty($time_in_str)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Log ID or Time In value.']);
    exit();
}

// Convert HTML datetime-local format (Y-m-d\TH:i) to MySQL format (Y-m-d H:i:s)
$time_in_mysql = str_replace('T', ' ', $time_in_str) . ':00';

// Handle time_out: NULL if empty, otherwise format it
$time_out_mysql = null;
if (!empty($time_out_str)) {
    $time_out_mysql = str_replace('T', ' ', $time_out_str) . ':00';
}

// Determine scan_type based on time_out status
$scan_type = $time_out_mysql === null ? 'IN' : 'OUT (Edited)';


// 3. Database Operation (UPDATE)
$sql = "UPDATE attendance_logs SET time_in = ?, time_out = ?, scan_type = ? WHERE log_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Bind parameters, using 's' for time_out even if it's null (MySQL handles this)
    $stmt->bind_param("sssi", $time_in_mysql, $time_out_mysql, $scan_type, $log_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => "Log ID {$log_id} updated successfully.",
            'time_in' => $time_in_mysql,
            'time_out' => $time_out_mysql
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database execution error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
}

$conn->close();
?>