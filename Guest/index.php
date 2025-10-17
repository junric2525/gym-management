<?php
// Include the database connection file
require_once '../backend/db.php'; 

// Initialize FALLBACK PROMO PATH
$promo_image_path = 'assets/img/default-promo.jpg'; 

// ---------------------------------------------
// FETCH PROMO IMAGE PATH
// ---------------------------------------------
if ($conn) {
    $sql_promo = "SELECT promo_image_path FROM promotions WHERE id = 1 LIMIT 1";
    
    if ($result_promo = $conn->query($sql_promo)) {
        if ($row_promo = $result_promo->fetch_assoc()) {
            // Overwrite the fallback path with the database path
            // Use htmlspecialchars() for consistency and safety.
            $promo_image_path = htmlspecialchars($row_promo['promo_image_path']);
        }
        $result_promo->close();
    }
    
    // Close the connection at the end of all operations.
    $conn->close();
} else {
    error_log("Database connection object (\$conn) is not available.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Charles Gym</title>
  <link rel="stylesheet" href="../assets/css/index.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
</head>

<body>

  <header class="header">
    <div class="container header-flex">

      <div class="logo">
        <img src="../assets/img/logo.png" alt="Logo" class="logo-img" />
        <h1 class="logo-text">Charles Gym</h1>
      </div>

      <nav class="nav-desktop">
        <a href="index.php#home"><i class="fas fa-home"></i> Home</a>
        <a href="index.php#services"><i class="fas fa-dumbbell"></i> Services</a>
        <a href="index.php#about"><i class="fas fa-info-circle"></i> About Us</a>
        <button onclick="window.location.href='signin.html'"><i class="fas fa-user"></i> Sign In</button>
        <button onclick="window.location.href='signup.html'"><i class="fas fa-user-plus"></i> Sign Up</button>
      </nav>

      <button onclick="toggleMenu()" class="menu-btn">
        <i id="menuIcon" class="fas fa-bars"></i>
      </button>

    </div>

    <div id="mobileMenu" class="nav-mobile">
      <a href="index.php#home"><i class="fas fa-home"></i> Home</a>
      <a href="index.php#services"><i class="fas fa-dumbbell"></i> Services</a>
      <a href="index.php#about"><i class="fas fa-info-circle"></i> About Us</a>
      <button class="btn-signin" onclick="window.location.href='signin.html?showLogin=true'"><i class="fas fa-user"></i> Sign In</button>
      <button onclick="window.location.href='signup.html'"><i class="fas fa-user-plus"></i> Sign Up</button>
    </div>
  </header>

  <section id="home" class="hero">
    <div class="container text-center">
      <h1 class="hero-title">Welcome to <span>Charles Gym</span></h1>
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
        
        <img id="promoImage" src="../../<?php echo htmlspecialchars($promo_image_path); ?>" alt="Promo Event">
        <h3 id="promoTitle">Promo of Gym</h3>
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
          <p>yoga sessions to relax your body and mind.</p>
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
        <a href="faq.html">FAQ</a>
      <a href="terms_condition.html">Terms</a>
      </div>
      <div class="footer-contact">
        <h4>Contact Us</h4>
        <p><i class="fas fa-map"></i> Unit 21, Landsdale Tower, QC</p>
        <p><i class="fas fa-phone"></i> (555) 123-4567</p>
        <p><i class="fa-brands fa-google"></i> charlesgym@gmail.com</p>
      </div>
    </div>
    <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
  </footer>

  <script src="../assets/js/index.js"></script>
</body>
</html>
