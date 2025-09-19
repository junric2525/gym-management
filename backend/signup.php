<?php
// Enable detailed error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "db.php"; // Include your database connection

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

    // 4️⃣ Password strength: at least 8 chars, 1 uppercase, 1 special char
    if (!preg_match('/^(?=.*[A-Z])(?=.*[!@#$%^&*()]).{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters, contain 1 uppercase letter and 1 special character!";
    }

    // 5️⃣ Checkbox agreement
    if (!$agree) {
        $errors[] = "You must agree to the terms and conditions!";
    }

    // 6️⃣ Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "This email is already registered!";
    }
    $stmt->close();

    // 7️⃣ If there are errors, display them
    if (!empty($errors)) {
        foreach ($errors as $err) {
            echo "<p style='color:red;'>⚠ $err</p>";
        }
        echo "<p><a href='../Guest/Signup.html'>Go back to signup</a></p>";
        exit;
    }

    // 8️⃣ Hash the password and insert user into the database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);

    if ($stmt->execute()) {
        // Successful registration
        header("Location: ../Guest/Signin.html");
        exit();
    } else {
        die("❌ Database error: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} else {
    echo "Invalid request!";
}
?>
