<?php

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Example: Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

session_start();
include '../backend/db.php'; // <-- FIX IS HERE

// CRITICAL SECURITY CHECK (Keep this)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}
// If the admin is logged in, continue to display the dashboard HTML below.

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
<body>

<header class="header">
    <div class="container header-flex">

        <div class="logo">
            <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
            <h1 class="logo-text">Charles Gym</h1>
        </div>

        <div class="profile-dropdown">
            <button class="profile-btn">
                <i class="fas fa-user"></i> <i class="fas fa-caret-down"></i>
            </button>
            <div class="dropdown-menu">
                <a href="Admin.php"><i class="fas fa-user"></i>Home</a>
                <a href="../Guest/index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

    </div>
</header>


<main class="dashboard-grid-new">
    <div class="dashboard-section-title">
        <h2>Gym Management Modules</h2>
        <p>Quick access to administrative functions.</p>
    </div>

    <div class="cards-container">
        <a href="membership_manage.php" class="dashboard-card-new admin-focus">
            <i class="fa fa-id-card fa-3x card-icon"></i>
            <span class="card-title">Membership Management</span>
            <p class="card-description">View and manage all active gym members.</p>
        </a>
         
        <a href="pending_renewal.php" class="dashboard-card-new">
            <i class="fa fa-user-clock fa-3x card-icon"></i>
            <span class="card-title">Pending Renewal</span>
            <p class="card-description">View and manage the pending Renewal members.</p>
        </a>

        <a href="payment_pendingview.php" class="dashboard-card-new admin-attention">
            <i class="fa fa-user-clock fa-3x card-icon"></i>
            <span class="card-title">Pending Membership</span>
            <p class="card-description">Review and approve new member applications.</p>
        </a>

        <a href="subscriptionview.php" class="dashboard-card-new">
            <i class="fa fa-receipt fa-3x card-icon"></i>
            <span class="card-title">Subscription Management</span>
            <p class="card-description">Handle renewal status and plan details for members.</p>
        </a>
        <a href="pending_subscription.php" class="dashboard-card-new admin-attention">
            <i class="fa fa-file-invoice-dollar fa-3x card-icon"></i>
            <span class="card-title">Pending Subscription</span>
            <p class="card-description">Process and verify pending subscription renewals.</p>
        </a>
        
        <a href="attendance_monitoring.php" class="dashboard-card-new">
            <i class="fa fa-check-circle fa-3x card-icon"></i>
            <span class="card-title">Attendance Monitoring</span>
            <p class="card-description">Track member check-ins and attendance records.</p>
        </a>
        <a href="attendance_history.php" class="dashboard-card-new">
            <i class="fa fa-check-circle fa-3x card-icon"></i>
            <span class="card-title">Attendance History</span>
            <p class="card-description">Track member check-ins and attendance records.</p>
        </a>
        <a href="coach_update.php" class="dashboard-card-new">
            <i class="fa fa-user-shield fa-3x card-icon"></i>
            <span class="card-title">Adding Coach</span>
            <p class="card-description">adding coach information.</p>
        </a>

         <a href="coach_appointmentview.php" class="dashboard-card-new">
            <i class="fa fa-user-shield fa-3x card-icon"></i>
            <span class="card-title">Coach Appointment List</span>
            <p class="card-description">Appointment List.</p>
        </a>
        
        
        <a href="promo_event.php" class="dashboard-card-new admin-focus">
            <i class="fa fa-bullhorn fa-3x card-icon"></i>
            <span class="card-title">Updating Event/Promo</span>
            <p class="card-description">Create and modify current marketing events and promotions.</p>
        </a>
        <a href="coach_evalmanage.php" class="dashboard-card-new">
            <i class="fa fa-chart-line fa-3x card-icon"></i>
            <span class="card-title">Evaluation</span>
            <p class="card-description">Gym and Coach Evaluations.</p>
        </a>

    
    </div>
</main>


    <script src="../assets/js/admindashboard.js" defer></script>
</body>
</html>