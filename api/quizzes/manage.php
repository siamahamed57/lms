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
$success_message = $_SESSION['quiz_management_success'] ?? '';
unset($_SESSION['quiz_management_success']);
$error_message = $_SESSION['quiz_management_error'] ?? '';
unset($_SESSION['quiz_management_error']);

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

// --- Pagination Setup ---
$quizzes_per_page = 100;
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($current_page - 1) * $quizzes_per_page;

// --- Search and Filter Logic ---
$search_query = trim($_GET['search'] ?? '');

// --- Build query ---
$sql_params = [];
$sql_types = "";
$where_clauses = [];

$sql_select = "SELECT q.*, c.title AS course_title
               FROM quizzes q
               JOIN courses c ON q.course_id = c.id";

if ($userRole === 'instructor') {
    $where_clauses[] = "c.instructor_id = ?";
    $sql_params[] = $_SESSION['user_id'];
    $sql_types .= "i";
}

if (!empty($search_query)) {
    $where_clauses[] = "(q.title LIKE ? OR c.title LIKE ?)";
    $sql_params[] = "%$search_query%";
    $sql_params[] = "%$search_query%";
    $sql_types .= "ss";
}

$sql_where = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- Total quizzes for pagination ---
$sql_count = "SELECT COUNT(q.id) AS total FROM quizzes q JOIN courses c ON q.course_id = c.id" . $sql_where;
$total_result = db_select($sql_count, $sql_types, $sql_params);
$total_quizzes = $total_result ? $total_result[0]['total'] : 0;
$total_pages = ceil($total_quizzes / $quizzes_per_page);

// --- Quizzes query ---
$sql_quizzes = $sql_select . $sql_where . " ORDER BY c.title, q.title ASC LIMIT ? OFFSET ?";
$sql_params_with_limit = array_merge($sql_params, [$quizzes_per_page, $offset]);
$sql_types_with_limit = $sql_types . "ii";

$quizzes = db_select($sql_quizzes, $sql_types_with_limit, $sql_params_with_limit);

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
<!-- Re-using lesson management styles -->
<link rel="stylesheet" href="assets/css/course-manage.css">
<style>/* ---- [ Import Modern Font & Icons ] ---- */
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
.quiz-title { font-weight: 600; font-size: 1.05rem; }
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
    grid-template-columns: 1fr 1fr;
}
.edit-form-group { display: flex; flex-direction: column; }
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
    .edit-form-grid { grid-template-columns: 1fr; }
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
} </style>
<div class="container">
    <div class="header">
        <h1 class="title">Quiz Management</h1>
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
                <input type="hidden" name="_page" value="dashboard"><input type="hidden" name="page" value="manage-quizzes">
                <input type="text" name="search" class="search-input" placeholder="Search by quiz or course title..." value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table class="table">
            <thead class="table-header">
                <tr>
                    <th>Quiz</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
        <div class="table-body">
            <table class="table">
                <tbody id="quizTableBody">
                    <?php if (empty($quizzes)): ?>
                        <tr class="table-row"><td colspan="4" class="empty-state">No quizzes found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr class="table-row" data-quiz-id="<?= $quiz['id'] ?>">
                                <td class="table-cell">
                                    <div class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></div>
                                    <div class="course-name"><?= ($quiz['duration'] > 0) ? htmlspecialchars($quiz['duration']) . ' mins' : 'No time limit' ?></div>
                                </td>
                                <td class="table-cell">
                                    <div class="course-name"><?= htmlspecialchars($quiz['course_title']) ?></div>
                                </td>
                                <td class="table-cell">
                                    <span class="status-badge <?= getStatusBadge($quiz['status']) ?>">
                                        <?= htmlspecialchars(ucfirst($quiz['status'])) ?>
                                    </span>
                                </td>
                                <td class="table-cell">
                                    <div class="actions">
                                        <button class="action-btn action-edit" onclick="toggleEditForm(<?= $quiz['id'] ?>)">
                                            <i class="fas fa-edit"></i> <span class="edit-btn-text">Edit</span>
                                        </button>
                                        <button class="action-btn action-delete" onclick="confirmDelete(<?= $quiz['id'] ?>, '<?= htmlspecialchars(addslashes($quiz['title']), ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="edit-form-row" id="edit-form-<?= $quiz['id'] ?>" style="display: none;">
                                <td colspan="4" class="edit-form-cell">
                                    <form method="POST" action="api/quizzes/manage-logic.php">
                                        <input type="hidden" name="action" value="update_quiz_inline">
                                        <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                        
                                        <div class="edit-form-grid">
                                            <div class="edit-form-group">
                                                <label>Title</label>
                                                <input type="text" name="title" value="<?= htmlspecialchars($quiz['title']) ?>" required>
                                            </div>
                                            <div class="edit-form-group">
                                                <label>Course</label>
                                                <select name="course_id" required>
                                                    <?php foreach ($courses as $course): ?>
                                                        <option value="<?= $course['id'] ?>" <?= $quiz['course_id'] == $course['id'] ? 'selected' : '' ?>><?= htmlspecialchars($course['title']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="edit-form-group">
                                            <label>Description</label>
                                            <textarea name="description" rows="3"><?= htmlspecialchars($quiz['description'] ?? '') ?></textarea>
                                        </div>

                                        <div class="edit-form-grid" style="grid-template-columns: repeat(4, 1fr);">
                                            <div class="edit-form-group">
                                                <label>Duration (mins)</label>
                                                <input type="number" name="duration" value="<?= htmlspecialchars($quiz['duration'] ?? '0') ?>" min="0">
                                            </div>
                                            <div class="edit-form-group">
                                                <label>Attempts</label>
                                                <input type="number" name="attempts_allowed" value="<?= htmlspecialchars($quiz['attempts_allowed'] ?? '1') ?>" min="0">
                                            </div>
                                            <div class="edit-form-group">
                                                <label>Pass Mark (%)</label>
                                                <input type="number" name="pass_mark" value="<?= htmlspecialchars($quiz['pass_mark'] ?? '50') ?>" min="0" max="100">
                                            </div>
                                            <div class="edit-form-group">
                                                <label>Status</label>
                                                <select name="status" required>
                                                    <option value="draft" <?= ($quiz['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Draft</option>
                                                    <option value="published" <?= ($quiz['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="edit-form-grid" style="grid-template-columns: 1fr 1fr;">
                                            <div class="edit-form-group">
                                                <label style="display:inline-flex; align-items:center; gap: 8px;">
                                                    <input type="checkbox" name="randomize_questions" value="1" <?= !empty($quiz['randomize_questions']) ? 'checked' : '' ?> style="width: auto;">
                                                    <span>Randomize Questions</span>
                                                </label>
                                            </div>
                                            <div class="edit-form-group">
                                                <label style="display:inline-flex; align-items:center; gap: 8px;">
                                                    <input type="checkbox" name="show_feedback_immediately" value="1" <?= !empty($quiz['show_feedback_immediately']) ? 'checked' : '' ?> style="width: auto;">
                                                    <span>Show Instant Feedback</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="edit-form-actions">
                                            <button type="button" class="btn-cancel" onclick="toggleEditForm(<?= $quiz['id'] ?>)">Cancel</button>
                                            <button type="submit" class="btn-update">Update Quiz</button>
                                        </div>
                                    </form>
                                    <div style="border-top: 1px solid var(--border); margin-top: 20px; padding-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                                        <h5 style="font-weight: 600; color: var(--text);">Question Management</h5>
                                        <a href="?page=create-quiz&quiz_id=<?= $quiz['id'] ?>" class="btn-update" style="text-decoration: none; background: var(--success);">
                                            <i class="fas fa-plus-circle" style="margin-right: 8px;"></i> Add / Edit Questions
                                        </a>
                                    </div>
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
<form id="delete-form" method="POST" action="api/quizzes/manage-logic.php" style="display: none;">
    <input type="hidden" name="action" value="delete_quiz">
    <input type="hidden" name="quiz_id_to_delete" id="quiz_id_to_delete">
</form>

<script>
    function confirmDelete(quizId, quizTitle) {
        if (confirm(`Are you sure you want to delete the quiz "${quizTitle}"? This action cannot be undone.`)) {
            document.getElementById('quiz_id_to_delete').value = quizId;
            document.getElementById('delete-form').submit();
        }
    }

    let activeEditFormId = null;

    function toggleEditForm(quizId) {
        const formRow = document.getElementById(`edit-form-${quizId}`);
        const tableRow = document.querySelector(`.table-row[data-quiz-id='${quizId}']`);
        const editButtonText = tableRow.querySelector('.edit-btn-text');

        if (activeEditFormId && activeEditFormId !== quizId) {
            document.getElementById(`edit-form-${activeEditFormId}`).style.display = 'none';
            document.querySelector(`.table-row[data-quiz-id='${activeEditFormId}'] .edit-btn-text`).textContent = 'Edit';
        }

        const isVisible = formRow.style.display === 'table-row';
        formRow.style.display = isVisible ? 'none' : 'table-row';
        editButtonText.textContent = isVisible ? 'Edit' : 'Cancel';
        activeEditFormId = isVisible ? null : quizId;
    }
</script>