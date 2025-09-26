<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader (inside backend folder)
require __DIR__ . '/vendor/autoload.php';

// Function to send recovery email
function sendRecoveryEmail($recipientEmail, $token) {
    // Correct reset password link
    $resetLink = "http://localhost/gym-management/Guest/ResetPassword.php?token=" . urlencode($token);

    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gymcharles8@gmail.com';   // your Gmail
        $mail->Password   = 'ufwyaorcihwmcekv';        // your App Password (NOT Gmail password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender and recipient
        $mail->setFrom('gymcharles8@gmail.com', 'Charles Gym');
        $mail->addAddress($recipientEmail);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - Charles Gym';
        $mail->Body    = "
            <h3>Password Reset Request</h3>
            <p>Hello,</p>
            <p>Click the link below to reset your password (valid for 10 minutes):</p>
            <p><a href='$resetLink'>$resetLink</a></p>
            <br>
            <p>If you didn’t request this, you can safely ignore this email.</p>
        ";
        $mail->AltBody = "Reset your password using this link: $resetLink";

        $mail->send();
        return true;

    } catch (Exception $e) {
        return "Mailer Error: " . $mail->ErrorInfo;
    }
}
