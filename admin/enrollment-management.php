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
/* ---- [ Import Modern Font & Icons ] ---- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

/* ---- [ CSS Variables for Easy Theming ] ---- */
:root {
    --primary-color: #b915ff;
    --primary-hover-color: #8b00cc;
    --background-start: #231134;
    --background-end: #0f172a;
    --glass-bg: rgba(255, 255, 255, 0.07);
    --glass-border: rgba(255, 255, 255, 0.2);
    --text-primary: #f0f0f0;
    --text-secondary: #a0a0a0;
    --input-bg: rgba(0, 0, 0, 0.3);

    /* Status & Alert Colors */
    --color-success: #28a745;
    --color-danger: #dc3545;
}


.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

/* ---- [ Header & Titles ] ---- */
.header { margin-bottom: 2rem; }
.title { font-size: 2.25rem; font-weight: 600; }

/* ---- [ Enrollment Form & Filter Container ] ---- */
.enrollment-form-container, .search-container {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
    padding: 2rem;
    margin-bottom: 2rem;
}
.form-section-title {
    font-size: 1.5rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
}
.form-grid, .filter-container {
    display: grid;
    gap: 1.5rem;
    align-items: flex-end;
}
.form-grid { grid-template-columns: 1fr 1fr 200px; }
.filter-container { grid-template-columns: 1fr 200px 150px; }

/* ---- [ Form Inputs ] ---- */
.form-group, .search-input, .form-group select {
    width: 100%; padding: 0.75rem 1rem; background: var(--input-bg);
    border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-primary);
    font-family: 'Poppins', sans-serif; transition: all 0.3s ease;
}
.form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary); }
.form-group select { appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23a0a0a0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 16px 12px;
}
.search-input::placeholder { color: var(--text-secondary); }
.search-input:focus, .form-group select:focus {
    outline: none; border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(185, 21, 255, 0.2);
}

/* ---- [ Buttons ] ---- */
.btn-enroll {
    padding: 0.75rem 1.5rem; border: none; border-radius: 8px; background-color: var(--primary-color);
    color: #fff; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
    white-space: nowrap;
}
.btn-enroll:hover { background-color: var(--primary-hover-color); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(185, 21, 255, 0.2); }

/* ---- [ Table Styling ] ---- */
.table-container {
    background: var(--glass-bg); backdrop-filter: blur(15px);
    border-radius: 16px; border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); padding: 0;
    overflow: hidden;
}
.table { width: 100%; border-collapse: collapse; }
.table-header { background: rgba(255, 255, 255, 0.05); }
.table-header th {
    padding: 1rem 1.5rem; text-align: left; font-weight: 600;
    color: var(--text-secondary); font-size: 0.8rem;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.table-body { display: block; max-height: 70vh; overflow-y: auto; }
.table-row:hover, table tbody tr:hover { background-color: rgba(185, 21, 255, 0.08); }
.table-cell { padding: 1rem 1.5rem; vertical-align: middle; border-bottom: 1px solid var(--glass-border); }
table tbody tr:last-child .table-cell { border-bottom: none; }
.lesson-title { font-weight: 600; font-size: 1.05rem; }
.course-name { font-size: 0.9rem; color: var(--text-secondary); }
.empty-state { text-align: center; color: var(--text-secondary); padding: 3rem; }

/* ---- [ Status Badges & Actions ] ---- */
.status-badge { padding: 0.3em 0.8em; border-radius: 1rem; font-size: 0.8rem; font-weight: 600; }
.status-published { background-color: rgba(40, 167, 69, 0.3); color: #7ee29a; }
.status-archived { background-color: rgba(220, 53, 69, 0.3); color: #ffacb3; }

.action-btn {
    background: transparent; border: 1px solid var(--glass-border);
    color: var(--text-secondary); padding: 0.4rem 0.8rem; border-radius: 6px;
    cursor: pointer; transition: all 0.3s ease;
    display: inline-flex; align-items: center; gap: 0.4rem; font-family: 'Poppins', sans-serif;
}
.action-btn i { font-size: 0.9em; }
.action-btn:hover { background: var(--input-bg); color: var(--text-primary); }
.action-edit:hover { border-color: var(--color-success); color: var(--color-success); }
.action-delete:hover { border-color: var(--color-danger); color: var(--color-danger); }

/* ---- [ Alerts ] ---- */
.alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid transparent; }
.alert-success { background-color: rgba(40, 167, 69, 0.15); border-color: rgba(40, 167, 69, 0.4); color: #a3ffb8; }
.alert-error { background-color: rgba(220, 53, 69, 0.15); border-color: rgba(220, 53, 69, 0.4); color: #ffacb3; }

/* ---- [ Responsive Design ] ---- */
@media (max-width: 992px) {
    .form-grid, .filter-container { grid-template-columns: 1fr; }
    .btn-enroll { width: 100%; }
}
@media (max-width: 768px) {
    .container { padding: 1rem; }
    .table-header { display: none; }
    .table-body, .table-row, .table-cell { display: block; }
    .table tbody tr { border: 1px solid var(--glass-border); border-radius: 8px; margin-bottom: 1rem; padding: 1rem; display: block; }
    .table-cell { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--glass-border); }
    .table-cell:last-child { border-bottom: none; }
    .table-cell::before {
        content: attr(data-label); font-weight: 500;
        color: var(--text-secondary); padding-right: 1rem;
    }
}

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
        <form method="POST" action="dashboard?page=enrollment-management">
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
                                    <form method="POST" action="dashboard?page=enrollment-management" style="display:inline-flex; gap: 10px;">
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
                                    <form method="POST" action="dashboard?page=enrollment-management" onsubmit="return confirm('Are you sure you want to permanently delete this enrollment?');" style="display:inline-flex;">
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