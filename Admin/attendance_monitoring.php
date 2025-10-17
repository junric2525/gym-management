
<?php
session_start();
include '../backend/db.php';
date_default_timezone_set('Asia/Manila');

// OPTIONAL: Admin access check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gym Attendance</title>
  <link rel="stylesheet" href="../assets/css/attendance.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    #reader {
      width: 100%;
      max-width: 400px; /* Limit scanner size for better mobile/desktop view */
      margin: 20px auto;
    }
  </style>
</head>
<body>
  <div class="container">
     <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
    <h1>Gym Attendance</h1>
    <form id="attendanceForm" onsubmit="return false;">
      <input type="text" id="members_id" placeholder="Enter or Scan Member ID" required>
      
      <button type="button" id="startScannerBtn" class="btn timein" style="width: 100%; margin-top: 10px;">
        Start QR Scanner 
      </button>

      <div id="reader"></div>

      <div class="btn-group">
        <button type="button" id="timeInBtn" class="btn timein">Time In</button>
        <button type="button" id="timeOutBtn" class="btn timeout">Time Out</button>
      </div>
    </form>
    <div id="message"></div>
  </div>

  <div class="container" style="margin-top: 30px;">
    <h2>Today's Attendance Logs</h2>
    <table id="attendanceTable">
      <thead>
        <tr>
          <th>Member ID</th>
          <th>Name</th>
          <th>Time In</th>
          <th>Time Out</th>
        </tr>
      </thead>
      <tbody id="attendanceBody">
        </tbody>
    </table>
  </div>

  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
  
  <script src="../assets/js/attendance.js"></script>
</body>
</html>