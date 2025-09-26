<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        header("Location: ../Guest/resetpassword.php?token=$token&error=All fields are required");
        exit();
    }

    if ($new_password !== $confirm_password) {
        header("Location: ../Guest/resetpassword.php?token=$token&error=Passwords do not match");
        exit();
    }

    // Check if token exists and not expired
    $stmt = $conn->prepare("SELECT id, reset_expiry FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $expires = strtotime($row['reset_expiry']);
        if ($expires < time()) {
            header("Location: ../Guest/resetpassword.php?error=Token expired. Please request a new password reset.");
            exit();
        }

        // Hash new password
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        // Update user password and clear token
        $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expiry=NULL WHERE id=?");
        $update->bind_param("si", $hashedPassword, $row['id']);
        $update->execute();

        // ✅ Redirect after success
        header("Location: ../Guest/Signin.html?reset=success");
        exit();
    } else {
        header("Location: ../Guest/resetpassword.php?error=Invalid or used token");
        exit();
    }
}
?>
