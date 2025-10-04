<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Member Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap">
    <link rel="stylesheet" href="../assets/css/profile.css"
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div class="container header-flex">
            <div class="logo">
                <div class="logo-img"></div>
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <nav class="nav-desktop">
                <a href="User.php"><i class="fas fa-home"></i> Home</a>
                <a href="User.php#services"><i class="fas fa-dumbbell"></i> Services</a>
                <a href="member_register.php"><i class="fas fa-id-card"></i> Membership Registration</a>
                <a href="User.php#about"><i class="fas fa-info-circle"></i> About Us</a>
                <div class="profile-dropdown">
                    <button class="profile-btn"><i class="fas fa-user"></i> <i class="fas fa-caret-down"></i></button>
                    <div class="dropdown-menu">
                        <a href="Profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </nav>
            <button onclick="toggleMenu()" class="menu-btn"><i class="fas fa-bars"></i></button>
        </div>
        <div id="mobileMenu" class="nav-mobile">
            <a href="User.php"><i class="fas fa-home"></i> Home</a>
            <a href="User.php#services"><i class="fas fa-dumbbell"></i> Services</a>
            <a href="member_register.php"><i class="fas fa-id-card"></i> Membership Registration</a>
            <a href="User.php#about"><i class="fas fa-info-circle"></i> About Us</a>
            <a href="Profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <!-- PROFILE CONTENT -->
    <main class="profile-container" id="profile">
        <div class="profile-card">
            <h1 class="section-title">My Profile</h1>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <button id="burgerBtn" class="burger-btn"><i class="fas fa-bars"></i> Quick Actions</button>
                <nav id="burgerNav" class="burger-nav">
                    <a href="subscription.php"><i class="fas fa-credit-card"></i> View Subscriptions</a>
                    <a href="renewal.html"><i class="fas fa-sync-alt"></i> Renew Membership</a>
                    <a href="coach_appointment.html"><i class="fas fa-calendar-check"></i> Book Coach</a>
                    <a href="gym_evalchoice.html"><i class="fas fa-dumbbell"></i> Gym Evaluation</a>
                    <a href="invoice.html"><i class="fas fa-file-invoice"></i> View Invoices</a>
                </nav>
            </div>

            <!-- Personal Info -->
            <div class="profile-info">
                <div class="info-grid">
                    <p><strong>Member ID:</strong> <span><?php echo $displayMemberID; ?></span></p>
                    <p><strong>Name:</strong> <span><?php echo $displayName; ?></span></p>
                    <p><strong>Email:</strong> <span><?php echo $displayEmail; ?></span></p>
                    <p><strong>Contact:</strong> <span><?php echo $displayContact; ?></span></p>
                    <p><strong>Date of Birth:</strong> <span><?php echo $displayDob; ?></span></p>
                    <p><strong>Address:</strong> <span><?php echo $displayAddress; ?></span></p>
                    <p><strong>Emergency Contact Name:</strong> <span><?php echo $displayEmergencyName; ?></span></p>
                    <p><strong>Emergency Contact Number:</strong> <span><?php echo $displayEmergencyNum; ?></span></p>
                    <p><strong>Medical Conditions:</strong> <span><?php echo $displayMedConditions; ?></span></p>
                    <p><strong>Medical Details:</strong> <span><?php echo $displayMedDetails; ?></span></p>
                    <p><strong>Medications:</strong> <span><?php echo $displayMedications; ?></span></p>
                    <p><strong>Medication Details:</strong> <span><?php echo $displayMedsDetails; ?></span></p>
                </div>
            </div>

            <!-- Membership & Subscription -->
            <div class="combined-details">
                <h3>Membership & Subscription Status</h3>
                <div class="details-grid">
                    <!-- Membership -->
                    <div class="inner-box">
                        <h4>Membership Status</h4>
                        <p><strong>Start Date:</strong> <span><?php echo $startDate; ?></span></p>
                        <p><strong>Expiry Date:</strong> <span><?php echo $expiryDate; ?></span></p>
                        <p><strong>Overall Status:</strong>
                            <span class="<?php echo ($membershipStatus == 'Active' ? 'status-active' : 'status-inactive'); ?>">
                                <?php echo $membershipStatus; ?>
                            </span>
                        </p>
                        <?php if (!$hasMembership): ?>
                            <p class="warning"><i class="fas fa-exclamation-triangle"></i> You do not have an approved membership yet.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Subscription -->
                    <div class="inner-box">
                        <h4>Latest Subscription</h4>
                        <?php if ($subscription): ?>
                            <p><strong>Type:</strong> <span><?php echo htmlspecialchars($subscription['subscription_type']); ?></span></p>
                            <p><strong>Period Start:</strong> <span><?php echo htmlspecialchars($subscription['start_date']); ?></span></p>
                            <p><strong>Period End:</strong> <span><?php echo $subscription['end_date'] ? htmlspecialchars($subscription['end_date']) : 'N/A'; ?></span></p>
                            <p><strong>Payment Status:</strong>
                                <span class="<?php echo ($subscription['status'] == 'active' ? 'status-active' : 'status-inactive'); ?>">
                                    <?php echo htmlspecialchars($subscription['status']); ?>
                                </span>
                            </p>
                        <?php else: ?>
                            <p class="warning"><i class="fas fa-exclamation-circle"></i> No subscription found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <button id="editProfileBtn" class="btn-edit"><i class="fas fa-edit"></i> Update Personal Details</button>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-about">
                <h3>CHARLES GYM</h3>
                <p>World-class fitness training in a supportive and motivating environment.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="User.php#home">Home</a>
                <a href="User.php#about">About Us</a>
                <a href="User.php#services">Services</a>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-map-marker-alt"></i> Unit 21, Landsdale Tower, QC</p>
                <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                <p><i class="fa-brands fa-google"></i> charlesgym@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
