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
                            <a href="detail.php?id=<?= htmlspecialchars($course['id']) ?>" class="details-link">View Details</a>
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
    <link rel="stylesheet" href="../assets/css/main.css">
    <style> 
        /* --- Main Content Container & Header --- */
.main-container {
    max-width: 1280px;
    margin: 0 auto;
    margin-top: 60px;
}

.page-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    padding-bottom: 0.5rem;
}

.page-header p {
    color: var(--text-color-muted);
    font-size: 1.125rem;
}

/* --- Horizontal Filter Bar (Glassmorphism) --- */
.filter-bar {
    background: var(--header-bg);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--header-border);
    border-radius: var(--border-radius-lg);
    padding: 1rem;
    margin-bottom: 2.5rem;
    position: sticky;
    top: 1rem;
    z-index: 10;
    box-shadow: 0 8px 32px 0 var(--shadow-color);
    border-radius: 20px;
}

#filter-form {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
}

.form-group {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1;
}

.form-group.search-group {
    flex: 2; /* Make search bar wider */
}

.form-group i {
    position: absolute;
    left: 1rem;
    color: var(--text-color-muted);
    pointer-events: none;
}

#filter-form input, #filter-form select {
    width: 100%;
    background-color: var(--input-bg);
    border: 1px solid var(--input-border);
    color: var(--text-color);
    border-radius: var(--border-radius-md);
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    transition: all var(--transition-speed) ease;
    appearance: none; /* for select */
    -webkit-appearance: none; /* for select */
}

#filter-form select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
}

#filter-form input:focus, #filter-form select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.3);
}

/* --- Course Card Styling --- */
#course-list-container {
    transition: opacity var(--transition-speed) ease;
}

.card {
    background-color: var(--card-bg);
    border: 1px solid var(--card-border);
    box-shadow: 0 4px 6px -1px var(--shadow-color);
    transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease, background-color var(--transition-speed) ease;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    border-radius: 20px;
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px var(--shadow-color);
    background-color: var(--card-hover-bg);
}

.card h3 {
    color: var(--text-color);
}

.card p, .card span {
    color: var(--text-color-muted);
}

.card .fas {
    color: var(--primary-color);
}

.card .price {
    color: var(--primary-color);
    font-weight: 700;
}

.card .details-link {
    font-weight: 500;
    color: var(--primary-color);
    text-decoration: none;
    transition: color var(--transition-speed) ease;
}

.card .details-link:hover {
    color: var(--primary-color-dark);
    text-decoration: underline;
}

/* --- "No courses found" Message --- */
.no-courses-found {
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--card-bg);
    border: 1px dashed var(--card-border);
    border-radius: var(--border-radius-lg);
    color: var(--text-color-muted);
    font-size: 1.25rem;
    border-radius: 20px;;
}

/* --- Pagination --- */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2.5rem;
    border-radius: 20px;
}

.pagination a {
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius-md);
    background-color: var(--card-bg);
    border: 1px solid var(--card-border);
    color: var(--text-color);
    transition: all var(--transition-speed) ease;
    border-radius: 20px;
}

.pagination a:hover {
    background-color: var(--card-hover-bg);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.pagination a.active {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: #fff;
    box-shadow: 0 4px 14px 0 rgba(124, 58, 237, 0.39);
}

/* --- Responsive Design --- */
@media (max-width: 768px) {
    body {
        padding: 1rem;
    }

    .page-header h1 {
        font-size: 2rem;
    }

    #filter-form {
        flex-direction: column;
        align-items: stretch;
    }
}

</style>
</head>
<body>

<div class="main-container">
    <!-- <header class="page-header">
        <h1 id="course_title_h1">Discover Your Next Course</h1>
        <p>Browse courses by university, department, or search for a specific topic.</p>
    </header> -->

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

    const fetchCourses = (page = null) => {
        const data = new FormData(form);
        if(page) data.set('page', page);
        data.set('is_ajax', 'true');
        const params = new URLSearchParams(data);
        // Optional: Show a loading indicator
        container.style.opacity = '0.5';
        fetch('pages/courses.php?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
        })
        .catch(console.error);
    };

    form.addEventListener('submit', e => { e.preventDefault(); fetchCourses(); });
    document.getElementById('search').addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(() => fetchCourses(), 500); });
    document.getElementById('university').addEventListener('change', () => fetchCourses());
    document.getElementById('department').addEventListener('change', () => fetchCourses());

    container.addEventListener('click', e => {
        const a = e.target.closest('.pagination a');
        if(a) { e.preventDefault(); const page = a.getAttribute('data-page'); fetchCourses(page); }
    });
});
</script>

</body>
</html>