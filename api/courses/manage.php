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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #b915ff;
            --primary-light: #c84eff;
            --primary-dark: #9700e6;
            --secondary: #1a1b23;
            --surface: #ffffff;
            --surface-light: #f8fafc;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        }

      
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert {
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 16px;
            font-weight: 600;
            animation: fadeIn 0.5s ease-out;
            border: 1px solid;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1); color: var(--success); border-color: var(--success);
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1); color: var(--error); border-color: var(--error);
        }

        .container {
            max-width: 1280px;
            width: 1280px;
            height: 820px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            position:fixed;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            animation: slideInDown 0.6s ease-out;
        }

        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .title {
            font-size: 28px;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .search-container {
            margin-bottom: 24px;
            animation: slideInUp 0.6s ease-out 0.2s both;
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 6px 6px 6px 4px;
            border: 2px solid var(--border);
            border-radius: 16px;
            font-size: 16px;
            background: var(--surface);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            name: "search";
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(185, 21, 255, 0.1);
            transform: translateY(-2px);
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--gradient);
            border: none;
            padding: 12px 16px;
            border-radius: 12px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            type: "submit";
        }

        .search-btn:hover {
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 8px 20px rgba(185, 21, 255, 0.3);
        }

        .table-container {
            background: var(--surface);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            height: 600px;
            animation: slideInUp 0.6s ease-out 0.4s both;
            position: relative;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            
        }

        .table-header {
            background: linear-gradient(135deg, var(--surface-light) 0%, rgba(185, 21, 255, 0.05) 100%);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-header th {
            padding: 20px 16px;
            text-align: left;
            font-weight: 700;
            color: var(--text);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--primary);
        }

        .table-body {
            height: calc(600px - 60px);
            overflow-y: auto;
            display: block;
        }

        .table-body table {
            width: 100%;
        }

        .table-row {
            transition: all 0.3s ease;
            cursor: pointer;
            display: table-row;
        }

        .table-row:hover {
            background: linear-gradient(135deg, rgba(185, 21, 255, 0.02) 0%, rgba(185, 21, 255, 0.05) 100%);
            transform: translateX(4px);
        }

        .table-row:nth-child(even) {
            background: rgba(248, 250, 252, 0.5);
        }

        .table-cell {
            padding: 20px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            animation: fadeInRow 0.6s ease-out;
            display: table-cell;
        }

        @keyframes fadeInRow {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .course-title {
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
        }

        .instructor-name {
            color: var(--text-light);
            font-size: 14px;
        }

        .price {
            font-weight: 700;
            font-size: 18px;
            color: var(--primary);
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .status-published { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .status-draft { background: rgba(107, 114, 128, 0.1); color: var(--text-light); }
        .status-archived { background: rgba(239, 68, 68, 0.1); color: var(--error); }

        .actions {
            display: flex;
            gap: 12px;
        }

        .action-btn {
            padding: 8px 12px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .action-edit {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .action-edit:hover {
            background: #3b82f6;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .action-delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }

        .action-delete:hover {
            background: var(--error);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-icon {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 16px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .hidden-input {
            display: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body { padding: 10px; }
            
            .container {
                max-width: 100%;
                height: auto;
                min-height: 620px;
                padding: 20px;
                border-radius: 16px;
            }
            
            .title { font-size: 24px; }
            
            .table-container { height: auto; min-height: 400px; }
            
            .table-body { height: auto; min-height: 340px; }
            
            .table-header th { padding: 16px 12px; font-size: 12px; }
            
            .table-cell { padding: 16px 12px; }
            
            .actions { flex-direction: column; gap: 8px; }
            
            .action-btn { justify-content: center; }
        }

        @media (max-width: 480px) {
            .search-input { padding: 14px 50px 14px 20px; }
            
            .table-header th, .table-cell { padding: 12px 8px; }
            
            .course-title { font-size: 14px; }
            .instructor-name { font-size: 12px; }
            .price { font-size: 16px; }
        }

        /* Custom Scrollbar */
        .table-body::-webkit-scrollbar {
            width: 6px;
        }

        .table-body::-webkit-scrollbar-track {
            background: var(--surface-light);
            border-radius: 3px;
        }

        .table-body::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .table-body::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Inline Edit Form Styles */
        .edit-form-cell {
            padding: 24px;
            background-color: #f7f8fc;
            border-bottom: 2px solid var(--primary);
        }
        .edit-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .edit-form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        .edit-form-group input,
        .edit-form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }
        .edit-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }
        .btn-update, .btn-cancel {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-update {
            background: var(--primary);
            color: white;
        }
        .btn-update:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .btn-cancel {
            background: #e2e8f0;
            color: #475569;
        }
        .edit-form-section-title {
            font-size: 1rem; font-weight: 700; color: var(--primary); 
            margin-top: 20px; margin-bottom: 16px; padding-bottom: 8px; 
            border-bottom: 2px solid var(--border);
        }
        .edit-form-section-title:first-child { margin-top: 0; }
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