<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// --- Authorization Check ---
$userRole = $_SESSION['user_role'] ?? 'student';
if ($userRole !== 'admin') {
    $_SESSION['enrollment_error'] = "You are not authorized to perform this action.";
    header('Location: ../dashboard?page=enrollment-management');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- Enroll Student Action ---
    if ($action === 'enroll_student') {
        $student_id = intval($_POST['student_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);

        if ($student_id > 0 && $course_id > 0) {
            $existing = db_select("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?", "ii", [$student_id, $course_id]);
            if (!empty($existing)) {
                $_SESSION['enrollment_error'] = "This student is already enrolled in this course.";
            } else {
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 year'));
                // Insert new enrollment. Progress and status will use database defaults.
                db_execute("INSERT INTO enrollments (student_id, course_id, expires_at) VALUES (?, ?, ?)", "iis", [$student_id, $course_id, $expires_at]);
                $_SESSION['enrollment_success'] = "Student successfully enrolled.";
            }
        } else {
            $_SESSION['enrollment_error'] = "Invalid student or course selected.";
        }
    }

    // --- Update Enrollment Status (Block/Unblock) ---
    elseif ($action === 'update_enrollment_status') {
        $enrollment_id = intval($_POST['enrollment_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';

        if ($enrollment_id > 0 && in_array($new_status, ['active', 'blocked'])) {
            db_execute("UPDATE enrollments SET status = ? WHERE id = ?", "si", [$enrollment_id, $new_status]);
            $_SESSION['enrollment_success'] = "Enrollment status updated successfully.";
        } else {
            $_SESSION['enrollment_error'] = "Invalid data provided for status update.";
        }
    }

    // --- Delete Enrollment ---
    elseif ($action === 'delete_enrollment') {
        $enrollment_id = intval($_POST['enrollment_id'] ?? 0);

        if ($enrollment_id > 0) {
            db_execute("DELETE FROM enrollments WHERE id = ?", "i", [$enrollment_id]);
            $_SESSION['enrollment_success'] = "Enrollment has been deleted.";
        } else {
            $_SESSION['enrollment_error'] = "Invalid enrollment ID for deletion.";
        }
    }

    else {
        $_SESSION['enrollment_error'] = "Unknown action.";
    }
}

header('Location: ../dashboard?page=enrollment-management');
exit;
?>