<?php
session_start();
header('Content-Type: application/json');

// Include your database connection
include 'db.php'; 

// Basic security check (must be admin)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

try {
    // SQL to delete ALL records from the attendance_logs table
    // Using TRUNCATE TABLE is faster than DELETE FROM, but DELETE FROM
    // might be preferred if you have foreign key constraints you need to manage carefully.
    // TRUNCATE TABLE is used here for a complete and fast reset.
    $sql = "TRUNCATE TABLE attendance_logs"; 
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'All attendance logs cleared.']);
    } else {
        throw new Exception("Database error: " . $conn->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>