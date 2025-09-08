<?php
// This file handles the form submissions for lessons/manage.php
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authorization check
$userRole = $_SESSION['user_role'] ?? 'student';
if (!isset($_SESSION['user_id']) || !in_array($userRole, ['admin', 'instructor'])) {
    // Silently exit if not authorized. The main page will handle the display.
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- Handle Delete ---
    if ($_POST['action'] === 'delete_lesson') {
        $lesson_id_to_delete = intval($_POST['lesson_id_to_delete'] ?? 0);
        if ($lesson_id_to_delete > 0) {
            // Security check: Instructor can only delete lessons from their own courses
            if ($userRole === 'instructor') {
                $check_sql = "SELECT l.id FROM lessons l JOIN courses c ON l.course_id = c.id WHERE l.id = ? AND c.instructor_id = ?";
                $check_result = db_select($check_sql, 'ii', [$lesson_id_to_delete, $_SESSION['user_id']]);
                if (empty($check_result)) {
                    $_SESSION['lesson_management_error'] = "You do not have permission to delete this lesson.";
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit;
                }
            }

            $conn->begin_transaction();
            try {
                // Delete related resources first
                db_execute("DELETE FROM lesson_resources WHERE lesson_id = ?", "i", [$lesson_id_to_delete]);
                
                // Finally, delete the lesson itself
                db_execute("DELETE FROM lessons WHERE id = ?", "i", [$lesson_id_to_delete]);
                
                $conn->commit();
                $_SESSION['lesson_management_success'] = "Lesson has been deleted successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['lesson_management_error'] = "Error deleting lesson: " . $e->getMessage();
            }
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // --- Handle Inline Update ---
    if ($_POST['action'] === 'update_lesson_inline') {
        // Get all fields from the submitted form
        $lesson_id = intval($_POST['lesson_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $order_no = intval($_POST['order_no'] ?? 0);
        $duration = trim($_POST['duration'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $is_preview = isset($_POST['is_preview']) ? 1 : 0;
        $is_locked = isset($_POST['is_locked']) ? 1 : 0;
        $release_date = !empty($_POST['release_date']) ? trim($_POST['release_date']) : null;
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $assignment_id = intval($_POST['assignment_id'] ?? 0);

        // Basic validation
        if (empty($title) || $lesson_id <= 0) {
            $_SESSION['lesson_management_error'] = "Title is required and Lesson ID must be valid.";
        } else {
            $updateQuery = "UPDATE lessons SET 
                course_id = ?, title = ?, description = ?, order_no = ?, duration = ?, 
                status = ?, is_preview = ?, release_date = ?, is_locked = ?, quiz_id = ?, assignment_id = ?
                WHERE id = ?";
            
            $q_id = $quiz_id > 0 ? $quiz_id : null;
            $a_id = $assignment_id > 0 ? $assignment_id : null;

            $success = db_execute($updateQuery, "ississisiiii", [
                $course_id, $title, $description, $order_no, $duration,
                $status, $is_preview, $release_date, $is_locked, $q_id, $a_id,
                $lesson_id
            ]);

            $_SESSION['lesson_management_success'] = "Lesson '{$title}' updated successfully!";
        }
        
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}