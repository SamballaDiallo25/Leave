<?php
session_start(); // Start the session

// Clear all session data
$_SESSION = array();
session_unset();

// Destroy the session
session_destroy();


header("Location: ../index.php"); 
exit();
?>
