<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];

    // Sanitize and retrieve form data
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $roll_no = trim($_POST['roll_no'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    // Basic validation
    if (empty($name) || empty($phone) || empty($university) || empty($department)) {
        $_SESSION['profile_error'] = "Please fill in all required fields (Name, Phone, University, Department).";
    } else {
        // Update user data in the database
        $sql = "UPDATE users SET name = ?, phone = ?, university = ?, department = ?, roll_no = ?, bio = ? WHERE id = ?";
        $updated = db_execute($sql, "ssssssi", [$name, $phone, $university, $department, $roll_no, $bio, $student_id]);

        if ($updated) {
            $_SESSION['profile_success'] = "Your profile has been updated successfully!";
        } else {
            $_SESSION['profile_error'] = "There was an error updating your profile. Please try again.";
        }
    }

    header('Location: dashboard?page=profile');
    exit;
}