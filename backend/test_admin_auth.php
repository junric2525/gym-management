<?php
// backend/test_admin_auth.php
session_start();
include "db.php"; // Use your existing database connection

// 1. Define the admin credentials we KNOW should work
$test_email = 'charlesgym@gmail.com';
$test_password = 'admin123'; // Your known password

echo "<h1>Admin Authentication Test</h1>";
echo "<p>Testing Email: <strong>$test_email</strong></p>";
echo "<p>Testing Password: <strong>$test_password</strong></p>";

// 2. Query the admins table using the prepared statement
$stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
if (!$stmt) {
    die("Database prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $test_email);
$stmt->execute();
$adminResult = $stmt->get_result();

if ($adminRow = $adminResult->fetch_assoc()) {
    echo "<h2> STEP 1: Admin Email Found in Database!</h2>";

    // 3. Test the password verification
    if (password_verify($test_password, $adminRow['password'])) {
        echo "<h2> STEP 2: Password Verification SUCCESSFUL!</h2>";
        echo "<p style='color: green; font-weight: bold;'>RESULT: AUTHENTICATION SUCCESS!</p>";
    } else {
        // This is the error if the hash is wrong
        echo "<h2>❌ STEP 2: Password Verification FAILED.</h2>";
        echo "<p style='color: red; font-weight: bold;'>The password hash in the DB is INCORRECT.</p>";
    }
} else {
    // This is the error if the query failed to find the row (the recurring issue)
    echo "<h2>❌ STEP 1: Admin Email NOT Found in Database.</h2>";
    echo "<p style='color: red; font-weight: bold;'>The query failed to find the row. (Whitespace/Casing/Encoding Issue).</p>";
}

$stmt->close();
$conn->close();
?>