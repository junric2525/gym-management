<?php
// backend/check_in.php

// 1. Configuration and Security
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json'); // Respond in JSON format

// Function to handle database connection
if (file_exists('db.php')) {
    require_once 'db.php';
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database connection file missing.']);
    exit;
}

// 2. Data Retrieval (Expects a POST request from the scanner)
// Use file_get_contents for robust JSON decoding from AJAX body
$input = json_decode(file_get_contents('php://input'), true);
$scanned_data = $input['member_data'] ?? null;

// 3. Data Validation
if (!$scanned_data || !str_starts_with($scanned_data, 'GM-')) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing QR code data.']);
    $conn->close();
    exit;
}

// Extract the numeric ID part (e.g., turn "GM-123" into "123")
$member_id = (int)str_replace('GM-', '', $scanned_data);

if ($member_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid member ID extracted.']);
    $conn->close();
    exit;
}

// 4. Check Member Status and Existence
try {
    // 4a. Check if the member exists and is active (you might need to adjust this check)
    $sql_check = "SELECT 
                    m.members_id, 
                    u.membership_status 
                  FROM membership m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.members_id = ?";
    
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $member_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $member_info = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$member_info) {
        echo json_encode(['status' => 'denied', 'message' => 'Member not found.']);
        $conn->close();
        exit;
    }

    // 4b. Perform additional checks (e.g., membership expiry and status logic from profile.php)
    // NOTE: For simplicity, we skip full expiry logic here, assuming an active status is sufficient.
    // In a real system, you'd check `expiration_date` here.
    if ($member_info['membership_status'] !== 'Active') {
        echo json_encode(['status' => 'denied', 'message' => "Access Denied. Membership Status: {$member_info['membership_status']}"]);
        $conn->close();
        exit;
    }
    
    // 5. Record Attendance (Assuming you have an `attendance` table: members_id, check_in_time)
    $sql_insert = "INSERT INTO attendance (members_id, check_in_time) VALUES (?, NOW())";
    
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("i", $member_id);

    if ($stmt_insert->execute()) {
        // Success response
        echo json_encode([
            'status' => 'success', 
            'message' => 'Check-In Successful!',
            'member_id' => $member_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        // Insert error
        error_log("Attendance INSERT error: " . $stmt_insert->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to record attendance in the database.']);
    }

    $stmt_insert->close();

} catch (Exception $e) {
    error_log("PHP EXCEPTION during check-in: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected server error occurred.']);
}

$conn->close();
?>