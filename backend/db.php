<?php
// DATABASE CONFIG
$host = "localhost";
$user = "root";
$pass = "";
$db   = "gym";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// MAIL CONFIG (Sender account for PHPMailer)
$MAIL_SENDER     = "yourgmail@gmail.com";      
$MAIL_PASSWORD   = "your-app-password";        
$MAIL_SENDERNAME = "My Website";               
?>
