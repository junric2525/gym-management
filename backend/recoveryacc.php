<?php
include "db.php";

// PHPMailer import
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Generate a secure token
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Save token and expiry in database
        $update = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
        $update->bind_param("ssi", $token, $expiry, $row['id']);
        $update->execute();

        // Create reset link
        $resetLink = "http://192.168.1.10/gym-management-system/ResetPassword.html?token=$token";

        // Setup PHPMailer
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 2; // or 3 for more details
        $mail->Debugoutput = 'html';


        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'junriclimpangog25@gmail.com'; // your Gmail
            $mail->Password = 'opsffyadftxmlegn';   // your Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Email content
            $mail->setFrom('junriclimpangog25@gmail.com', 'Charles Gym');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body    = "Click the link below to reset your password:<br><a href='$resetLink'>$resetLink</a>";

            $mail->send();
            echo json_encode(['success' => true, 'message' => 'Reset link sent to your email.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'No account found with this email.']);
    }

    exit();
}
