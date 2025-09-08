<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = [];
session_destroy();

// Redirect to account page, using a relative path that works from this script's location.
header("Location: ../../account");
exit;
