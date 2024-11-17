<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Delete the session cookie (if exists)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Add security headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Location: index.php");
exit();
?>
