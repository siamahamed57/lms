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
    .lesson-title { font-weight: 700; color: var(--text); margin-bottom: 4px; }
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