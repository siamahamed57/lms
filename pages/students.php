<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Denied!</h2><p>This page is for instructors only.</p></div>";
    exit;
}
$instructor_id = $_SESSION['user_id'];

// --- Data Fetching ---
$students_sql = "
    SELECT
        s.id as student_id,
        s.name as student_name,
        s.email as student_email,
        s.avatar as student_avatar,
        GROUP_CONCAT(c.title SEPARATOR '<br>') as enrolled_courses,
        MAX(e.enrolled_at) as last_enrollment_date
    FROM
        users s
    JOIN
        enrollments e ON s.id = e.student_id
    JOIN
        courses c ON e.course_id = c.id
    WHERE
        c.instructor_id = ?
    GROUP BY
        s.id, s.name, s.email, s.avatar
    ORDER BY
        last_enrollment_date DESC
";
$students = db_select($students_sql, "s", [$instructor_id]);
?>

<style>
    /* Reusing styles for consistency */
    .students-container { padding: 2rem; }
    .students-header { margin-bottom: 2rem; }
    .students-header h1 { font-size: 2.25rem; font-weight: 600; }
    .students-header p { color: var(--text-secondary); }

    .table-container { background: var(--glass-bg); backdrop-filter: blur(15px); border-radius: 16px; border: 1px solid var(--glass-border); overflow: hidden; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { padding: 1rem 1.5rem; text-align: left; vertical-align: middle; border-bottom: 1px solid var(--glass-border); }
    .table thead th { color: var(--text-secondary); font-weight: 500; text-transform: uppercase; font-size: 0.8rem; }
    .table tbody tr:last-child td { border-bottom: none; }
    .table tbody tr:hover { background-color: rgba(185, 21, 255, 0.08); }

    .student-info { display: flex; align-items: center; gap: 1rem; }
    .student-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: var(--primary-color); }
    .student-name { font-weight: 600; }
    .student-email { font-size: 0.9rem; color: var(--text-secondary); }
    .enrolled-courses { font-size: 0.9rem; line-height: 1.5; }
    .empty-state { text-align: center; color: var(--text-secondary); padding: 3rem; }
</style>

<div class="students-container">
    <div class="students-header">
        <h1>My Students</h1>
        <p>A list of all students enrolled in your courses.</p>
    </div>

    <div class="table-container">
        <table class="table">
            <thead><tr><th>Student</th><th>Enrolled Courses</th><th>Last Enrolled</th></tr></thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="3" class="empty-state"><i class="fas fa-users-slash" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i><p>No students have enrolled in your courses yet.</p></td></tr>
                <?php else: foreach ($students as $student): ?>
                    <tr>
                        <td><div class="student-info"><img src="<?= htmlspecialchars($student['student_avatar'] ?? 'assets/images/default_avatar.png') ?>" alt="Avatar" class="student-avatar" onerror="this.onerror=null;this.src='assets/images/default_avatar.png';"><div><div class="student-name"><?= htmlspecialchars($student['student_name']) ?></div><div class="student-email"><?= htmlspecialchars($student['student_email']) ?></div></div></div></td>
                        <td class="enrolled-courses"><?= $student['enrolled_courses'] ?></td>
                        <td><?= date('M d, Y', strtotime($student['last_enrollment_date'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>