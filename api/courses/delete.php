<?php
// Path to the database connection file.
require_once __DIR__ . '/../../includes/db.php';
// Check if user is logged in and is an instructor or admin.
// Assuming role 1 is admin, role 3 is instructor.
$is_authorized = isset($_SESSION['user_id']) && ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 3);

if (!$is_authorized) {
    echo "You do not have permission to delete this course.";
    exit;
}

// Get the course ID from the URL.
$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    echo "Course ID is missing.";
    exit;
}

// Simple confirmation. In a real application, you'd use a form with a POST request.
if (isset($_GET['confirm']) && $_GET['confirm'] === 'true') {
    try {
        // Prepare and execute the delete statement.
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);

        // Redirect back to the courses list page with a success message.
        header('Location: list.php?message=Course deleted successfully&type=success');
        exit;
    } catch (PDOException $e) {
        // Redirect back with an error message.
        header('Location: list.php?message=Error deleting course&type=error');
        exit;
    }
} else {
    // If not confirmed, provide a link to confirm.
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Confirm Deletion</title></head>";
    echo "<body>";
    echo "<div style='text-align: center; margin-top: 50px;'>";
    echo "<h2>Are you sure you want to delete this course?</h2>";
    echo "<a href='delete.php?id=" . htmlspecialchars($course_id) . "&confirm=true'>Yes, delete it.</a> | ";
    echo "<a href='list.php'>No, go back.</a>";
    echo "</div>";
    echo "</body></html>";
}
?>
