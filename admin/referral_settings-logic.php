<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Silently fail or redirect, but for now, just exit.
    exit;
}

// Handle POST request to update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_referral_settings'])) {
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;
    $reward_type = in_array($_POST['reward_type'], ['fixed', 'percentage']) ? $_POST['reward_type'] : 'fixed';
    $reward_value = filter_input(INPUT_POST, 'reward_value', FILTER_VALIDATE_FLOAT);

    if ($course_id && $reward_value >= 0) {
        $sql = "INSERT INTO referral_settings (course_id, is_enabled, reward_type, reward_value) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                is_enabled = VALUES(is_enabled), 
                reward_type = VALUES(reward_type), 
                reward_value = VALUES(reward_value)";
        
        db_execute($sql, 'iisd', [$course_id, $is_enabled, $reward_type, $reward_value]);
        
        $_SESSION['success_message'] = "Referral settings updated successfully.";
    } else {
        $_SESSION['error_message'] = "Invalid data provided for referral settings.";
    }
    header('Location: dashboard?page=referral-settings');
    exit;
}