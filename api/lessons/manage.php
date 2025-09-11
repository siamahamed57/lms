<?php
// Path to the database connection file.
require_once __DIR__ . '/../../includes/db.php';

// Start session and check for admin/instructor role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = $_SESSION['user_role'] ?? 'student';
if (!isset($_SESSION['user_id']) || !in_array($userRole, ['admin', 'instructor'])) {
    header('HTTP/1.0 403 Forbidden');
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Denied!</h2><p class='text-gray-600'>Only Administrators or Instructors can access this page.</p></div>";
    exit;
}

// --- Success/Error Message Handling ---
$success_message = $_SESSION['lesson_management_success'] ?? '';
unset($_SESSION['lesson_management_success']);
$error_message = $_SESSION['lesson_management_error'] ?? '';
unset($_SESSION['lesson_management_error']);

// --- Fetch data for dropdowns in edit forms ---
$coursesQuery = "SELECT id, title FROM courses";
$coursesParams = [];
$coursesTypes = '';
if ($userRole === 'instructor') {
    $coursesQuery .= " WHERE instructor_id = ?";
    $coursesParams[] = $_SESSION['user_id'];
    $coursesTypes .= 'i';
}
$coursesQuery .= " ORDER BY title ASC";
$courses = db_select($coursesQuery, $coursesTypes, $coursesParams);

$quizzes = db_select("SELECT id, title FROM quizzes ORDER BY title ASC");
$assignments = db_select("SELECT id, title FROM assignments ORDER BY title ASC");

// --- Pagination Setup ---
$lessons_per_page = 100;
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($current_page - 1) * $lessons_per_page;

// --- Search and Filter Logic ---
$search_query = trim($_GET['search'] ?? '');

// --- Build query ---
$sql_params = [];
$sql_types = "";
$where_clauses = [];

$sql_select = "SELECT l.*, c.title AS course_title
               FROM lessons l
               JOIN courses c ON l.course_id = c.id";

if ($userRole === 'instructor') {
    $where_clauses[] = "c.instructor_id = ?";
    $sql_params[] = $_SESSION['user_id'];
    $sql_types .= "i";
}

if (!empty($search_query)) {
    $where_clauses[] = "(l.title LIKE ? OR c.title LIKE ?)";
    $sql_params[] = "%$search_query%";
    $sql_params[] = "%$search_query%";
    $sql_types .= "ss";
}

$sql_where = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- Total lessons for pagination ---
$sql_count = "SELECT COUNT(l.id) AS total FROM lessons l JOIN courses c ON l.course_id = c.id" . $sql_where;
$total_result = db_select($sql_count, $sql_types, $sql_params);
$total_lessons = $total_result ? $total_result[0]['total'] : 0;
$total_pages = ceil($total_lessons / $lessons_per_page);

// --- Lessons query ---
$sql_lessons = $sql_select . $sql_where . " ORDER BY c.title, l.order_no ASC LIMIT ? OFFSET ?";
$sql_params_with_limit = array_merge($sql_params, [$lessons_per_page, $offset]);
$sql_types_with_limit = $sql_types . "ii";

$lessons = db_select($sql_lessons, $sql_types_with_limit, $sql_params_with_limit);

// Function to get status badge class
if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        switch ($status) {
            case 'published': return 'status-published';
            case 'draft': return 'status-draft';
            default: return 'status-draft';
        }
    }
}
?>
<!-- This is a content fragment, not a full page. -->
<link rel="stylesheet" href="assets/css/course-manage.css"> <!-- Re-using course management styles -->
<style> 
    /* ---- [ Import Modern Font & Icons ] ---- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
/* Font Awesome for icons */
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
    --color-draft: #6c757d; /* Gray */
}


.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

/* ---- [ Header & Titles ] ---- */
.header { margin-bottom: 2rem; }
.title { font-size: 2.25rem; font-weight: 600; }

/* ---- [ Search Container ] ---- */
.search-container { margin-bottom: 2rem; }
.search-box {
    display: flex;
    max-width: 500px;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    overflow: hidden;
    backdrop-filter: blur(10px);
}
.search-input {
    flex-grow: 1;
    border: none;
    background: transparent;
    padding: 0.8rem 1.2rem;
    color: var(--text-primary);
    font-size: 1rem;
}
.search-input::placeholder { color: var(--text-secondary); }
.search-input:focus { outline: none; }
.search-btn {
    border: none;
    background: var(--primary-color);
    color: #fff;
    padding: 0 1.5rem;
    cursor: pointer;
    font-size: 1.1rem;
    transition: background-color 0.3s ease;
}
.search-btn:hover { background-color: var(--primary-hover-color); }

/* ---- [ Table Container Styling ] ---- */
.table-container {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    padding: 0;
    overflow: hidden;
}
.table { width: 100%; border-collapse: collapse; }
.table-header { background: rgba(255, 255, 255, 0.05); }
.table-header th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.table-body { display: block; max-height: 70vh; overflow-y: auto; }
.table-row { border-bottom: 1px solid var(--glass-border); }
.table-row:last-child { border-bottom: none; }
.table-row:hover { background-color: rgba(185, 21, 255, 0.08); }
.table-cell { padding: 1rem 1.5rem; vertical-align: middle; }
.lesson-title { font-weight: 600; font-size: 1.05rem; }
.course-name { font-size: 0.9rem; color: var(--text-secondary); }
.empty-state { text-align: center; color: var(--text-secondary); padding: 3rem; }

/* ---- [ Status Badges ] ---- */
.status-badge {
    padding: 0.3em 0.8em;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
}
.status-published { background-color: rgba(40, 167, 69, 0.3); color: #7ee29a; }
.status-draft { background-color: rgba(108, 117, 125, 0.3); color: #ced4da; }

/* ---- [ Actions Buttons ] ---- */
.actions { display: flex; gap: 0.5rem; }
.action-btn {
    background: transparent;
    border: 1px solid var(--glass-border);
    color: var(--text-secondary);
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex; align-items: center; gap: 0.4rem;
}
.action-btn:hover { background: var(--input-bg); color: var(--text-primary); }
.action-edit:hover { border-color: var(--primary-color); color: var(--primary-color); }
.action-delete:hover { border-color: var(--color-danger); color: var(--color-danger); }

/* ---- [ Inline Edit Form ] ---- */
.edit-form-cell { padding: 2rem !important; background: rgba(0, 0, 0, 0.25); }
.edit-form-grid {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    grid-template-columns: repeat(4, 1fr);
}
.edit-form-group { display: flex; flex-direction: column; }
.edit-form-group[style*="grid-column"] { grid-column: span 4; }
.edit-form-group label {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
}
.edit-form-group input, .edit-form-group select, .edit-form-group textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--input-bg);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    color: var(--text-primary);
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
}
.edit-form-group input:focus, .edit-form-group select:focus, .edit-form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(185, 21, 255, 0.2);
}
.edit-form-group textarea { resize: vertical; }
.edit-form-group input[type="checkbox"] {
    width: 18px; height: 18px;
    accent-color: var(--primary-color);
}
.edit-form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; }
.btn-update {
    padding: 0.75rem 1.5rem; border: none; border-radius: 8px; background-color: var(--primary-color);
    color: #fff; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
}
.btn-update:hover { background-color: var(--primary-hover-color); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(185, 21, 255, 0.2); }
.btn-cancel {
    padding: 0.75rem 1.5rem; border-radius: 8px; background: transparent;
    border: 1px solid var(--glass-border); color: var(--text-secondary);
    font-weight: 600; cursor: pointer; transition: all 0.3s ease;
}
.btn-cancel:hover { background: var(--input-bg); color: var(--text-primary); }

/* ---- [ Alerts ] ---- */
.alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid transparent; display: flex; align-items: center; }
.alert-success { background-color: rgba(40, 167, 69, 0.15); border-color: rgba(40, 167, 69, 0.4); color: #a3ffb8; }
.alert-error { background-color: rgba(220, 53, 69, 0.15); border-color: rgba(220, 53, 69, 0.4); color: #ffacb3; }
.alert .fas { margin-right: 0.75rem; font-size: 1.2rem; }

/* ---- [ Responsive Design ] ---- */
@media (max-width: 992px) {
    .edit-form-grid { grid-template-columns: 1fr 1fr; }
    .edit-form-group[style*="grid-column"] { grid-column: span 2; }
}
@media (max-width: 768px) {
    .container { padding: 1rem; }
    .table-header { display: none; }
    .table-body, .table-row, .table-cell { display: block; }
    .table-row { border: 1px solid var(--glass-border); border-radius: 8px; margin-bottom: 1rem; padding: 1rem; }
    .table-cell { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--glass-border); }
    .table-cell:last-child { border-bottom: none; }
    .table-cell::before {
        content: attr(data-label);
        font-weight: 500;
        color: var(--text-secondary);
        padding-right: 1rem;
    }
    .actions { justify-content: flex-end; }
    .edit-form-grid { grid-template-columns: 1fr; }
    .edit-form-group[style*="grid-column"] { grid-column: span 1; }
}
</style>
    <div class="container">
        <div class="header">
            <h1 class="title">Lesson Management</h1>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert"><i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error" role="alert"><i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <div class="search-container">
            <form action="" method="GET">
                <div class="search-box">
                    <input type="hidden" name="_page" value="dashboard"><input type="hidden" name="page" value="manage-lessons">
                    <input type="text" name="search" class="search-input" placeholder="Search by lesson or course title..." value="<?= htmlspecialchars($search_query) ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead class="table-header">
                    <tr>
                        <th>Lesson</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
            <div class="table-body">
                <table class="table">
                    <tbody id="lessonTableBody">
                        <?php if (empty($lessons)): ?>
                            <tr class="table-row"><td colspan="4" class="empty-state">No lessons found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr class="table-row" data-lesson-id="<?= $lesson['id'] ?>">
                                    <td class="table-cell">
                                        <div class="lesson-title"><?= htmlspecialchars($lesson['title']) ?></div>
                                        <div class="course-name"><?= htmlspecialchars($lesson['duration'] ?? 'N/A') ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="course-name"><?= htmlspecialchars($lesson['course_title']) ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <span class="status-badge <?= getStatusBadge($lesson['status']) ?>">
                                            <?= htmlspecialchars(ucfirst($lesson['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <div class="actions">
                                            <button type="button" class="action-btn action-edit" onclick="toggleEditForm(<?= $lesson['id'] ?>)">
                                                <i class="fas fa-edit"></i> <span class="edit-btn-text">Edit</span>
                                            </button>
                                            <button class="action-btn action-delete" onclick="confirmDelete(<?= $lesson['id'] ?>, '<?= htmlspecialchars(addslashes($lesson['title']), ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="edit-form-row" id="edit-form-<?= $lesson['id'] ?>" style="display: none;">
                                    <td colspan="4" class="edit-form-cell">
                                        <form method="POST" action="api/lessons/manage-logic.php">
                                            <input type="hidden" name="action" value="update_lesson_inline">
                                            <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                            
                                            <div class="edit-form-grid">
                                                <div class="edit-form-group">
                                                    <label>Title</label>
                                                    <input type="text" name="title" value="<?= htmlspecialchars($lesson['title']) ?>" required>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label>Course</label>
                                                    <select name="course_id" required>
                                                        <?php foreach ($courses as $course): ?>
                                                            <option value="<?= $course['id'] ?>" <?= $lesson['course_id'] == $course['id'] ? 'selected' : '' ?>><?= htmlspecialchars($course['title']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label>Order</label>
                                                    <input type="number" name="order_no" value="<?= htmlspecialchars($lesson['order_no']) ?>" min="0">
                                                </div>
                                                <div class="edit-form-group">
                                                    <label>Duration</label>
                                                    <input type="text" name="duration" value="<?= htmlspecialchars($lesson['duration']) ?>" placeholder="e.g., 15 min">
                                                </div>
                                            </div>
                                            <div class="edit-form-group">
                                                <label>Description</label>
                                                <textarea name="description" rows="3"><?= htmlspecialchars($lesson['description'] ?? '') ?></textarea>
                                            </div>

                                            <div class="edit-form-grid">
                                                <div class="edit-form-group">
                                                    <label>Status</label>
                                                    <select name="status" required>
                                                        <option value="draft" <?= ($lesson['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Draft</option>
                                                        <option value="published" <?= ($lesson['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                                                    </select>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label>Drip Schedule (Release Date)</label>
                                                    <input type="datetime-local" name="release_date" value="<?= !empty($lesson['release_date']) ? date('Y-m-d\TH:i', strtotime($lesson['release_date'])) : '' ?>">
                                                </div>
                                            </div>

                                            <div class="edit-form-grid">
                                                <div class="edit-form-group">
                                                    <label>Attach Quiz</label>
                                                    <select name="quiz_id">
                                                        <option value="0">-- No Quiz --</option>
                                                        <?php foreach ($quizzes as $quiz): ?>
                                                            <option value="<?= $quiz['id'] ?>" <?= $lesson['quiz_id'] == $quiz['id'] ? 'selected' : '' ?>><?= htmlspecialchars($quiz['title']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label>Attach Assignment</label>
                                                    <select name="assignment_id">
                                                        <option value="0">-- No Assignment --</option>
                                                        <?php foreach ($assignments as $assignment): ?>
                                                            <option value="<?= $assignment['id'] ?>" <?= $lesson['assignment_id'] == $assignment['id'] ? 'selected' : '' ?>><?= htmlspecialchars($assignment['title']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="edit-form-grid" style="grid-template-columns: 1fr 1fr;">
                                                <div class="edit-form-group">
                                                    <label style="display:inline-flex; align-items:center; gap: 8px;">
                                                        <input type="checkbox" name="is_preview" value="1" <?= !empty($lesson['is_preview']) ? 'checked' : '' ?> style="width: auto;">
                                                        <span>Free Preview Lesson</span>
                                                    </label>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label style="display:inline-flex; align-items:center; gap: 8px;">
                                                        <input type="checkbox" name="is_locked" value="1" <?= !empty($lesson['is_locked']) ? 'checked' : '' ?> style="width: auto;">
                                                        <span>Lock Lesson</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="edit-form-actions">
                                                <button type="button" class="btn-cancel" onclick="toggleEditForm(<?= $lesson['id'] ?>)">Cancel</button>
                                                <button type="submit" class="btn-update">Update Lesson</button>
                                            </div>
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

    <!-- Hidden form for deletion -->
    <form id="delete-form" method="POST" action="api/lessons/manage-logic.php" style="display: none;">
        <input type="hidden" name="action" value="delete_lesson">
        <input type="hidden" name="lesson_id_to_delete" id="lesson_id_to_delete">
    </form>

    <script>
        function confirmDelete(lessonId, lessonTitle) {
            if (confirm(`Are you sure you want to delete "${lessonTitle}"? This action cannot be undone.`)) {
                document.getElementById('lesson_id_to_delete').value = lessonId;
                document.getElementById('delete-form').submit();
            }
        }

        let activeEditFormId = null;

        function toggleEditForm(lessonId) {
            const formRow = document.getElementById(`edit-form-${lessonId}`);
            const tableRow = document.querySelector(`.table-row[data-lesson-id='${lessonId}']`);
            const editButtonText = tableRow.querySelector('.edit-btn-text');

            if (activeEditFormId && activeEditFormId !== lessonId) {
                document.getElementById(`edit-form-${activeEditFormId}`).style.display = 'none';
                document.querySelector(`.table-row[data-lesson-id='${activeEditFormId}'] .edit-btn-text`).textContent = 'Edit';
            }

            const isVisible = formRow.style.display === 'table-row';
            formRow.style.display = isVisible ? 'none' : 'table-row';
            editButtonText.textContent = isVisible ? 'Edit' : 'Cancel';
            activeEditFormId = isVisible ? null : lessonId;
        }
    </script>