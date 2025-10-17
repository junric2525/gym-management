<?php
// Start the session to access and destroy session data
session_start();

// 1. Clear all session variables
// This removes specific user/admin data like 'user_id', 'role', etc.
$_SESSION = [];

// 2. Destroy the session cookie (Crucial for complete security)
// This immediately tells the browser to discard the session identifier.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session file on the server
session_destroy();

// 4. Redirect to the guest page
// Using a path relative to the site's root directory is generally the safest method 
// for redirects from different subfolders (like /Admin and /User).
header("Location: /gym-management/Guest/index.php");
exit();
?>