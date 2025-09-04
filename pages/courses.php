<?php
require_once __DIR__ . '/../includes/db.php';

// --- Pagination setup ---
$courses_per_page = 6;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $courses_per_page;

// --- Filters ---
$search_query   = trim($_GET['search'] ?? '');
$university_id  = isset($_GET['university']) && is_numeric($_GET['university']) ? (int)$_GET['university'] : null;
$category_id    = isset($_GET['department']) && is_numeric($_GET['department']) ? (int)$_GET['department'] : null;

// --- Build query ---
$sql_params = [];
$sql_types = "";
$where_clauses = [];

$sql_select = "SELECT c.*, u.name AS instructor_name, cat.name AS category_name, uni.name AS university_name
               FROM courses c
               JOIN users u ON c.instructor_id = u.id
               LEFT JOIN categories cat ON c.category_id = cat.id
               LEFT JOIN universities uni ON c.university_id = uni.id";

if(!empty($search_query)){
    $where_clauses[] = "(c.title LIKE ? OR u.name LIKE ?)";
    $sql_params[] = "%$search_query%";
    $sql_params[] = "%$search_query%";
    $sql_types .= "ss";
}
if($university_id){
    $where_clauses[] = "c.university_id = ?";
    $sql_params[] = $university_id;
    $sql_types .= "i";
}
if($category_id){
    $where_clauses[] = "c.category_id = ?";
    $sql_params[] = $category_id;
    $sql_types .= "i";
}

$sql_where = count($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- Courses query ---
$sql_courses = $sql_select . $sql_where . " ORDER BY c.id DESC LIMIT ? OFFSET ?";
$sql_params_with_limit = $sql_params;
$sql_params_with_limit[] = $courses_per_page;
$sql_params_with_limit[] = $offset;
$sql_types_with_limit = $sql_types . "ii";

$courses = db_select($sql_courses, $sql_types_with_limit, $sql_params_with_limit);

// --- Total courses for pagination ---
$sql_count = "SELECT COUNT(*) AS total 
              FROM courses c
              JOIN users u ON c.instructor_id = u.id
              " . $sql_where;
$total_result = db_select($sql_count, $sql_types, $sql_params);
$total_courses = $total_result ? $total_result[0]['total'] : 0;
$total_pages = ceil($total_courses / $courses_per_page);

// --- Filter options ---
$categories = db_select("SELECT id, name FROM categories ORDER BY name ASC");
$universities = db_select("SELECT id, name FROM universities ORDER BY name ASC");

// --- AJAX check ---
$is_ajax = isset($_GET['is_ajax']) && $_GET['is_ajax'] === 'true';

// --- Render courses function ---
function render_course_list($courses, $current_page, $total_pages){
    ?>
    <?php if(empty($courses)): ?>
        <div class="no-courses-found">No courses found.</div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($courses as $course): ?>
                <div class="card">
                    <img src="<?= htmlspecialchars($course['thumbnail']) ?>" 
                         alt="<?= htmlspecialchars($course['title']) ?>" 
                         class="w-full h-40 object-cover"
                         onerror="this.onerror=null;this.src='https://placehold.co/400x250/e5e7eb/4b5563?text=Thumbnail';">
                    <div class="p-6">
                        <h3 class="font-bold text-xl mb-2 truncate"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="text-sm mb-4 truncate"><?= htmlspecialchars($course['subtitle']) ?></p>
                        <div class="flex items-center text-sm mb-2">
                            <i class="fas fa-user-circle mr-2"></i>
                            <span><?= htmlspecialchars($course['instructor_name']) ?></span>
                        </div>
                        <div class="flex items-center text-sm mb-2">
                            <i class="fas fa-university mr-2"></i>
                            <span><?= htmlspecialchars($course['university_name']) ?></span>
                        </div>
                        <div class="flex justify-between items-center mt-4">
                            <span class="text-lg font-bold price">$<?= number_format($course['price'], 2) ?></span>
                            <a href="course_details?id=<?= htmlspecialchars($course['id']) ?>" class="details-link">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($current_page > 1): ?>
                    <a href="#" data-page="<?= $current_page - 1 ?>">Previous</a>
                <?php endif; ?>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="#" data-page="<?= $i ?>" class="<?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if($current_page < $total_pages): ?>
                    <a href="#" data-page="<?= $current_page + 1 ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif;
}

// --- AJAX output ---
if($is_ajax){
    render_course_list($courses, $current_page, $total_pages);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Courses | UNIES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css"> <!-- Main stylesheet -->
    <link rel="stylesheet" href="../assets/css/courses.css"> <!-- Page-specific modern styles -->
</head>
<body class="courses-page">

<!-- Animated background elements -->
<div class="dot-grid-bg"></div>
<div class="animated-overlay"></div>

<div class="main-container">
    <header class="page-header">
        <h1 id="course_title_h1">Discover Your Next Course</h1>
        <p>Browse courses by university, department, or search for a specific topic.</p>
    </header>

    <div class="filter-bar">
        <form id="filter-form">
            <div class="form-group search-group">
                <i class="fas fa-search"></i>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search course or instructor...">
            </div>
            <div class="form-group">
                <i class="fas fa-university"></i>
                <select id="university" name="university">
                    <option value="">All Universities</option>
                    <?php foreach($universities as $uni): ?>
                        <option value="<?= $uni['id'] ?>" <?= $university_id == $uni['id'] ? 'selected' : '' ?>><?= htmlspecialchars($uni['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <i class="fas fa-graduation-cap"></i>
                <select id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <div id="course-list-container">
        <?php render_course_list($courses, $current_page, $total_pages); ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('filter-form');
    const container = document.getElementById('course-list-container');
    let debounce;

    const fetchCourses = (page = 1) => {
        const data = new FormData(form);
        data.set('page', page);

        // Create params for the URL bar (without is_ajax)
        const urlParams = new URLSearchParams(data);
        const url = `${window.location.pathname}?${urlParams.toString()}`;
        window.history.pushState({path: url}, '', url);

        // Add is_ajax for the fetch request
        data.set('is_ajax', 'true');
        const fetchParams = new URLSearchParams(data);
        
        // Optional: Show a loading indicator
        container.style.opacity = '0.5';

        fetch(`pages/courses.php?${fetchParams.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
        })
        .catch(error => {
            console.error('Error fetching courses:', error);
            container.innerHTML = '<div class="no-courses-found">Error loading courses. Please try again.</div>';
            container.style.opacity = '1';
        });
    };

    // Prevent form submission, as we handle it with JS
    form.addEventListener('submit', e => e.preventDefault());

    // Add event listeners to filters (reset to page 1 on change)
    document.getElementById('search').addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(() => fetchCourses(1), 500); });
    document.getElementById('university').addEventListener('change', () => fetchCourses(1));
    document.getElementById('department').addEventListener('change', () => fetchCourses(1));

    container.addEventListener('click', e => {
        const a = e.target.closest('.pagination a');
        if(a) { e.preventDefault(); const page = a.getAttribute('data-page'); fetchCourses(page); }
    });
});
</script>

</body>
</html>