<?php
// Simulate a POST request for Member ID 47 and action time_in
$_POST['members_id'] = '47';
$_REQUEST['action'] = 'time_in';

// Include the original script to run the logic
include 'attendance_action.php';
?>