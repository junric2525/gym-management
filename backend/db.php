<?php
$servername = "localhost";
$username = "root";   // default in XAMPP
$password = "";       // default in XAMPP
$dbname = "gym";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
