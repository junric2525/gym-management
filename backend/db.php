<?php
// DATABASE CONFIG
$host = "localhost";
$user = "root";
$pass = "";
$db  = "gym"; // Assuming 'gym' is the correct database name based on this file

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Note: Do NOT use die() in included files if a redirect is expected later.
    // However, for a fatal DB error, die() is often acceptable.
    die("DB Connection failed: " . $conn->connect_error); 
}

// MAIL CONFIG (Sender account for PHPMailer)
$MAIL_SENDER     = "yourgmail@gmail.com";        
$MAIL_PASSWORD   = "your-app-password";          
$MAIL_SENDERNAME = "My Website";               
// NO CLOSING PHP TAG HERE