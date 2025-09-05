<?php
switch ($section) {
    case 'create-course':
        if ($userRole === 'admin' || $userRole === 'instructor') {
            include __DIR__ . '/../../api/courses/create-course.php';
        } else {
            echo "<h2>🚫 Access Denied</h2>";
        }
        break;

    case 'my-courses':
        include __DIR__ . '/../../api/enrollments/my_courses.php';
        break;

    case 'overview':
    default:
        echo "<h2>📊 Dashboard Overview</h2>";
        echo "<p>Welcome to UNIES LMS Dashboard.</p>";
        break;
}
?>
