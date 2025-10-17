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

// 3. Database Operation (DELETE)
$sql = "DELETE FROM attendance_logs WHERE log_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $log_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => "Log ID {$log_id} deleted successfully."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Log ID {$log_id} not found or already deleted."]);
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