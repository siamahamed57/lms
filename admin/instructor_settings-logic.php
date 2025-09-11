<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    exit;
}

// Handle POST request to update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_commission_settings'])) {
    $instructor_id = filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT);
    $commission_rate = filter_input(INPUT_POST, 'commission_rate', FILTER_VALIDATE_FLOAT);

    if ($instructor_id && $commission_rate >= 0 && $commission_rate <= 100) {
        $sql = "UPDATE users SET commission_rate = ? WHERE id = ? AND role = 'instructor'";
        db_execute($sql, 'di', [$commission_rate, $instructor_id]);
        
        $_SESSION['success_message'] = "Commission rate updated successfully.";
    } else {
        $_SESSION['error_message'] = "Invalid data provided. Commission rate must be between 0 and 100.";
    }
    header('Location: dashboard?page=instructor-settings');
    exit;
}