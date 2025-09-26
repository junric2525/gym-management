<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

function sendVerificationEmail($recipientEmail, $token) {
    $verifyLink = "http://localhost/gym-management/backend/verify_email.php?token=" . urlencode($token);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gymcharles8@gmail.com';  // your Gmail
        $mail->Password   = 'ufwyaorcihwmcekv';      // your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('gymcharles8@gmail.com', 'Charles Gym');
        $mail->addAddress($recipientEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - Charles Gym';
        $mail->Body    = "
            <h3>Welcome to Charles Gym!</h3>
            <p>Please verify your email by clicking the link below:</p>
            <p><a href='$verifyLink'>$verifyLink</a></p>
            <br>
            <p>This link will expire in 10 minutes.</p>
        ";
        $mail->AltBody = "Verify your email using this link: $verifyLink";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mailer Error: " . $mail->ErrorInfo;
    }
}
