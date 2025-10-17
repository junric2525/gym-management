<?php
// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

session_start(); // Start the session at the very top of the script

// Show errors while testing (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . "/db.php";

if (!isset($_GET['token'])) {
    die("❌ Invalid verification link!");
}

$token = $_GET['token'];

// Find user by token in the PENDING_REGISTRATIONS table
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, token_created_at FROM pending_registrations WHERE verify_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ Invalid or expired verification link!");
}

$user = $result->fetch_assoc();

// Check if token expired (10 minutes)
$createdAt = strtotime($user['token_created_at']);
if (time() - $createdAt > 600) { // 600 seconds = 10 minutes
    // Delete the expired record from the temporary table
    $deleteStmt = $conn->prepare("DELETE FROM pending_registrations WHERE id = ?");
    $deleteStmt->bind_param("i", $user['id']);
    $deleteStmt->execute();
    die("❌ Verification link expired. Please sign up again.");
}

// Move the user data from pending to the final 'users' table.
$insertUser = $conn->prepare("
    INSERT INTO users (first_name, last_name, email, password) 
    VALUES (?, ?, ?, ?)
");
$insertUser->bind_param("ssss", $user['first_name'], $user['last_name'], $user['email'], $user['password']);

if ($insertUser->execute()) {
    // Delete the record from the temporary table after successful insertion.
    $deleteStmt = $conn->prepare("DELETE FROM pending_registrations WHERE id = ?");
    $deleteStmt->bind_param("i", $user['id']);
    $deleteStmt->execute();

    // **SUCCESS! Log the user in and redirect to a dashboard page.**
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];

    // You can now redirect the user to a secure page, like a dashboard.
    header('Location: /gym-management/Guest/Signin.html'); // Replace with your actual dashboard path
    exit();

} else {
    echo "❌ Database error: " . $conn->error;
}

$stmt->close();
$conn->close();
?>