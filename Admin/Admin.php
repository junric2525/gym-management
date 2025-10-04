<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    // If not logged in, redirect them to the login page
    // Adjust the path to your actual login page (e.g., index.html or login.php)
    header("Location: /gym-management/login.html");
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
                <a href="AdminDashboard.html"><i class="fas fa-user"></i>Home</a>
                <a href="../Guest/Index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
            <p class="card-description">View, edit, and manage all active gym members.</p>
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
        
        <a href="Attendance.html" class="dashboard-card-new">
            <i class="fa fa-check-circle fa-3x card-icon"></i>
            <span class="card-title">Attendance Monitoring</span>
            <p class="card-description">Track member check-ins and attendance records.</p>
        </a>
        <a href="Staff.html" class="dashboard-card-new">
            <i class="fa fa-user-shield fa-3x card-icon"></i>
            <span class="card-title">Staff Management</span>
            <p class="card-description">Manage staff profiles, roles, and access permissions.</p>
        </a>
        
        <a href="UpdatingPromo.html" class="dashboard-card-new admin-focus">
            <i class="fa fa-bullhorn fa-3x card-icon"></i>
            <span class="card-title">Updating Event/Promo</span>
            <p class="card-description">Create and modify current marketing events and promotions.</p>
        </a>
        <a href="PerformanceAnalytics.html" class="dashboard-card-new">
            <i class="fa fa-chart-line fa-3x card-icon"></i>
            <span class="card-title">Performance Analytics</span>
            <p class="card-description">View key gym performance metrics and data trends.</p>
        </a>
    </div>
</main>
<footer class="footer">
        <div class="container footer-grid">
            <div class="footer-about">
                <h3>CHARLES GYM</h3>
                <p>
                    World-class fitness training in a supportive and motivating environment.
                </p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="#home">Home</a>
                <a href="#about">About Us</a>
                <a href="#services">Services</a>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p>📍 Unit 21, Landsdale Tower, QC</p>
                <p>📞 (555) 123-4567</p>
                <p>✉ charlesgym@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
    </footer>

    <script src="../assets/js/admindashboard.js"></script>
</body>
</html>