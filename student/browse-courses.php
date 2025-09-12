<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Denied!</h2></div>";
    exit;
}

$search_term = trim($_GET['search'] ?? '');
$courses = [];

if (!empty($search_term)) {
    // If searching, find all matching published courses
    $sql = "SELECT c.id, c.title, c.subtitle, c.thumbnail, c.price, u.name as instructor_name
            FROM courses c
            JOIN users u ON c.instructor_id = u.id
            WHERE c.status = 'published' AND (c.title LIKE ? OR c.subtitle LIKE ? OR u.name LIKE ?)
            ORDER BY c.created_at DESC";
    $search_like = "%$search_term%";
    $courses = db_select($sql, 'sss', [$search_like, $search_like, $search_like]);
} else {
    // If not searching, show the 6 most recent published courses
    $sql = "SELECT c.id, c.title, c.subtitle, c.thumbnail, c.price, u.name as instructor_name
            FROM courses c
            JOIN users u ON c.instructor_id = u.id
            WHERE c.status = 'published'
            ORDER BY c.created_at DESC
            LIMIT 6";
    $courses = db_select($sql);
}
?>
<style>
    /* Reusing styles from other dashboard pages */
    :root {
        --primary-color: #b915ff; --primary-hover-color: #8b00cc;
        --glass-bg: rgba(255, 255, 255, 0.07); --glass-border: rgba(255, 255, 255, 0.2);
        --text-primary: #f0f0f0; --text-secondary: #a0a0a0; --input-bg: rgba(0, 0, 0, 0.3);
    }
    .browse-container { padding: 2rem; }
    .browse-header { margin-bottom: 2rem; }
    .browse-header h1 { font-size: 2.25rem; font-weight: 600; }
    .browse-header p { color: var(--text-secondary); }

    .search-box { display: flex; max-width: 600px; margin-bottom: 2.5rem; background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 12px; overflow: hidden; }
    .search-input { flex-grow: 1; border: none; background: transparent; padding: 0.8rem 1.2rem; color: var(--text-primary); font-size: 1rem; }
    .search-input:focus { outline: none; }
    .search-btn { border: none; background: var(--primary-color); color: #fff; padding: 0 1.5rem; cursor: pointer; font-size: 1.1rem; transition: background-color 0.3s ease; }
    .search-btn:hover { background-color: var(--primary-hover-color); }

    .course-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
    .course-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; overflow: hidden; transition: all 0.3s ease; display: flex; flex-direction: column; text-decoration: none; color: var(--text-primary); }
    .course-card:hover { transform: translateY(-8px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
    .card-thumbnail { height: 180px; width: 100%; object-fit: cover; }
    .card-content { padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column; }
    .card-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }
    .card-instructor { font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem; }
    .card-meta { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--glass-border); }
    .card-price { font-size: 1.25rem; font-weight: 700; color: var(--primary-color); }
    .empty-state { text-align: center; color: var(--text-secondary); padding: 3rem; grid-column: 1 / -1; }
</style>

<div class="browse-container">
    <div class="browse-header">
        <h1>Explore Courses</h1>
        <p>Find the perfect course to expand your knowledge.</p>
    </div>

    <form method="GET">
        <input type="hidden" name="page" value="browse-courses">
        <div class="search-box">
            <input type="text" name="search" class="search-input" placeholder="Search for courses, instructors..." value="<?= htmlspecialchars($search_term) ?>">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </div>
    </form>

    <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;"><?= !empty($search_term) ? 'Search Results' : 'Recently Added Courses' ?></h2>

    <div class="course-grid">
        <?php if (empty($courses)): ?>
            <div class="empty-state"><i class="fas fa-search-minus" style="font-size: 2rem; margin-bottom: 1rem;"></i><p>No courses found.</p></div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <a href="?page=course-details&id=<?= $course['id'] ?>" class="course-card">
                    <img src="<?= htmlspecialchars($course['thumbnail'] ?? 'assets/images/default_course.jpg') ?>" alt="Thumbnail" class="card-thumbnail" onerror="this.onerror=null;this.src='assets/images/default_course.jpg';">
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="card-instructor">By <?= htmlspecialchars($course['instructor_name']) ?></p>
                        <div class="card-meta">
                            <span class="card-price"><?= $course['price'] > 0 ? '$' . number_format($course['price'], 2) : 'Free' ?></span>
                            <span class="text-sm text-secondary">View Details &rarr;</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>