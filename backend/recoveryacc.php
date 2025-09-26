<?php
include "db.php";                
include "send_recovery.php";     
session_start();

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);

    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        $updateStmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expiry=? WHERE email=?");
        $updateStmt->bind_param("sss", $token, $expiry, $email);
        $updateStmt->execute();
        $updateStmt->close();

        $result = sendRecoveryEmail($email, $token);

        if ($result === true) {
            echo json_encode([
                "status" => "success",
                "message" => "✅ Recovery link sent to $email. Please check your inbox."
            ]);
        } else {
            // Log error, don't expose full details
            error_log("Mailer Error for $email: $result");
            echo json_encode([
                "status" => "error",
                "message" => "❌ Failed to send email. Please try again later."
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "❌ No account found with that email."
        ]);
    }

    $checkStmt->close();
    $conn->close();
}
