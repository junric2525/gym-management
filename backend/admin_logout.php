<?php
session_start();
session_unset();   // clear all session variables
session_destroy(); // destroy the session

header("Location: ../Guest/Index.html"); // redirect to guest login/home
exit();
?>
