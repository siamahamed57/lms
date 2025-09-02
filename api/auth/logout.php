<?php


// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header('Location:account');
exit;
