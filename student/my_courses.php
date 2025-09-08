<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    // This page is loaded via AJAX, so we just show an error.
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Please log in to view your courses.</h2></div>";
    exit;
}
$student_id = $_SESSION['user_id'];

// --- Data Fetching ---
// Fetch all courses the student is enrolled in.
// We also get the instructor's name and calculate the progress.
// The 'continue_lesson_id' will be the first uncompleted lesson, or the last lesson if the course is complete.
$enrolled_courses_query = "
    SELECT 
        c.id, c.title, c.thumbnail, c.description,
        u.name as instructor_name,
        (SELECT COUNT(id) FROM lessons WHERE course_id = c.id) as total_lessons,
        (SELECT COUNT(s.id) FROM student_lesson_completion s JOIN lessons l ON s.lesson_id = l.id WHERE s.student_id = e.student_id AND l.course_id = c.id) as completed_lessons,
        (SELECT l.id FROM lessons l WHERE l.course_id = c.id AND l.id NOT IN (SELECT slc.lesson_id FROM student_lesson_completion slc WHERE slc.student_id = e.student_id) ORDER BY l.order_no ASC, l.id ASC LIMIT 1) as next_lesson_id,
        (SELECT l.id FROM lessons l WHERE l.course_id = c.id ORDER BY l.order_no ASC, l.id ASC LIMIT 1) as first_lesson_id,
        (SELECT l.id FROM lessons l WHERE l.course_id = c.id ORDER BY l.order_no DESC, l.id DESC LIMIT 1) as last_lesson_id
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE e.student_id = ?
    ORDER BY e.enrolled_at DESC
";

$enrolled_courses = db_select($enrolled_courses_query, "i", [$student_id]);

?>

<style>
    /* Styles for the My Courses page */
    .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2rem;
    }
    .course-card {
        background: linear-gradient(135deg, #b915ff63, #8a5cf652);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .course-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }
    .card-thumbnail {
        height: 180px;
        width: 100%;
        object-fit: cover;
    }
    .card-content {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }
    .card-instructor {
        font-size: 0.875rem;
        color: var(--text-light);
        margin-bottom: 1rem;
    }
    .progress-container {
        margin-top: auto; /* Pushes progress bar to the bottom */
    }
    .progress-bar {
        width: 100%;
        background-color: var(--border);
        border-radius: 8px;
        height: 8px;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    .progress-bar-inner {
        height: 100%;
        background: var(--gradient);
        border-radius: 8px;
        transition: width 0.5s ease-in-out;
    }
    .progress-text {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-light);
    }
    .card-actions {
        padding: 1rem 1.5rem;
        background-color: var(--surface-light);
        border-top: 1px solid var(--border);
    }
    .btn-continue {
        display: block;
        width: 100%;
        text-align: center;
        padding: 0.75rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        background: var(--gradient);
        color: white;
        transition: all 0.3s ease;
    }
    .btn-continue:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(185, 21, 255, 0.3);
    }
    .btn-completed {
        background: linear-gradient(135deg, var(--success) 0%, #15803d 100%);
    }
    .btn-revisit {
        background: var(--primary-dark); margin-top: 0.5rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    }
    .btn-materials {
        display: block; width: 100%; text-align: center; padding: 0.5rem; border-radius: 8px; font-weight: 600;
        text-decoration: none; background: var(--surface-light); color: var(--text-light); transition: all 0.3s ease; margin-top: 0.5rem; border: 1px solid var(--border); cursor: pointer;
    }
    .empty-state {
        text-align: center;
        padding: 4rem;
        background: var(--surface);
        border-radius: 16px;
    }
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; display: none; justify-content: center; align-items: center; }
    .modal-content { background: black; padding: 2rem; border-radius: 16px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1rem; }
    .modal-title { font-size: 1.5rem; font-weight: 700; }
    .modal-close { background: none; border: none; font-size: 2rem; cursor: pointer; color: var(--text-light); }
    .resource-item { display: flex; align-items: center; gap: 1rem; padding: 0.75rem; border-radius: 8px; }
    .resource-item:hover { background: var(--surface-light); }
    .resource-item a { text-decoration: none; color: var(--text); font-weight: 500; }
    .resource-item .icon { color: var(--primary); }

</style>

<div class="container mx-auto p-4 md:p-8">
    <div class="header">
        <h1 class="title">My Learning</h1>
    </div>

    <?php if (empty($enrolled_courses)): ?>
        <div class="empty-state">
            <i class="fas fa-book-open text-5xl text-primary mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Your learning journey awaits!</h2>
            <p class="text-text-light mb-6">You haven't enrolled in any courses yet. Explore our catalog to get started.</p>
            <a href="?_page=courses" class="btn-continue" style="max-width: 200px; margin: 0 auto;">Browse Courses</a>
        </div>
    <?php else: ?>
        <div class="course-grid">
            <?php foreach ($enrolled_courses as $course): ?>
                <?php
                    $progress = 0;
                    if ($course['total_lessons'] > 0) {
                        $progress = round(($course['completed_lessons'] / $course['total_lessons']) * 100);
                    }
                    // Determine the link for the continue button
                    $continue_link = $course['next_lesson_id'] ? '?page=lesson&id=' . $course['next_lesson_id'] : '?page=lesson&id=' . $course['last_lesson_id'];
                    $revisit_link = $course['first_lesson_id'] ? '?page=lesson&id=' . $course['first_lesson_id'] : '#';
                ?>
                <div class="course-card">
                    <img src="<?= htmlspecialchars($course['thumbnail'] ?? 'assets/images/default_course.jpg') ?>" 
                         alt="Course thumbnail for <?= htmlspecialchars($course['title']) ?>" 
                         class="card-thumbnail"
                         onerror="this.onerror=null;this.src='assets/images/default_course.jpg';">
                    
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="card-instructor">By <?= htmlspecialchars($course['instructor_name']) ?></p>
                        
                        <div class="progress-container">
                            <div class="progress-text">
                                <span>Progress</span>
                                <span><?= $progress ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-bar-inner" style="width: <?= $progress ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-actions">
                        <?php if ($progress >= 100): ?>
                            <a href="?page=certificate&course_id=<?= $course['id'] ?>" class="btn-continue btn-completed" target="_blank"><i class="fas fa-award mr-2"></i> View Certificate</a>
                            <a href="<?= $revisit_link ?>" class="btn-continue btn-revisit"><i class="fas fa-book-open mr-2"></i> Revisit Course</a>
                            
                        <?php else: ?>
                            <a href="<?= $continue_link ?>" class="btn-continue"><i class="fas fa-play mr-2"></i> Continue Learning</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Materials Modal -->
<div id="materials-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title" class="modal-title">Course Materials</h2>
            <button id="modal-close-btn" class="modal-close">&times;</button>
        </div>
        <div id="modal-body">
            <!-- Resources will be loaded here by JavaScript -->
            <p>Loading...</p>
        </div>
    </div>
</div>

<!-- Note: To make progress tracking work, you need to add logic to your lesson page to mark a lesson as complete.
     This would involve creating a new table 'student_lesson_completion' and inserting a record when a student finishes a lesson.
     Example SQL for the new table:
     CREATE TABLE `student_lesson_completion` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `student_id` bigint(20) UNSIGNED NOT NULL,
        `lesson_id` bigint(20) UNSIGNED NOT NULL,
        `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `student_lesson` (`student_id`,`lesson_id`)
     ) ENGINE=InnoDB;
-->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('materials-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const closeModalBtn = document.getElementById('modal-close-btn');

    document.querySelectorAll('.btn-materials').forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.dataset.courseId;
            const courseTitle = this.closest('.course-card').querySelector('.card-title').textContent;
            
            modalTitle.textContent = `Materials for: ${courseTitle}`;
            modalBody.innerHTML = '<p>Loading...</p>';
            modal.style.display = 'flex';

            fetch(`student/get_resources.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = `<p style="color: red;">${data.error}</p>`;
                        return;
                    }
                    if (data.length === 0) {
                        modalBody.innerHTML = '<p>No downloadable materials found for this course.</p>';
                        return;
                    }

                    let html = '<ul>';
                    data.forEach(resource => {
                        html += `<li class="resource-item"><i class="fas fa-file-download icon"></i> <a href="${resource.file_path}" download>${resource.file_name}</a></li>`;
                    });
                    html += '</ul>';
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = `<p style="color: red;">An error occurred while fetching materials.</p>`;
                    console.error('Error:', error);
                });
        });
    });

    const closeModal = () => modal.style.display = 'none';
    closeModalBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', e => e.target === modal && closeModal());
});
</script>