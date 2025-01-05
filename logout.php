<?php
// Set a custom session name for admins
session_name('admin_session');

// Start the session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy the session
session_destroy();

// Redirect to the admin login page
header("Location: adminLogin.php");
exit; // Ensure no further code is executed
?>