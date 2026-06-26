<?php
// Start the session
session_start();

// Destroy all session data
$_SESSION = array(); // Clear all session variables

// If you want to delete the session cookie as well:
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>