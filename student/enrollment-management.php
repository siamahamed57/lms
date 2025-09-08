<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// --- Authorization Check ---
$userRole = $_SESSION['user_role'] ?? 'student';
if ($userRole !== 'admin') {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Denied!</h2><p>Only Administrators can access this page.</p></div>";
    exit;
}

// --- Message Handling ---
$success_message = $_SESSION['enrollment_success'] ?? '';
unset($_SESSION['enrollment_success']);
$error_message = $_SESSION['enrollment_error'] ?? '';
unset($_SESSION['enrollment_error']);

// --- Fetch Data for Forms & Table ---
$students = db_select("SELECT id, name, email FROM users WHERE role = 'student' ORDER BY name ASC");
$courses = db_select("SELECT id, title FROM courses ORDER BY title ASC");

// --- Search & Filter Logic ---
$search_query = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');

$sql_params = [];
$sql_types = "";
$where_clauses = ["s.role = 'student'"];

$sql_select = "SELECT e.id, e.status, e.enrolled_at, s.name as student_name, s.email as student_email, c.title as course_title
               FROM enrollments e
               JOIN users s ON e.student_id = s.id
               JOIN courses c ON e.course_id = c.id";

if (!empty($search_query)) {
    $where_clauses[] = "(s.name LIKE ? OR s.email LIKE ? OR c.title LIKE ?)";
    $search_like = "%$search_query%";
    array_push($sql_params, $search_like, $search_like, $search_like);
    $sql_types .= "sss";
}

if (!empty($status_filter)) {
    $where_clauses[] = "e.status = ?";
    $sql_params[] = $status_filter;
    $sql_types .= "s";
}

$sql_where = " WHERE " . implode(" AND ", $where_clauses);
$enrollments = db_select($sql_select . $sql_where . " ORDER BY e.enrolled_at DESC", $sql_types, $sql_params);

?>
<link rel="stylesheet" href="assets/css/course-manage.css"> <!-- Re-using styles -->
<style>
    .enrollment-form-container {
        background: var(--surface-light);
        padding: 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        border: 1px solid var(--border);
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 200px;
        gap: 1.5rem;
        align-items: end;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .form-group select, .form-group input {
        width: 100%;
        padding: 0.75rem;
        border-radius: 8px;
        border: 1px solid var(--border);
    }
    .btn-enroll {
        width: 100%;
        padding: 0.75rem;
        border-radius: 8px;
        background: var(--gradient);
        color: white;
        font-weight: 600;
        border: none;
        cursor: pointer;
    }
    .filter-container {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    /* Custom styles for enrollment status */
    .status-active { background: rgba(16, 185, 129, 0.1); color: var(--success); }
    .status-blocked { background: rgba(239, 68, 68, 0.1); color: var(--error); }
</style>

<div class="container">
    <div class="header">
        <h1 class="title">Student Enrollment Management</h1>
    </div>

    <?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

    <!-- Enroll Form -->
    <div class="enrollment-form-container">
        <h3 class="form-section-title" style="margin-top:0; margin-bottom: 1.5rem;">Enroll Student in a Course</h3>
        <form method="POST" action="student/enrollment-logic.php">
            <input type="hidden" name="action" value="enroll_student">
            <div class="form-grid">
                <div class="form-group">
                    <label for="student_id">Select Student</label>
                    <select name="student_id" id="student_id" required>
                        <option value="">-- Choose a student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name'] . ' (' . $student['email'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="course_id">Select Course</label>
                    <select name="course_id" id="course_id" required>
                        <option value="">-- Choose a course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-enroll">Enroll Student</button>
            </div>
        </form>
    </div>

    <!-- Search and Filter -->
    <div class="search-container">
        <form action="" method="GET">
            <input type="hidden" name="page" value="enrollment-management">
            <div class="filter-container">
                <input type="text" name="search" class="search-input" style="padding: 0.75rem;" placeholder="Search by student, email, or course..." value="<?= htmlspecialchars($search_query) ?>">
                <select name="status_filter" class="form-group" style="width: 200px; padding: 0.75rem;">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="blocked" <?= $status_filter === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                </select>
                <button type="submit" class="btn-enroll" style="width: 150px;">Filter</button>
            </div>
        </form>
    </div>

    <!-- Enrollment Table -->
    <div class="table-container">
        <table class="table">
            <thead class="table-header">
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Enrolled On</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
        <div class="table-body">
            <table class="table">
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr><td colspan="5" class="empty-state">No enrollments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td class="table-cell">
                                    <div class="lesson-title"><?= htmlspecialchars($enrollment['student_name']) ?></div>
                                    <div class="course-name"><?= htmlspecialchars($enrollment['student_email']) ?></div>
                                </td>
                                <td class="table-cell course-name"><?= htmlspecialchars($enrollment['course_title']) ?></td>
                                <td class="table-cell course-name"><?= date("M j, Y", strtotime($enrollment['enrolled_at'])) ?></td>
                                <td class="table-cell">
                                    <span class="status-badge <?= $enrollment['status'] === 'active' ? 'status-published' : 'status-archived' ?>">
                                        <?= htmlspecialchars(ucfirst($enrollment['status'])) ?>
                                    </span>
                                </td>
                                <td class="table-cell">
                                    <form method="POST" action="student/enrollment-logic.php" style="display:inline-flex; gap: 10px;">
                                        <input type="hidden" name="enrollment_id" value="<?= $enrollment['id'] ?>">
                                        <?php if ($enrollment['status'] === 'active'): ?>
                                            <input type="hidden" name="action" value="update_enrollment_status">
                                            <input type="hidden" name="new_status" value="blocked">
                                            <button type="submit" class="action-btn action-delete" title="Block Student"><i class="fas fa-user-slash"></i> Block</button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="update_enrollment_status">
                                            <input type="hidden" name="new_status" value="active">
                                            <button type="submit" class="action-btn action-edit" style="background: rgba(16, 185, 129, 0.1); color: var(--success);" title="Unblock Student"><i class="fas fa-user-check"></i> Unblock</button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="POST" action="student/enrollment-logic.php" onsubmit="return confirm('Are you sure you want to permanently delete this enrollment?');" style="display:inline-flex;">
                                        <input type="hidden" name="action" value="delete_enrollment">
                                        <input type="hidden" name="enrollment_id" value="<?= $enrollment['id'] ?>">
                                        <button type="submit" class="action-btn action-delete" title="Delete Enrollment"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>