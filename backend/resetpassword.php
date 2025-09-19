<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        die("⚠ Passwords do not match!");
    }

    // Check if token exists and not expired
    $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $expires = strtotime($row['reset_expires']);
        if ($expires < time()) {
            die("⚠ Token expired. Please request a new password reset.");
        }

        // Hash new password
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        // Update user password and clear token
        $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        $update->bind_param("si", $hashedPassword, $row['id']);
        $update->execute();

        echo "✅ Password reset successful! You can now <a href='Signin.html'>login</a>.";
    } else {
        echo "⚠ Invalid or used token.";
    }
}
?>
