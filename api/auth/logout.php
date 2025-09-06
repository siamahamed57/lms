<?php

// Clear all session data
$_SESSION = [];
session_destroy();

// Redirect to home page
header("Location:account");
exit;
