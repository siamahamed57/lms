<?php
// Path to the database connection file.
require_once __DIR__ . '/../../includes/db.php';

// Start session and check for admin role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Show an error message and stop execution if not an admin
    header('HTTP/1.0 403 Forbidden');
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Denied!</h2><p class='text-gray-600'>Only Administrators can access this page.</p></div>";
    exit;
}

// --- Success Message Handling ---
$success_message = '';
if (isset($_SESSION['course_management_success'])) {
    $success_message = $_SESSION['course_management_success'];
    unset($_SESSION['course_management_success']);
}

// --- Error Message Handling ---
$error_message = '';
if (isset($_SESSION['course_management_error'])) {
    $error_message = $_SESSION['course_management_error'];
    unset($_SESSION['course_management_error']);
}

// --- Fetch data for dropdowns in edit forms ---
$categories = db_select("SELECT id, name FROM categories ORDER BY name ASC");
$universities = db_select("SELECT id, name FROM universities ORDER BY name ASC");
$instructors = db_select("SELECT id, name FROM users WHERE role = 'instructor' ORDER BY name ASC");

// --- Pagination Setup ---
$courses_per_page = 100;
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $courses_per_page;

// --- Search and Filter Logic ---
$search_query = trim($_GET['search'] ?? '');

// --- Build query ---
$where_clauses = []; // The $sql_params array is not needed for the mysqli implementation.

$sql_select = "SELECT c.*, u.name AS instructor_name, cat.name AS category_name, uni.name AS university_name,
                      GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS tags
               FROM courses c
               JOIN users u ON c.instructor_id = u.id
               LEFT JOIN categories cat ON c.category_id = cat.id
               LEFT JOIN universities uni ON c.university_id = uni.id
               LEFT JOIN course_tag ct ON c.id = ct.course_id
               LEFT JOIN tags t ON ct.tag_id = t.id";

if (!empty($search_query)) {
    $where_clauses[] = "(c.title LIKE ? OR u.name LIKE ? OR cat.name LIKE ?)";
}

$sql_where = count($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- Total courses for pagination ---
$count_query = "SELECT COUNT(DISTINCT c.id) as total
                FROM courses c
                JOIN users u ON c.instructor_id = u.id
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN universities uni ON c.university_id = uni.id
                " . $sql_where;
$stmt_count = $conn->prepare($count_query);
if (!empty($search_query)) {
    $search_like = "%{$search_query}%";
    $stmt_count->bind_param("sss", $search_like, $search_like, $search_like);
}
$stmt_count->execute();
$total_courses = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();
$total_pages = ceil($total_courses / $courses_per_page);

// --- Courses query ---
$sql_courses = $sql_select . $sql_where . " GROUP BY c.id ORDER BY c.created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql_courses);
if (!empty($search_query)) {
    $search_like = "%{$search_query}%";
    $stmt->bind_param("sssii", $search_like, $search_like, $search_like, $courses_per_page, $offset);
} else {
    $stmt->bind_param("ii", $courses_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Function to get status badge class
if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        switch ($status) {
            case 'published':
                return 'status-published';
            case 'pending':
                return 'status-pending';
            case 'draft':
                return 'status-draft';
            case 'archived':
                return 'status-archived';
            default:
                return 'status-draft';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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
    --color-pending: #ffc107;
    --color-draft: #6c757d;
}


.container {
    max-width: 1400px;
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
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}
.search-input {
    flex-grow: 1; border: none; background: transparent;
    padding: 0.8rem 1.2rem; color: var(--text-primary); font-size: 1rem;
}
.search-input::placeholder { color: var(--text-secondary); }
.search-input:focus { outline: none; }
.search-btn {
    border: none; background: var(--primary-color); color: #fff;
    padding: 0 1.5rem; cursor: pointer; font-size: 1.1rem;
    transition: background-color 0.3s ease;
}
.search-btn:hover { background-color: var(--primary-hover-color); }

/* ---- [ Table Container Styling ] ---- */
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
.table-row {
    border-bottom: 1px solid var(--glass-border);
    transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
    animation: fadeIn 0.5s ease-out forwards;
    opacity: 0;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.table-row:last-child { border-bottom: none; }
.table-row:hover { background-color: rgba(185, 21, 255, 0.08); transform: scale(1.01); }
.table-cell { padding: 1rem 1.5rem; vertical-align: middle; }
.course-title { font-weight: 600; font-size: 1.05rem; }
.instructor-name { font-size: 0.9rem; color: var(--text-secondary); }
.price { font-weight: 500; }
.empty-state { text-align: center; color: var(--text-secondary); padding: 4rem; }
.empty-icon { font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem; }

/* ---- [ Status Badges ] ---- */
.status-badge {
    padding: 0.3em 0.8em; border-radius: 1rem;
    font-size: 0.8rem; font-weight: 600;
}
.status-published { background-color: rgba(40, 167, 69, 0.3); color: #7ee29a; }
.status-draft { background-color: rgba(108, 117, 125, 0.3); color: #ced4da; }
.status-pending { background-color: rgba(255, 193, 7, 0.3); color: #ffe69c; }
.status-archived { background-color: rgba(220, 53, 69, 0.3); color: #ffacb3; }

/* ---- [ Actions Buttons ] ---- */
.actions { display: flex; gap: 0.5rem; }
.action-btn {
    background: transparent; border: 1px solid var(--glass-border);
    color: var(--text-secondary); padding: 0.4rem 0.8rem; border-radius: 6px;
    cursor: pointer; transition: all 0.3s ease;
    display: flex; align-items: center; gap: 0.4rem;
}
.action-btn:hover { background: var(--input-bg); color: var(--text-primary); }
.action-edit:hover, .action-edit.active { border-color: var(--primary-color); color: var(--primary-color); }
.action-delete:hover { border-color: var(--color-danger); color: var(--color-danger); }

/* ---- [ Inline Edit Form ] ---- */
.edit-form-cell { padding: 2rem !important; background: rgba(0, 0, 0, 0.25); }
.edit-form-section-title {
    font-size: 1.1rem; font-weight: 500; margin-top: 1.5rem; margin-bottom: 1rem;
    padding-bottom: 0.5rem; border-bottom: 1px solid var(--glass-border);
}
.edit-form-section-title:first-child { margin-top: 0; }
.edit-form-grid {
    display: grid; gap: 1.5rem; margin-bottom: 1.5rem;
    grid-template-columns: repeat(4, 1fr);
}
.edit-form-group { display: flex; flex-direction: column; }
.edit-form-group label { margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary); }
.edit-form-group input, .edit-form-group select, .edit-form-group textarea {
    width: 100%; padding: 0.75rem 1rem; background: var(--input-bg);
    border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-primary);
    font-family: 'Poppins', sans-serif; transition: all 0.3s ease;
}
.edit-form-group input:focus, .edit-form-group select:focus, .edit-form-group textarea:focus {
    outline: none; border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(185, 21, 255, 0.2);
}
.edit-form-group textarea { resize: vertical; }
.edit-form-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary-color); }
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

/* ---- [ Pagination ] ---- */
.pagination {
    display: flex; justify-content: center; align-items: center;
    padding: 1.5rem; gap: 0.5rem;
}
.pagination a, .pagination span {
    text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px;
    color: var(--text-secondary); border: 1px solid var(--glass-border);
    transition: all 0.3s ease;
}
.pagination a:hover { background-color: var(--input-bg); color: var(--text-primary); }
.pagination a.active {
    background-color: var(--primary-color); color: #fff;
    border-color: var(--primary-color); font-weight: 600;
}
.pagination span.disabled { color: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.1); }

/* ---- [ Alerts ] ---- */
.alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid transparent; display: flex; align-items: center; }
.alert-success { background-color: rgba(40, 167, 69, 0.15); border-color: rgba(40, 167, 69, 0.4); color: #a3ffb8; }
.alert-error { background-color: rgba(220, 53, 69, 0.15); border-color: rgba(220, 53, 69, 0.4); color: #ffacb3; }
.alert .fas { margin-right: 0.75rem; font-size: 1.2rem; }

/* ---- [ Responsive Design ] ---- */
@media (max-width: 992px) {
    .edit-form-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
    .container { padding: 1rem; }
    .table-header { display: none; }
    .table-body, .table-row, .table-cell { display: block; }
    .table-row { border: 1px solid var(--glass-border); border-radius: 8px; margin-bottom: 1rem; padding: 1rem; transform: none !important; }
    .table-row:hover { transform: none !important; box-shadow: 0 8px 25px rgba(185, 21, 255, 0.1) !important; }
    .table-cell { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--glass-border); }
    .table-cell:last-child { border-bottom: none; }
    .table-cell::before {
        content: attr(data-label); font-weight: 500;
        color: var(--text-secondary); padding-right: 1rem;
    }
    .actions { justify-content: flex-end; }
    .edit-form-grid { grid-template-columns: 1fr; }
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Course Management</h1>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= $error_message ?>
            </div>
        <?php endif; ?>
        
        <div class="search-container">
            <form action="" method="GET">
                <div class="search-box">
                    <input type="hidden" name="_page" value="dashboard"><input type="hidden" name="page" value="manage">
                    <input type="text" name="search" class="search-input" 
                           placeholder="Search by course title or instructor..."
                           value="<?= htmlspecialchars($search_query) ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead class="table-header">
                    <tr>
                        <th>Course</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
            <div class="table-body">
                <table class="table">
                    <tbody id="courseTableBody">
                        <?php if (empty($courses)): ?>
                            <tr class="table-row">
                                <td colspan="4" class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">No courses found</div>
                                    <div><?= !empty($search_query) ? 'Try adjusting your search terms' : 'No courses available in the system' ?></div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $index => $course): ?>
                                <tr class="table-row" data-course-id="<?= $course['id'] ?>" style="animation-delay: <?= $index * 0.1 ?>s">
                                    <td class="table-cell">
                                        <div class="course-title"><?= htmlspecialchars($course['title']) ?></div>
                                        <div class="instructor-name"><?= htmlspecialchars($course['instructor_name']) ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <div class="price">$<?= htmlspecialchars(number_format($course['price'], 2)) ?></div>
                                    </td>
                                    <td class="table-cell">
                                        <span class="status-badge <?= getStatusBadge($course['status']) ?>">
                                            <?= htmlspecialchars(ucfirst($course['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        <div class="actions">
                                            <button class="action-btn action-edit" onclick="toggleEditForm(<?= $course['id'] ?>)">
                                                <i class="fas fa-edit"></i> <span class="edit-btn-text">Edit</span>
                                            </button>
                                            <button class="action-btn action-delete" onclick="confirmDelete(<?= $course['id'] ?>, '<?= htmlspecialchars(addslashes($course['title']), ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="edit-form-row" id="edit-form-<?= $course['id'] ?>" style="display: none;">
                                    <td colspan="4" class="edit-form-cell">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="update_course_inline">
                                            <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                            
                                            <h4 class="edit-form-section-title">General Information</h4>
                                            <div class="edit-form-grid">
                                                <div class="edit-form-group">
                                                    <label for="title-<?= $course['id'] ?>">Title</label>
                                                    <input type="text" id="title-<?= $course['id'] ?>" name="title" value="<?= htmlspecialchars($course['title']) ?>" required>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label for="subtitle-<?= $course['id'] ?>">Subtitle</label>
                                                    <input type="text" id="subtitle-<?= $course['id'] ?>" name="subtitle" value="<?= htmlspecialchars($course['subtitle'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="edit-form-group">
                                                <label for="description-<?= $course['id'] ?>">Description</label>
                                                <textarea id="description-<?= $course['id'] ?>" name="description" rows="3"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
                                            </div>
                                            <div class="edit-form-grid" style="grid-template-columns: 1fr 1fr;">
                                                <div class="edit-form-group">
                                                    <label for="course_level-<?= $course['id'] ?>">Level</label>
                                                    <select id="course_level-<?= $course['id'] ?>" name="course_level">
                                                        <option value="beginner" <?= ($course['course_level'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                                                        <option value="intermediate" <?= ($course['course_level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                                                        <option value="advanced" <?= ($course['course_level'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                                                    </select>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label for="course_language-<?= $course['id'] ?>">Language</label>
                                                    <select id="course_language-<?= $course['id'] ?>" name="course_language">
                                                        <option value="English" <?= ($course['course_language'] ?? '') === 'English' ? 'selected' : '' ?>>English</option>
                                                        <option value="Spanish" <?= ($course['course_language'] ?? '') === 'Spanish' ? 'selected' : '' ?>>Spanish</option>
                                                        <option value="Bengali" <?= ($course['course_language'] ?? '') === 'Bengali' ? 'selected' : '' ?>>Bengali</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <h4 class="edit-form-section-title">Categorization & Pricing</h4>
                                            <div class="edit-form-grid">
                                                <div class="edit-form-group">
                                                    <label for="instructor-<?= $course['id'] ?>">Instructor</label>
                                                    <select id="instructor-<?= $course['id'] ?>" name="instructor_id" required>
                                                        <?php foreach ($instructors as $instructor): ?>
                                                            <option value="<?= $instructor['id'] ?>" <?= $course['instructor_id'] == $instructor['id'] ? 'selected' : '' ?>><?= htmlspecialchars($instructor['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label for="category-<?= $course['id'] ?>">Category</label>
                                                    <select id="category-<?= $course['id'] ?>" name="category_id">
                                                        <option value="0">None</option>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?= $category['id'] ?>" <?= $course['category_id'] == $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label for="university-<?= $course['id'] ?>">University</label>
                                                    <select id="university-<?= $course['id'] ?>" name="university_id">
                                                        <option value="0">None</option>
                                                        <?php foreach ($universities as $university): ?>
                                                            <option value="<?= $university['id'] ?>" <?= $course['university_id'] == $university['id'] ? 'selected' : '' ?>><?= htmlspecialchars($university['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label for="price-<?= $course['id'] ?>">Price</label>
                                                    <input type="number" id="price-<?= $course['id'] ?>" name="price" value="<?= htmlspecialchars($course['price']) ?>" step="0.01" min="0" required>
                                                </div>
                                            </div>

                                            <h4 class="edit-form-section-title">Advanced Details</h4>
                                            <div class="edit-form-grid">
                                                <div class="edit-form-group">
                                                    <label for="seo_title-<?= $course['id'] ?>">SEO Title</label>
                                                    <input type="text" id="seo_title-<?= $course['id'] ?>" name="seo_title" value="<?= htmlspecialchars($course['seo_title'] ?? '') ?>">
                                                </div>
                                                <div class="edit-form-group">
                                                    <label for="tags-<?= $course['id'] ?>">Tags (comma-separated)</label>
                                                    <input type="text" id="tags-<?= $course['id'] ?>" name="tags" value="<?= htmlspecialchars($course['tags'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="edit-form-group">
                                                <label for="meta_description-<?= $course['id'] ?>">Meta Description</label>
                                                <textarea id="meta_description-<?= $course['id'] ?>" name="meta_description" rows="2"><?= htmlspecialchars($course['meta_description'] ?? '') ?></textarea>
                                            </div>
                                            <div class="edit-form-group">
                                                <label for="prerequisites-<?= $course['id'] ?>">Prerequisites</label>
                                                <textarea id="prerequisites-<?= $course['id'] ?>" name="prerequisites" rows="2"><?= htmlspecialchars($course['prerequisites'] ?? '') ?></textarea>
                                            </div>

                                            <h4 class="edit-form-section-title">Publishing & Enrollment</h4>
                                            <div class="edit-form-grid" style="grid-template-columns: repeat(3, 1fr);">
                                                <div class="edit-form-group">
                                                    <label for="status-<?= $course['id'] ?>">Status</label>
                                                    <select id="status-<?= $course['id'] ?>" name="status" required>
                                                        <option value="draft" <?= ($course['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Draft</option>
                                                        <option value="pending" <?= ($course['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="published" <?= ($course['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                                                        <option value="archived" <?= ($course['status'] ?? '') == 'archived' ? 'selected' : '' ?>>Archived</option>
                                                    </select>
                                                </div>
                                                <div class="edit-form-group">
                                                    <label for="enrollment_limit-<?= $course['id'] ?>">Enrollment Limit</label>
                                                    <input type="number" id="enrollment_limit-<?= $course['id'] ?>" name="enrollment_limit" value="<?= htmlspecialchars($course['enrollment_limit'] ?? '0') ?>" min="0">
                                                </div>
                                                <div class="edit-form-group" style="align-self: center;">
                                                    <label for="certificate_of_completion-<?= $course['id'] ?>" style="display:inline-flex; align-items:center; gap: 8px; height: 100%;">
                                                        <input type="checkbox" id="certificate_of_completion-<?= $course['id'] ?>" name="certificate_of_completion" value="1" <?= !empty($course['certificate_of_completion']) ? 'checked' : '' ?> style="width: auto;">
                                                        <span>Enable Certificate</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="edit-form-actions">
                                                <button type="button" class="btn-cancel" onclick="toggleEditForm(<?= $course['id'] ?>)">Cancel</button>
                                                <button type="submit" class="btn-update">Update Course</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="<?= urlencode($search_query) ?>&p=<?= $current_page - 1 ?>">Previous</a>
                <?php else: ?>
                    <span class="disabled">Previous</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="<?= urlencode($search_query) ?>&p=<?= $i ?>" class="<?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="<?= urlencode($search_query) ?>&p=<?= $current_page + 1 ?>">Next</a>
                <?php else: ?>
                    <span class="disabled">Next</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hidden form for deletion -->
    <form id="delete-form" method="POST" action="" style="display: none;">
        <input type="hidden" name="action" value="delete_course">
        <input type="hidden" name="course_id_to_delete" id="course_id_to_delete">
    </form>

    <script>
        function confirmDelete(courseId, courseTitle) {
            if (confirm(`Are you sure you want to delete "${courseTitle}"?\n\nThis action will permanently delete the course and all its associated data (lessons, enrollments, etc.) and cannot be undone.`)) {
                document.getElementById('course_id_to_delete').value = courseId;
                document.getElementById('delete-form').submit();
            }
        }

        let activeEditFormId = null;

        function toggleEditForm(courseId) {
            const formRow = document.getElementById(`edit-form-${courseId}`);
            const tableRow = document.querySelector(`.table-row[data-course-id='${courseId}']`);
            const editButton = tableRow.querySelector('.action-edit');
            const editButtonText = editButton.querySelector('.edit-btn-text');

            // If another form is open, close it first
            if (activeEditFormId && activeEditFormId !== courseId) {
                const lastFormRow = document.getElementById(`edit-form-${activeEditFormId}`);
                const lastTableRow = document.querySelector(`.table-row[data-course-id='${activeEditFormId}']`);
                const lastEditButton = lastTableRow.querySelector('.action-edit');
                const lastEditButtonText = lastEditButton.querySelector('.edit-btn-text');
                
                if (lastFormRow) lastFormRow.style.display = 'none';
                if (lastEditButton) {
                    lastEditButton.classList.remove('active');
                    if (lastEditButtonText) lastEditButtonText.textContent = 'Edit';
                }
            }

            // Toggle the current form
            const isVisible = formRow.style.display === 'table-row';
            formRow.style.display = isVisible ? 'none' : 'table-row';
            
            if (editButton) {
                editButton.classList.toggle('active', !isVisible);
                if (editButtonText) {
                    editButtonText.textContent = isVisible ? 'Edit' : 'Cancel';
                }
            }

            activeEditFormId = isVisible ? null : courseId;
        }
        // Add click animations to action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Add enhanced hover effects for table rows
        document.querySelectorAll('.table-row').forEach(row => {
            if (!row.querySelector('.empty-state')) {
                row.addEventListener('mouseenter', function() {
                    this.style.boxShadow = '0 8px 25px rgba(185, 21, 255, 0.1)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.boxShadow = '';
                });
            }
        });

        // Smooth scroll animation for search form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const searchBtn = document.querySelector('.search-btn');
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        });

        // Add loading state management
        window.addEventListener('beforeunload', function() {
            const searchBtn = document.querySelector('.search-btn');
            if (searchBtn) {
                searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
        });

        // Reset search button on page load
        window.addEventListener('load', function() {
            const searchBtn = document.querySelector('.search-btn');
            if (searchBtn) {
                searchBtn.innerHTML = '<i class="fas fa-search"></i>';
            }
        });

        // Add keyboard navigation for accessibility
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                document.querySelector('.search-input').focus();
            }
        });
    </script>
</body>
</html>