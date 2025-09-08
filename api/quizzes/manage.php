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
<style>
    /* Re-using course management styles from your project */
    :root {
        --primary: #b915ff; --primary-light: #c84eff; --primary-dark: #9700e6;
        --secondary: #1a1b23; --surface: #ffffff; --surface-light: #f8fafc;
        --text: #1e293b; --text-light: #64748b; --border: #e2e8f0;
        --success: #10b981; --warning: #f59e0b; --error: #ef4444;
    }
    .container { max-width: 1280px; width: 100%; height: auto; margin: 0 auto; background: rgba(0, 0, 0, 0); padding: 0; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
    .title { font-size: 28px; font-weight: 800; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .alert { padding: 16px; margin-bottom: 24px; border-radius: 16px; font-weight: 600; border: 1px solid; }
    .alert-success { background: rgba(16, 185, 129, 0.1); color: var(--success); border-color: var(--success); }
    .alert-error { background: rgba(239, 68, 68, 0.1); color: var(--error); border-color: var(--error); }
    .search-container { margin-bottom: 24px; }
    .search-box { position: relative; width: 100%; }
    .search-input { width: 100%; padding: 16px 60px 16px 24px; border: 2px solid var(--border); border-radius: 16px; font-size: 16px; background: var(--surface); transition: all 0.3s; outline: none; }
    .search-btn { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); border: none; padding: 12px 16px; border-radius: 12px; color: white; cursor: pointer; }
    .table-container { background: var(--surface); border-radius: 20px; box-shadow: var(--shadow); overflow: hidden; height: 600px; position: relative; }
    .table { width: 100%; border-collapse: collapse; }
    .table-header { background: linear-gradient(135deg, var(--surface-light) 0%, rgba(185, 21, 255, 0.05) 100%); position: sticky; top: 0; z-index: 10; }
    .table-header th { padding: 20px 16px; text-align: left; font-weight: 700; color: var(--text); font-size: 14px; text-transform: uppercase; border-bottom: 2px solid var(--primary); }
    .table-body { height: calc(600px - 60px); overflow-y: auto; display: block; }
    .table-body table { width: 100%; }
    .table-row { transition: all 0.3s ease; cursor: pointer; display: table-row; }
    .table-cell { padding: 20px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; display: table-cell; }
    .quiz-title { font-weight: 700; color: var(--text); margin-bottom: 4px; }
    .course-name { color: var(--text-light); font-size: 14px; }
    .status-badge { padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; display: inline-block; }
    .status-published { background: rgba(16, 185, 129, 0.1); color: var(--success); }
    .status-draft { background: rgba(107, 114, 128, 0.1); color: var(--text-light); }
    .actions { display: flex; gap: 12px; }
    .action-btn { padding: 8px 12px; border-radius: 10px; text-decoration: none; font-size: 12px; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer; }
    .action-edit { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .action-delete { background: rgba(239, 68, 68, 0.1); color: var(--error); }
    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); }
    .edit-form-cell { padding: 24px; background-color: #f7f8fc; border-bottom: 2px solid var(--primary); }
    .edit-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .edit-form-group label { display: block; font-weight: 600; font-size: 13px; color: var(--text-light); margin-bottom: 8px; }
    .edit-form-group input, .edit-form-group select, .edit-form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; background: white; }
    .edit-form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
    .btn-update, .btn-cancel { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-update { background: var(--primary); color: white; }
    .btn-cancel { background: #e2e8f0; color: #475569; }
</style>
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