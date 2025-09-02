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

// --- Total courses for pagination (JOIN users for search) ---
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
        <div class="text-center text-gray-500 text-2xl py-12">No courses found.</div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($courses as $course): ?>
                <div class="card rounded-xl overflow-hidden shadow-lg">
                    <img src="<?= htmlspecialchars($course['thumbnail']) ?>" 
                         alt="<?= htmlspecialchars($course['title']) ?>" 
                         class="w-full h-40 object-cover"
                         onerror="this.onerror=null;this.src='https://placehold.co/400x250/e5e7eb/4b5563?text=Thumbnail';">
                    <div class="p-6">
                        <h3 class="font-bold text-xl mb-2 truncate"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="text-sm mb-4 text-gray-500 truncate"><?= htmlspecialchars($course['subtitle']) ?></p>
                        <div class="flex items-center text-sm mb-2">
                            <i class="fas fa-user-circle text-purple-500 mr-2"></i>
                            <span><?= htmlspecialchars($course['instructor_name']) ?></span>
                        </div>
                        <div class="flex items-center text-sm mb-2">
                            <i class="fas fa-university text-purple-500 mr-2"></i>
                            <span><?= htmlspecialchars($course['university_name']) ?></span>
                        </div>
                        <div class="flex justify-between items-center mt-4">
                            <span class="text-lg font-bold text-purple-600">$<?= number_format($course['price'],2) ?></span>
                            <a href="detail.php?id=<?= htmlspecialchars($course['id']) ?>" class="text-sm font-medium text-purple-600 hover:underline transition-colors duration-200">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_pages>1): ?>
            <div class="pagination flex justify-center items-center space-x-2 mt-8">
                <?php if($current_page>1): ?>
                    <a href="#" data-page="<?= $current_page-1 ?>" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">Previous</a>
                <?php endif; ?>
                <?php for($i=1;$i<=$total_pages;$i++): ?>
                    <a href="#" data-page="<?= $i ?>" class="px-4 py-2 rounded-lg <?= $i==$current_page?'bg-purple-600 text-white':'bg-gray-200 hover:bg-gray-300' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if($current_page<$total_pages): ?>
                    <a href="#" data-page="<?= $current_page+1 ?>" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif;
}

// --- AJAX output ---
if($is_ajax){
    render_course_list($courses,$current_page,$total_pages);
    exit;
}
?>

<!-- Full HTML page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Courses | UNIES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="p-8">

<h1 class="text-4xl font-extrabold text-center mb-2 text-purple-600">Discover Your Next Course</h1>
<p class="text-center text-gray-500 mb-12">Browse courses by university, department, or search for a specific topic.</p>

<div class="md:grid md:grid-cols-4 md:gap-8">
    <!-- Filter Sidebar -->
    <div class="col-span-1 mb-8 md:mb-0">
        <div class="filter-bg rounded-xl p-6 shadow-lg sticky top-8">
            <h2 class="text-xl font-bold mb-4">Search & Filter</h2>
            <form id="filter-form" class="space-y-4">
                <div>
                    <label>Search</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search_query) ?>" class="w-full border rounded p-2">
                </div>
                <div>
                    <label>University</label>
                    <select id="university" name="university" class="w-full border rounded p-2">
                        <option value="">All Universities</option>
                        <?php foreach($universities as $uni): ?>
                            <option value="<?= $uni['id'] ?>" <?= $university_id==$uni['id']?'selected':'' ?>><?= htmlspecialchars($uni['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Department</label>
                    <select id="department" name="department" class="w-full border rounded p-2">
                        <option value="">All Departments</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_id==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Course List -->
    <div id="course-list-container" class="col-span-3">
        <?php render_course_list($courses,$current_page,$total_pages); ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const form = document.getElementById('filter-form');
    const container = document.getElementById('course-list-container');
    let debounce;

    const fetchCourses = (page=null)=>{
        const data = new FormData(form);
        if(page) data.set('page',page);
        data.set('is_ajax','true');
        const params = new URLSearchParams(data);
        fetch('pages/courses.php?' + params.toString(), { headers:{ 'X-Requested-With':'XMLHttpRequest' } })
            .then(r=>r.text())
            .then(html=>{ container.innerHTML = html; })
            .catch(console.error);
    };

    form.addEventListener('submit', e=>{ e.preventDefault(); fetchCourses(); });
    document.getElementById('search').addEventListener('input', ()=>{ clearTimeout(debounce); debounce = setTimeout(()=>fetchCourses(), 500); });
    document.getElementById('university').addEventListener('change', ()=>fetchCourses());
    document.getElementById('department').addEventListener('change', ()=>fetchCourses());

    container.addEventListener('click', e=>{
        const a = e.target.closest('.pagination a');
        if(a){ e.preventDefault(); const page = a.getAttribute('data-page'); fetchCourses(page); }
    });
});
</script>

</body>
</html>
