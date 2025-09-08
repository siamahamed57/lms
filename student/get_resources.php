<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// --- Authorization & Validation ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}
$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    echo json_encode(['error' => 'Invalid course ID.']);
    exit;
}

// --- Security Check: Ensure student is enrolled ---
$enrollment_check_raw = db_select("SELECT id, expires_at FROM enrollments WHERE student_id = ? AND course_id = ?", "ii", [$student_id, $course_id]);
if (empty($enrollment_check_raw)) {
    echo json_encode(['error' => 'You are not enrolled in this course.']);
    exit;
}
$enrollment_check = $enrollment_check_raw[0];
if ($enrollment_check['expires_at'] !== null && strtotime($enrollment_check['expires_at']) < time()) {
    echo json_encode(['error' => 'Your access to this course has expired.']);
    exit;
}

// --- Fetch all resources for the given course ---
$resources_sql = "SELECT r.file_name, r.file_path FROM lesson_resources r JOIN lessons l ON r.lesson_id = l.id WHERE l.course_id = ?";
$resources = db_select($resources_sql, "i", [$course_id]);

echo json_encode($resources);
?>