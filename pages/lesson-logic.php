<?php
// This file is included from dashboard.php to handle form submissions for lesson.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// This logic is moved from lesson.php to handle form submission before any HTML output.
if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_complete') {
    require_once __DIR__ . '/../includes/db.php'; // Ensure db functions are available
    $student_id = $_SESSION['user_id'];
    $lesson_to_complete = intval($_POST['lesson_id_to_complete'] ?? 0);
    $current_lesson_id = intval($_GET['id'] ?? 0);

    if ($lesson_to_complete > 0 && $current_lesson_id > 0) {
        // Use INSERT IGNORE to prevent errors on re-completion
        db_execute(
            "INSERT IGNORE INTO student_lesson_completion (student_id, lesson_id) VALUES (?, ?)",
            "ii",
            [$student_id, $lesson_to_complete]
        );
        // Redirect to the same page to show the updated status and prevent re-submission on refresh
        header("Location: ?page=lesson&id=" . $current_lesson_id);
        exit;
    }
}