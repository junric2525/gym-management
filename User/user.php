<?php
session_start();
// Include your database connection file
require_once '../backend/db.php'; 

$username = "Member"; // Default name if not logged in or name not found

if (isset($_SESSION['user_id'])) {
    $target_user_id = $_SESSION['user_id'];
    
    // Fetch user's first name for a personalized greeting
    // NOTE: This assumes your 'users' table has a 'first_name' column.
    $sql = "SELECT first_name FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $target_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if ($user_data && isset($user_data['first_name'])) {
        $username = htmlspecialchars($user_data['first_name']);
    }
} else {
    // Optional: Redirect non-logged-in users back to the index/login page
    // header("Location: ../Guest/Index.html");
    // exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
    <title>Charles Gym - User</title>
</head>
<body>

    <header class="header">
        <div class="container header-flex">

            <div class="logo">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
                <h1 class="logo-text">Charles Gym</h1>
            </div>

            <nav class="nav-desktop">
                <a href="#home"><i class="fas fa-home"></i> Home</a>
                <a href="#services"><i class="fas fa-dumbbell"></i> Services</a>
                <a href="member_register.php"><i class="fas fa-id-card"></i> Membership Registration</a>
                <a href="#about"><i class="fas fa-info-circle"></i> About Us</a>

                <div class="profile-dropdown">
                    <button class="profile-btn">
                        <i class="fas fa-user"></i> <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </nav>

            <button onclick="toggleMenu()" class="menu-btn">
                <i class="fas fa-user"></i>
            </button>

        </div>

        <div id="mobileMenu" class="nav-mobile">
            <a href="#home"><i class="fas fa-home"></i> Home</a>
            <a href="#services"><i class="fas fa-dumbbell"></i> Services</a>
            <a href="member_register.php"><i class="fas fa-id-card"></i> Membership Registration</a>
            <a href="#about"><i class="fas fa-info-circle"></i> About Us</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../Guest/index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <section id="home" class="hero">
        <div class="container text-center">
            <h1 class="hero-title">Welcome, <span><?php echo $username; ?></span> to Charles Gym!</h1>
            <p class="hero-sub">Train hard, stay strong, and become the best version of yourself.</p>
            <p class="hero-desc">
                At Charles Hardcore Gym, we build not just muscle—but discipline, confidence, and community.
                From beginners to athletes, we help you crush your fitness goals with expert trainers and top-tier equipment.
            </p>
            <div class="hero-buttons">
                <a href="#services" class="btn btn-primary">Explore Services</a>
            </div>
        </div>
    </section>
    
    <section id="location" class="map-promo">
        <div class="container map-promo-flex">

            <div class="map-box text-center">
                <h2 class="section-title"><i class="fas fa-map"></i> Find Us Here</h2>
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.353434582472!2d121.02567507385491!3d14.635867785854643!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b7004e534ce3%3A0x2ecd59fd6ed6885e!2sCharles%20Hardcore%20Gym!5e0!3m2!1sen!2sph!4v1756716467717!5m2!1sen!2sph" 
                    width="100%" 
                    height="350" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

            <div class="promo-box text-center">
                <h2 class="section-title"><i class="fas fa-bullhorn"></i> Latest Promo</h2>
                <img id="promoImage" src="assets/img/default-promo.jpg" alt="Promo Event">
                <h3 id="promoTitle">Special Gym Promo Coming Soon!</h3>
            </div>

        </div>
    </section>

    <section id="services" class="services">
        <div class="container">
            <h2 class="section-title text-center"><i class="fas fa-dumbbell"></i> Our Services</h2>
            <div class="grid">
                <div class="service-card">   
                    <i class="fas fa-dumbbell"></i>
                    <h3>Strength Training</h3>
                    <p>Professional weightlifting equipment and personal coaching sessions.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-running"></i>
                    <h3>Cardio Fitness</h3>
                    <p>Modern treadmills, cycling, and HIIT training to burn fat fast.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-spa"></i>
                    <h3>Wellness & Recovery</h3>
                    <p>Sauna, massage therapy, and yoga sessions to relax your body and mind.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="about">
        <div class="container text-center">
            <h2 class="section-title"><i class="fas fa-address-card"></i> About Charles Gym</h2>
            <div class="about-card">

                <div class="about-img">
                    <div class="aboutimg">
                        <img src="../assets/img/aboutus.jpg" alt="About Us"/>
                    </div>
                </div>

                <div class="aboutus">
                    <p>
                        Since 2010, Charles Gym has been transforming lives through fitness. 
                        Our mission is to provide a welcoming and empowering environment where everyone can achieve their goals.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-about">
                <h3>CHARLES GYM</h3>
                <p>World-class fitness training in a supportive and motivating environment.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="#home">Home</a>
                <a href="#about">About Us</a>
                <a href="#services">Services</a>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-map"></i> Unit 21, Landsdale Tower, QC</p>
                <p><i class="fas fa-phone"></i>  (555) 123-4567</p>
                <p><i class="fa-brands fa-google"></i> charlesgym@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
    </footer>

    <script src="../assets/js/user.js"></script>
</body>
</html>