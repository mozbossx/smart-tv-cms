<?php
@include 'config_connection.php';
session_start();
session_unset();
session_destroy();

// Add cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login.php?pageid=Login
header("Location: login.php?pageid=Login");
exit; // Ensure no further code execution after redirection
?>