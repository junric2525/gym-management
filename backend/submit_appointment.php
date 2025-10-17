<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

session_start();
// CORRECT PATH: Since submit_appointment.php and db.php are both in /backend, use a direct name.
include 'db.php'; 

// Initialize status variables for the status page
$message = '';
$class = 'error'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($conn) || $conn->connect_error) {
        $message = " FATAL ERROR: Database Connection Failed: " . $conn->connect_error;
        goto end_submission;
    }
    
    // --- 1. Receive and Sanitize Data ---
    $member_id = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
    $coach_id = filter_input(INPUT_POST, 'coach_id', FILTER_VALIDATE_INT);
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $appointment_datetime = $date . " " . $time;

    if (!$member_id || !$coach_id || empty($date) || empty($time)) {
        $message = " Error: Missing required form fields. Please ensure all selections are made.";
        goto end_submission;
    }
    
    try {
        // --- 2. Fetch and Validate Member Name (using your schema) ---
        $member_sql = "
            SELECT 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name 
            FROM membership m
            JOIN users u ON m.User_id = u.Id  
            WHERE m.members_id = ?           
        ";
        $member_stmt = $conn->prepare($member_sql);
        $member_stmt->bind_param("i", $member_id);
        $member_stmt->execute();
        $member_result = $member_stmt->get_result();
        
        if ($member_result->num_rows === 0) {
            $message = " Error: Member ID {$member_id} is invalid. Contact administration.";
            $member_stmt->close(); goto end_submission;
        }
        $member_name = $member_result->fetch_assoc()['full_name'];
        $member_stmt->close();

        // --- 3. Get Coach Name and Check Availability (Double Booking Check) ---
        $check_sql = "
            SELECT c.name FROM coaches c 
            LEFT JOIN appointments a ON c.coach_id = a.coach_id AND a.appointment_datetime = ?
            WHERE c.coach_id = ? 
            GROUP BY c.coach_id
            HAVING COUNT(a.appointment_id) = 0
        ";
        
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $appointment_datetime, $coach_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            // Coach slot is either booked or coach_id is bad. Get name for better error.
            $coach_name_sql = "SELECT name FROM coaches WHERE coach_id = ?";
            $name_stmt = $conn->prepare($coach_name_sql);
            $name_stmt->bind_param("i", $coach_id);
            $name_stmt->execute();
            $name_result = $name_stmt->get_result();
            if ($name_result->num_rows > 0) {
                $coach_name = $name_result->fetch_assoc()['name'];
                $message = " Booking Failed: {$coach_name} is already booked on {$date} at " . substr($time, 0, 5) . ". Please select another time or coach.";
            } else {
                $message = " Booking Failed: Coach ID {$coach_id} is invalid.";
            }
            $name_stmt->close();
            $check_stmt->close(); goto end_submission;
        }
        
        $coach_name = $check_result->fetch_assoc()['name'];
        $check_stmt->close();


        // --- 4. Insert Appointment into Database ---
        $insert_sql = "INSERT INTO appointments (members_id, member_name, coach_id, appointment_datetime) 
                        VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("isis", $member_id, $member_name, $coach_id, $appointment_datetime);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $message = " Appointment booked successfully! Confirmation ID: {$new_id}.<br>
                        Member: {$member_name}<br>
                        Coach: {$coach_name} on {$date} at " . substr($time, 0, 5) . "**.";
            $class = 'success';
        } else {
            $message = " Database Insertion Error: " . $stmt->error . "<br>Please ensure your database columns are correct.";
        }
        $stmt->close();

    } catch (Exception $e) {
        $message = " General Error during booking: " . $e->getMessage();
    }
} else {
    $message = " Invalid request method. Please submit the form from the booking page.";
}

end_submission:
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close(); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Status</title>
    <link rel="stylesheet" href="../assets/css/coachappointment.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Note: Styles are relative to where the HTML page is served from, not the PHP script file location */
        .status-page-container { max-width: 600px; margin: 50px auto; text-align: center; }
        .status-box { padding: 30px; border-radius: 8px; font-size: 1.1em; line-height: 1.6; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: inline-block; margin-top: 25px; padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; transition: background-color 0.3s; }
        .back-link:hover { background-color: #2980b9; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-flex">
            <div class="logo">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img" /> 
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <nav class="nav-desktop">
                <a href="../User/profile.php"><i class="fas fa-user"></i> Profile</a> 
                <a href="../Guest/index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header> 

    <main class="container status-page-container">
        <div class="status-box <?php echo $class; ?>">
            <h2>Appointment Booking Status</h2>
            <p><?php echo nl2br($message); ?></p>
            <a href="../User/coach_appointment.php" class="back-link"><i class="fas fa-calendar-alt"></i> Book Another Appointment</a>
        </div>
    </main>
</body>
</html>