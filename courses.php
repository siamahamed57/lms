<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/header.php';

// --- Data Fetching ---
// Fetch all published courses along with instructor and some stats
$courses_sql = "
    SELECT 
        c.id, c.title, c.subtitle, c.thumbnail, c.price,
        u.name as instructor_name,
        (SELECT COUNT(id) FROM lessons WHERE course_id = c.id) as total_lessons,
        (SELECT COUNT(id) FROM enrollments WHERE course_id = c.id) as total_students
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE c.status = 'published'
    ORDER BY c.created_at DESC
";

$courses = db_select($courses_sql);

?>

<style>
    /* Theme variables to match home.php and about.php */
    :root, [data-theme="dark"] {
        --background-gradient-dots: #4f46e5;
        --background-container: #0f172a;
    }

    [data-theme="light"] {
        --background-gradient-dots: #818cf8;
        --background-container: #e2e8f0;
    }

    /* Custom styles for the courses page, consistent with your site's theme */
    .course-card {
        background: var(--surface, rgba(22, 27, 34, 0.6));
        backdrop-filter: blur(16px) saturate(180%);
        -webkit-backdrop-filter: blur(16px) saturate(180%);
        border: 1px solid var(--border, rgba(255, 255, 255, 0.1));
        border-radius: 24px; /* Softer, more modern radius */
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .course-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        border-color: var(--primary, #6366f1);
    }
    .card-thumbnail {
        height: 200px;
        width: 100%;
        object-fit: cover;
        border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.1));
    }
    .card-content {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #fff);
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }
    .card-instructor {
        font-size: 0.875rem;
        color: var(--text-secondary, #a1a1aa);
        margin-bottom: 1rem;
    }
    .card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px solid var(--border, rgba(255, 255, 255, 0.1));
    }
    .card-price {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--primary, #6366f1);
    }
    .card-stats {
        display: flex;
        gap: 1rem;
        font-size: 0.8rem;
        color: var(--text-secondary, #a1a1aa);
    }

    /* Animated Background Shapes from about.php */
    .floating-shapes {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: -1;
    }
    .floating-shapes li {
        position: absolute;
        display: block;
        list-style: none;
        width: 20px;
        height: 20px;
        background: rgba(164, 53, 240, 0.2);
        animation: animate-bg 25s linear infinite;
        bottom: -200px;
    }
    .floating-shapes li:nth-child(1){ left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
    .floating-shapes li:nth-child(2){ left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
    .floating-shapes li:nth-child(3){ left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
    .floating-shapes li:nth-child(4){ left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
    .floating-shapes li:nth-child(5){ left: 65%; width: 20px; height: 20px; animation-delay: 0s; }
    .floating-shapes li:nth-child(6){ left: 75%; width: 110px; height: 110px; animation-delay: 3s; }
    .floating-shapes li:nth-child(7){ left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
    .floating-shapes li:nth-child(8){ left: 50%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s; }
    .floating-shapes li:nth-child(9){ left: 20%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s; }
    .floating-shapes li:nth-child(10){ left: 85%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s; }

    @keyframes animate-bg {
        0% { transform: translateY(0) rotate(0deg); opacity: 1; border-radius: 0; }
        100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; border-radius: 50%; }
    }
</style>

<div class="absolute inset-0 -z-10 h-full w-full bg-[var(--background-container)] bg-[radial-gradient(var(--background-gradient-dots)_1px,transparent_1px)] [background-size:16px_16px]"></div>
<ul class="floating-shapes">
    <li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li>
</ul>
<main class="container mx-auto max-w-7xl px-4 py-10">
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-extrabold gradient-text-primary mb-4">Explore Our Courses</h1>
        <p class="text-lg text-text-secondary max-w-2xl mx-auto">Find the perfect course to boost your skills and advance your career.</p>
    </div>

    <?php if (empty($courses)): ?>
        <div class="text-center py-20 px-6 bg-surface rounded-2xl">
            <i class="fas fa-search-minus text-6xl text-primary opacity-50 mb-6"></i>
            <h2 class="text-3xl font-bold text-text-primary mb-2">No Courses Available</h2>
            <p class="text-text-secondary">We are working on adding new courses. Please check back soon!</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach ($courses as $course): ?>
                <a href="course-detail?id=<?= $course['id'] ?>" class="course-card group">
                    <img src="<?= htmlspecialchars($course['thumbnail'] ?? 'assets/images/default_course.jpg') ?>" 
                         alt="Thumbnail for <?= htmlspecialchars($course['title']) ?>" 
                         class="card-thumbnail"
                         onerror="this.onerror=null;this.src='assets/images/default_course.jpg';">
                    
                    <div class="card-content">
                        <h3 class="card-title group-hover:text-primary transition-colors"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="card-instructor">By <?= htmlspecialchars($course['instructor_name']) ?></p>
                        
                        <div class="card-meta">
                            <div class="card-price">
                                <?= $course['price'] > 0 ? '$' . number_format($course['price'], 2) : 'Free' ?>
                            </div>
                            <div class="card-stats">
                                <span><i class="fas fa-book-open mr-1"></i> <?= $course['total_lessons'] ?></span>
                                <span><i class="fas fa-users mr-1"></i> <?= $course['total_students'] ?></span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php
include_once __DIR__ . '/includes/footer.php';
?>