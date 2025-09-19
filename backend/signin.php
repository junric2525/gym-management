<?php
session_start();
include "db.php";

header('Content-Type: application/json'); // Tell browser to expect JSON

$response = ['success' => false, 'message' => 'Invalid request!'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_email'] = $row['email'];

            $response = ['success' => true];
        } else {
            $response['message'] = '⚠ Invalid password!';
        }
    } else {
        $response['message'] = '⚠ No user found with that email!';
    }

    $stmt->close();
    $conn->close();
}

// Return JSON response
echo json_encode($response);
exit();
