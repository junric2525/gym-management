<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    // 🔹 Replace with your Gmail and App Password
    $mail->Username = 'gymcharles8@gmail.com';   
    $mail->Password = 'ufwyaorcihwmcekv'; 

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('gymcharles8@gmail.com', 'Charles Gym');
    $mail->addAddress('junriclimpangog25@gmail.com'); // test receiving email

    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test';
    $mail->Body    = '✅ If you see this, PHPMailer is working!';

    $mail->SMTPDebug = 2;  // show debug info
    $mail->Debugoutput = 'html';

    $mail->send();
    echo "✅ Test email sent successfully!";
} catch (Exception $e) {
    echo "❌ Test failed. Error: {$mail->ErrorInfo}";
}
