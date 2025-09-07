<?php
// This file handles the form submissions for manage.php
// It is included at the top of dashboard.php before any HTML output.

// Ensure DB connection is available
require_once __DIR__ . '/../../includes/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for admin role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Don't output anything, just stop. The main page will handle the display error.
    return;
}

// --- Handle POST requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- Handle Delete ---
    if ($_POST['action'] === 'delete_course') {
        $course_id_to_delete = intval($_POST['course_id_to_delete'] ?? 0);
        if ($course_id_to_delete > 0) {
            $conn->begin_transaction();
            try {
                // Delete related data first to maintain foreign key integrity
                db_execute("DELETE FROM course_tag WHERE course_id = ?", "i", [$course_id_to_delete]);
                db_execute("DELETE FROM enrollments WHERE course_id = ?", "i", [$course_id_to_delete]);
                db_execute("DELETE FROM lessons WHERE course_id = ?", "i", [$course_id_to_delete]);
                db_execute("DELETE FROM quizzes WHERE course_id = ?", "i", [$course_id_to_delete]);
                db_execute("DELETE FROM assignments WHERE course_id = ?", "i", [$course_id_to_delete]);
                
                // Finally, delete the course itself
                $delete_stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
                $delete_stmt->bind_param("i", $course_id_to_delete);
                $delete_stmt->execute();
                
                $conn->commit();
                $_SESSION['course_management_success'] = "Course and all its related data have been deleted successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['course_management_error'] = "Error deleting course: " . $e->getMessage();
            }
        }
        // Redirect to the same page to show the result and prevent re-submission
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // --- Handle Inline Update ---
    if ($_POST['action'] === 'update_course_inline') {
        // Get all fields from the submitted form
        $course_id_to_update = intval($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $university_id = intval($_POST['university_id'] ?? 0);
        $status = $_POST['status'] ?? 'draft';
        $course_level = $_POST['course_level'] ?? 'beginner';
        $course_language = trim($_POST['course_language'] ?? 'English');
        $seo_title = trim($_POST['seo_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $prerequisites = trim($_POST['prerequisites'] ?? '');
        $certificate_of_completion = isset($_POST['certificate_of_completion']) ? 1 : 0;
        $enrollment_limit = intval($_POST['enrollment_limit'] ?? 0);
        $instructor_id = intval($_POST['instructor_id'] ?? 0);
        $tags_string = trim($_POST['tags'] ?? '');

        // Basic validation
        $update_errors = [];
        if (empty($title)) $update_errors[] = "Title cannot be empty.";
        if ($price < 0) $update_errors[] = "Price cannot be negative.";
        if ($course_id_to_update <= 0) $update_errors[] = "Invalid Course ID.";
        
        if (empty($update_errors)) {
            $conn->begin_transaction();
            try {
                // 1. Update the main course table
                $updateQuery = "UPDATE courses SET 
                    title = ?, subtitle = ?, description = ?, price = ?, status = ?, 
                    category_id = ?, university_id = ?, instructor_id = ?, course_level = ?, course_language = ?, 
                    seo_title = ?, meta_description = ?, prerequisites = ?, certificate_of_completion = ?, enrollment_limit = ?
                    WHERE id = ?";
                
                $stmt_update = $conn->prepare($updateQuery);

                $cat_id = $category_id > 0 ? $category_id : null;
                $uni_id = $university_id > 0 ? $university_id : null;
                $sub = !empty($subtitle) ? $subtitle : null;
                $seo_t = !empty($seo_title) ? $seo_title : null;
                $meta_d = !empty($meta_description) ? $meta_description : null;
                $prereq = !empty($prerequisites) ? $prerequisites : null;
                $en_limit = $enrollment_limit > 0 ? $enrollment_limit : null;

                $stmt_update->bind_param("sssdssiiissssiii", $title, $sub, $description, $price, $status, $cat_id, $uni_id, $instructor_id, $course_level, $course_language, $seo_t, $meta_d, $prereq, $certificate_of_completion, $en_limit, $course_id_to_update);
                if (!$stmt_update->execute()) { throw new Exception("Failed to update course details: " . $stmt_update->error); }
                $stmt_update->close();

                // 2. Handle Tags
                db_execute("DELETE FROM course_tag WHERE course_id = ?", "i", [$course_id_to_update]);
                if (!empty($tags_string)) {
                    $tags_array = array_unique(array_map('trim', explode(',', $tags_string)));
                    foreach ($tags_array as $tagName) {
                        if (!empty($tagName)) {
                            $tag_id = db_execute("INSERT IGNORE INTO tags (name) VALUES (?)", "s", [$tagName]);
                            if ($tag_id === 0) { $tag_id = db_select("SELECT id FROM tags WHERE name = ?", "s", [$tagName])[0]['id']; }
                            db_execute("INSERT IGNORE INTO course_tag (course_id, tag_id) VALUES (?, ?)", "ii", [$course_id_to_update, $tag_id]);
                        }
                    }
                }
                $conn->commit();
                $_SESSION['course_management_success'] = "Course '{$title}' updated successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['course_management_error'] = "Database Error: " . $e->getMessage();
            }
        } else {
            $_SESSION['course_management_error'] = implode("<br>", $update_errors);
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}