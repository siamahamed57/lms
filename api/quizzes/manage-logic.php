<?php
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authorization check
$userRole = $_SESSION['user_role'] ?? 'student';
if (!isset($_SESSION['user_id']) || !in_array($userRole, ['admin', 'instructor'])) {
    $_SESSION['quiz_management_error'] = "You are not authorized to perform this action.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- Handle Create ---
    if ($_POST['action'] === 'create_quiz') {
        $title = trim($_POST['title'] ?? '');
        $course_id = intval($_POST['course_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $duration = intval($_POST['duration'] ?? 0);
        $attempts_allowed = intval($_POST['attempts_allowed'] ?? 1);
        $pass_mark = intval($_POST['pass_mark'] ?? 50);
        $status = $_POST['status'] ?? 'draft';
        $randomize_questions = isset($_POST['randomize_questions']) ? 1 : 0;
        $show_feedback_immediately = isset($_POST['show_feedback_immediately']) ? 1 : 0;

        $errors = [];
        if (empty($title)) $errors[] = "Quiz title is required.";
        if ($course_id <= 0) $errors[] = "A valid course must be selected.";

        if (empty($errors)) {
            $sql = "INSERT INTO quizzes (course_id, title, description, duration, status, attempts_allowed, pass_mark, randomize_questions, show_feedback_immediately) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $new_quiz_id = db_execute($sql, "isssisiii", [
                $course_id, $title, $description, $duration, $status, 
                $attempts_allowed, $pass_mark, $randomize_questions, $show_feedback_immediately
            ]);

            if ($new_quiz_id) {
                $_SESSION['quiz_creation_success'] = "Quiz '{$title}' created successfully! You can now add questions.";
                // Redirect to the same page but with the new quiz ID to show the question editor
                header('Location: ../../dashboard?_page=dashboard&page=create-quiz&quiz_id=' . $new_quiz_id);
                exit;
            } else {
                $_SESSION['quiz_creation_errors'] = ["Database error: Could not create the quiz."];
            }
        } else {
            $_SESSION['quiz_creation_errors'] = $errors;
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']); // On error, redirect back to the form
        exit;
    }

    // --- Handle Add Question ---
    if ($_POST['action'] === 'add_question') {
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $question_text = trim($_POST['question_text'] ?? '');
        $question_type = $_POST['question_type'] ?? 'mcq';
        $marks = intval($_POST['marks'] ?? 1);

        $errors = [];
        if ($quiz_id <= 0) $errors[] = "Invalid Quiz ID.";
        if (empty($question_text)) $errors[] = "Question text cannot be empty.";
        if (!in_array($question_type, ['mcq', 'true_false', 'short_answer', 'essay', 'fill_in_the_blank', 'matching', 'code'])) {
            $errors[] = "Invalid question type.";
        }

        if (empty($errors)) {
            global $conn;
            $conn->begin_transaction();
            try {
                $sql_question = "INSERT INTO questions (quiz_id, question_text, type, marks) VALUES (?, ?, ?, ?)";
                $question_id = db_execute($sql_question, "issi", [$quiz_id, $question_text, $question_type, $marks]);

                if (!$question_id) {
                    throw new Exception("Failed to create the question record.");
                }

                if ($question_type === 'mcq') {
                    $options = $_POST['options'] ?? [];
                    $correct_option_index = isset($_POST['correct_option']) ? intval($_POST['correct_option']) : -1;
                    if (count($options) < 2 || $correct_option_index < 0) throw new Exception("MCQs require at least 2 options and a correct answer.");
                    
                    $sql_option = "INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
                    foreach ($options as $index => $option_text) {
                        if (!empty(trim($option_text))) db_execute($sql_option, "isi", [$question_id, trim($option_text), ($index === $correct_option_index) ? 1 : 0]);
                    }
                } elseif ($question_type === 'true_false') {
                    $correct_answer = $_POST['true_false_correct'] ?? '';
                    if (!in_array($correct_answer, ['true', 'false'])) throw new Exception("A correct answer (True or False) must be selected.");
                    $sql_option = "INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
                    db_execute($sql_option, "isi", [$question_id, 'True', ($correct_answer === 'true' ? 1 : 0)]);
                    db_execute($sql_option, "isi", [$question_id, 'False', ($correct_answer === 'false' ? 1 : 0)]);
                }
                $conn->commit();
                $_SESSION['quiz_question_success'] = "Question added successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['quiz_question_error'] = "Error: " . $e->getMessage();
            }
        } else {
            $_SESSION['quiz_question_error'] = implode("<br>", $errors);
        }
        header('Location: ../../dashboard?_page=dashboard&page=create-quiz&quiz_id=' . $quiz_id);
        exit;
    }

    // --- Handle Update Question ---
    if ($_POST['action'] === 'update_question') {
        $question_id = intval($_POST['question_id'] ?? 0);
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $question_text = trim($_POST['question_text'] ?? '');
        $question_type = $_POST['question_type'] ?? 'mcq';
        $marks = intval($_POST['marks'] ?? 1);

        $errors = [];
        if ($question_id <= 0) $errors[] = "Invalid Question ID.";
        if ($quiz_id <= 0) $errors[] = "Invalid Quiz ID.";
        if (empty($question_text)) $errors[] = "Question text cannot be empty.";

        if (empty($errors)) {
            global $conn;
            $conn->begin_transaction();
            try {
                // Update the question itself
                $sql_update_q = "UPDATE questions SET question_text = ?, type = ?, marks = ? WHERE id = ? AND quiz_id = ?";
                db_execute($sql_update_q, "ssiii", [$question_text, $question_type, $marks, $question_id, $quiz_id]);

                // Delete old options before inserting new ones
                db_execute("DELETE FROM quiz_options WHERE question_id = ?", "i", [$question_id]);

                // Re-add options based on type (same logic as add_question)
                if ($question_type === 'mcq') {
                    $options = $_POST['options'] ?? [];
                    $correct_option_index = isset($_POST['correct_option']) ? intval($_POST['correct_option']) : -1;
                    if (count($options) < 2 || $correct_option_index < 0) throw new Exception("MCQs require at least 2 options and a correct answer.");
                    
                    $sql_option = "INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
                    foreach ($options as $index => $option_text) {
                        if (!empty(trim($option_text))) db_execute($sql_option, "isi", [$question_id, trim($option_text), ($index === $correct_option_index) ? 1 : 0]);
                    }
                } elseif ($question_type === 'true_false') {
                    $correct_answer = $_POST['true_false_correct'] ?? '';
                    if (!in_array($correct_answer, ['true', 'false'])) throw new Exception("A correct answer (True or False) must be selected.");
                    $sql_option = "INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
                    db_execute($sql_option, "isi", [$question_id, 'True', ($correct_answer === 'true' ? 1 : 0)]);
                    db_execute($sql_option, "isi", [$question_id, 'False', ($correct_answer === 'false' ? 1 : 0)]);
                }

                $conn->commit();
                $_SESSION['quiz_question_success'] = "Question updated successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['quiz_question_error'] = "Error updating question: " . $e->getMessage();
            }
        } else {
            $_SESSION['quiz_question_error'] = implode("<br>", $errors);
        }
        header('Location: ../../dashboard?_page=dashboard&page=create-quiz&quiz_id=' . $quiz_id);
        exit;
    }

    // --- Handle Delete Question ---
    if ($_POST['action'] === 'delete_question') {
        $question_id = intval($_POST['question_id_to_delete'] ?? 0);
        $quiz_id = intval($_POST['quiz_id'] ?? 0); // For redirect

        if ($question_id > 0) {
            // In a transaction to ensure both question and its options are deleted
            $conn->begin_transaction();
            try {
                db_execute("DELETE FROM quiz_options WHERE question_id = ?", "i", [$question_id]);
                db_execute("DELETE FROM questions WHERE id = ?", "i", [$question_id]);
                $conn->commit();
                $_SESSION['quiz_question_success'] = "Question deleted successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['quiz_question_error'] = "Error deleting question: " . $e->getMessage();
            }
        } else {
            $_SESSION['quiz_question_error'] = "Invalid question ID provided for deletion.";
        }
        header('Location: ../../dashboard?_page=dashboard&page=create-quiz&quiz_id=' . $quiz_id);
        exit;
    }

    // --- Handle Inline Update ---
    if ($_POST['action'] === 'update_quiz_inline') {
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $course_id = intval($_POST['course_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $duration = intval($_POST['duration'] ?? 0);
        $attempts_allowed = intval($_POST['attempts_allowed'] ?? 1);
        $pass_mark = intval($_POST['pass_mark'] ?? 50);
        $status = $_POST['status'] ?? 'draft';
        $randomize_questions = isset($_POST['randomize_questions']) ? 1 : 0;
        $show_feedback_immediately = isset($_POST['show_feedback_immediately']) ? 1 : 0;

        if (empty($title) || $quiz_id <= 0) {
            $_SESSION['quiz_management_error'] = "Title is required and Quiz ID must be valid.";
        } else {
            // Security check for instructors
            if ($userRole === 'instructor') {
                $check_sql = "SELECT q.id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ? AND c.instructor_id = ?";
                if (!db_select($check_sql, 'ii', [$quiz_id, $_SESSION['user_id']])) {
                    $_SESSION['quiz_management_error'] = "You do not have permission to edit this quiz.";
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit;
                }
            }

            $updateQuery = "UPDATE quizzes SET 
                course_id = ?, title = ?, description = ?, duration = ?, status = ?, 
                attempts_allowed = ?, pass_mark = ?, randomize_questions = ?, show_feedback_immediately = ?
                WHERE id = ?";
            
            db_execute($updateQuery, "isssisiiii", [
                $course_id, $title, $description, $duration, $status, 
                $attempts_allowed, $pass_mark, $randomize_questions, $show_feedback_immediately,
                $quiz_id
            ]);

            $_SESSION['quiz_management_success'] = "Quiz '{$title}' updated successfully!";
        }
        
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // --- Handle Delete ---
    if ($_POST['action'] === 'delete_quiz') {
        $quiz_id_to_delete = intval($_POST['quiz_id_to_delete'] ?? 0);
        if ($quiz_id_to_delete > 0) {
            // Security check for instructors
            if ($userRole === 'instructor') {
                $check_sql = "SELECT q.id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ? AND c.instructor_id = ?";
                if (!db_select($check_sql, 'ii', [$quiz_id_to_delete, $_SESSION['user_id']])) {
                    $_SESSION['quiz_management_error'] = "You do not have permission to delete this quiz.";
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                    exit;
                }
            }

            // Add transaction to delete quiz and related questions/attempts later
            // For now, just delete the quiz
            db_execute("DELETE FROM quizzes WHERE id = ?", "i", [$quiz_id_to_delete]);
            
            $_SESSION['quiz_management_success'] = "Quiz has been deleted successfully.";
        } else {
            $_SESSION['quiz_management_error'] = "Invalid Quiz ID for deletion.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Fallback redirect if no action is matched
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../../dashboard'));
exit;