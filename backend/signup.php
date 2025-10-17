<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// Enable detailed error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . "/db.php";
include __DIR__ . "/send_emailverification.php"; // This now sends the magic link

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and trim input
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $agree = isset($_POST['agree']);

    $errors = [];

    // 1️⃣ Required fields
    if (!$firstName || !$lastName || !$email || !$password || !$confirmPassword) {
        $errors[] = "Please fill all fields!";
    }

    // 2️⃣ Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format!";
    }

    // 3️⃣ Password match
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match!";
    }

    // 4️⃣ Password strength
    if (!preg_match('/^(?=.*[A-Z])(?=.*[!@#$%^&*()]).{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters, contain 1 uppercase letter and 1 special character!";
    }

    // 5️⃣ Checkbox agreement
    if (!$agree) {
        $errors[] = "You must agree to the terms and conditions!";
    }

    // 6️⃣ Check if email already exists in either the main or pending table
    $stmt = $conn->prepare("
        SELECT id FROM users WHERE email = ? 
        UNION 
        SELECT id FROM pending_registrations WHERE email = ?
    ");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "This email is already registered or a verification email has been sent.";
    }
    $stmt->close();

    // 7️⃣ If there are errors, return JSON
    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "messages" => $errors
        ]);
        exit;
    }

    // 8️⃣ Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 9️⃣ Generate verification token + timestamp
    $token = bin2hex(random_bytes(32));
    $createdAt = date("Y-m-d H:i:s");

    // 🔟 Insert registration data into a temporary table for verification
    $stmt = $conn->prepare("
        INSERT INTO pending_registrations (first_name, last_name, email, password, verify_token, token_created_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $hashedPassword, $token, $createdAt);

    if ($stmt->execute()) {
        // Send verification email (magic link)
        $result = sendVerificationEmail($email, $token);

        header('Content-Type: application/json');
        if ($result === true) {
            echo json_encode([
                "status" => "success",
                "message" => "Registration successful! Please check your email to verify your account."
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to send verification email: $result"
            ]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "Database error: " . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request!"
    ]);
}
?>
