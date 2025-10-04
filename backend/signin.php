<?php
// backend/login_action.php

session_start();
header("Content-Type: application/json");

// Include the database connection file
require_once __DIR__ . "/db.php";

// Debugging (disable or remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validate POST data
if (empty($_POST['email']) || empty($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing email or password']);
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

// 1. ---- Check Admins first ----
$stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE email = ? LIMIT 1");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed for admins.']);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Admin found, verify password
    if (password_verify($password, $row['password'])) {
        // Successful Admin Login
        
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_email'] = $row['email'];
        
        // ** THE FIX: Set the session flag required by Admin pages **
        $_SESSION['is_admin'] = true; 
        
        echo json_encode([
            'success' => true,
            'redirect' => '../Admin/admin.php'
        ]);
        exit;
    }
}
$stmt->close(); // Close statement before proceeding to the next query

// 2. ---- If not admin, check Users ----
$stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ? LIMIT 1");

if (!$stmt) {
    // If the first query succeeded but this one fails, report failure.
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed for users.']);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // User found, verify password
    if (password_verify($password, $row['password'])) {
        // Successful User Login
        
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_email'] = $row['email'];
        
        // Ensure the admin flag is explicitly NOT set for regular users
        unset($_SESSION['is_admin']); 

        echo json_encode([
            'success' => true,
            'redirect' => '../User/user.php'
        ]);
        exit;
    }
}
$stmt->close();


// ❌ Invalid credentials
// This message is shown if the email was not found in either table OR the password failed verification in both cases.
echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
exit;
?>