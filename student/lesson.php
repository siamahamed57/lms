<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// --- Authorization & Data Fetching ---
if (!isset($_SESSION['user_id'])) {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Please log in to view this lesson.</h2></div>";
    exit;
}
$student_id = $_SESSION['user_id'];
$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($lesson_id <= 0) {
    die("Invalid lesson specified.");
}

// --- Fetch lesson, course, and curriculum data ---
$lesson_sql = "SELECT l.*, c.id as course_id, c.title as course_title 
               FROM lessons l 
               JOIN courses c ON l.course_id = c.id 
               WHERE l.id = ?";
$lesson_data = db_select($lesson_sql, "i", [$lesson_id]);

if (empty($lesson_data)) {
    die("Lesson not found.");
}
$lesson = $lesson_data[0];
$course_id = $lesson['course_id'];

// --- Fetch lesson resources ---
$resources = db_select("SELECT * FROM lesson_resources WHERE lesson_id = ?", "i", [$lesson_id]);

// --- Security Check: Ensure student is enrolled ---
$enrollment_check_raw = db_select("SELECT id, expires_at, status FROM enrollments WHERE student_id = ? AND course_id = ?", "ii", [$student_id, $course_id]);
if (empty($enrollment_check_raw)) {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Denied.</h2><p>You are not enrolled in this course.</p></div>";
    exit;
}
$enrollment_check = $enrollment_check_raw[0];
if ($enrollment_check['status'] === 'blocked') {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Blocked.</h2><p>Your access to this course has been blocked by an administrator.</p></div>";
    exit;
}
if ($enrollment_check['expires_at'] !== null && strtotime($enrollment_check['expires_at']) < time()) {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Expired.</h2><p>Your access to this course has expired. Please re-enroll to continue.</p></div>";
    exit;
}

// --- Fetch full curriculum for the sidebar ---
$curriculum_sql = "
    (SELECT id, title, 'lesson' as type, order_no, quiz_id FROM lessons WHERE course_id = ?)
    UNION
    (SELECT id, title, 'quiz' as type, 999 as order_no, NULL as quiz_id FROM quizzes WHERE course_id = ?)
    ORDER BY order_no, title
";
$curriculum_items = db_select($curriculum_sql, "ii", [$course_id, $course_id]);

// --- Fetch all course resources for the sidebar ---
$course_resources_sql = "SELECT r.file_name, r.file_path FROM lesson_resources r JOIN lessons l ON r.lesson_id = l.id WHERE l.course_id = ?";
$course_resources = db_select($course_resources_sql, "i", [$course_id]);

// --- Fetch student's completion status for all lessons in this course ---
$completed_lessons_raw = db_select("SELECT lesson_id FROM student_lesson_completion WHERE student_id = ? AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)", "ii", [$student_id, $course_id]);
$completed_lesson_ids = array_column($completed_lessons_raw, 'lesson_id');

// --- Find Previous & Next Lesson IDs ---
$prev_lesson_id = null;
$next_lesson_id = null;
$current_index = -1;

$lesson_items_only = array_filter($curriculum_items, fn($item) => $item['type'] === 'lesson');
$lesson_ids_ordered = array_column($lesson_items_only, 'id');
$current_key = array_search($lesson_id, $lesson_ids_ordered);

if ($current_key !== false) {
    if ($current_key > 0) {
        $prev_lesson_id = $lesson_ids_ordered[$current_key - 1];
    }
    if ($current_key < count($lesson_ids_ordered) - 1) {
        $next_lesson_id = $lesson_ids_ordered[$current_key + 1];
    }
}

function get_embed_url($url) {
    if (empty($url)) return null;
    if (preg_match('/(youtube\.com|youtu\.be)\/(watch\?v=|embed\/|v\/|)([\w-]{11})/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[3];
    }
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    return null;
}

$is_completed = in_array($lesson_id, $completed_lesson_ids);

$quiz_popup_message = $_SESSION['quiz_result_popup'] ?? null;
unset($_SESSION['quiz_result_popup']);

?>
<style>
    .learning-wrapper { display: flex; height: calc(100vh - 80px); background: var(--bg-secondary); }
    .learning-content { flex-grow: 1; padding: 2rem; overflow-y: auto; }
    .learning-sidebar { flex: 0 0 350px; background: var(--surface); border-left: 1px solid var(--border); overflow-y: auto; padding: 1.5rem; }
    .video-container { position: relative; padding-bottom: 56.25%; height: 0; background: #000; border-radius: 12px; overflow: hidden; margin-bottom: 2rem; }
    .video-container iframe,
    .video-container video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
    .video-overlay {
        position: absolute;
        font-size: 16px; font-weight: 700; color: #b915ff;
        opacity: 0.6; text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        pointer-events: none; z-index: 10;
        transition: top 3s ease-in-out, left 3s ease-in-out;
        padding: 5px 10px; background-color: rgba(0,0,0,0.3); border-radius: 5px;
    }
    .lesson-title { font-size: 2rem; font-weight: 800; color: var(--text); margin-bottom: 1rem; }
    .lesson-article { color: var(--text-light); line-height: 1.8; }
    .curriculum-list { list-style: none; padding: 0; margin-top: 1rem; }
    .curriculum-header { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-light); padding: 1rem 0.8rem 0.5rem; border-bottom: 1px solid var(--border); margin-bottom: 0.5rem; }
    .curriculum-item { display: flex; align-items: center; padding: 0.8rem; border-radius: 8px; margin-bottom: 0.5rem; transition: background-color 0.2s; }
    .curriculum-item a { text-decoration: none; color: var(--text-light); font-weight: 500; flex-grow: 1; }
    .curriculum-item:hover { background-color: var(--surface-light); }
    .curriculum-item.active { background-color: rgba(185, 21, 255, 0.1); border-left: 4px solid var(--primary); }
    .curriculum-item.active a { color: var(--primary); font-weight: 700; }
    .curriculum-item .icon { width: 20px; text-align: center; margin-right: 1rem; }
    .completed .icon { color: var(--success); animation: pulse 1.5s infinite; }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    .resources-section { margin-top: 2rem; padding: 1.5rem; background: var(--surface); border-radius: 12px; }
    .resources-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; }
    .resources-list { list-style: none; padding: 0; }
    .resources-list li a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-radius: 8px; text-decoration: none; color: var(--text-light); transition: all 0.2s; }
    .resources-list li a:hover { background: var(--surface-light); color: var(--primary); }
    .lesson-nav { display: flex; justify-content: space-between; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); }
    .btn-nav { padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.2s; }
    .btn-nav.prev { background: var(--surface-light); color: var(--text); }
    .btn-nav.next { background: var(--gradient); color: white; }
    .btn-nav.disabled { background: var(--border); color: var(--text-light); cursor: not-allowed; }
    .btn-action { display: inline-block; padding: 0.8rem 2rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; }

    @media (max-width: 768px) {
        .learning-wrapper { flex-direction: column; height: auto; }
        .learning-sidebar { flex: 1 1 auto; border-left: none; border-top: 1px solid var(--border); max-height: 50vh; }
        .learning-content { padding: 1rem; }
        .lesson-title { font-size: 1.5rem; }
    }
</style>

<div class="learning-wrapper">
    <div class="learning-content">
        <h1 class="lesson-title fade-in-up" style="animation-delay: 0.1s;"><?= htmlspecialchars($lesson['title']) ?></h1>

        <?php if ($embed_url = get_embed_url($lesson['video_url'])): ?>
            <div class="video-container fade-in-up" style="animation-delay: 0.2s;">
                <iframe src="<?= $embed_url ?>" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                <div class="video-overlay" id="video-watermark"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
            </div>
        <?php elseif (!empty($lesson['video_file_path'])): ?>
            <div class="video-container fade-in-up" style="animation-delay: 0.2s;">
                <video controls controlsList="nodownload" class="w-full h-full">
                    <source src="<?= htmlspecialchars($lesson['video_file_path']) ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <div class="video-overlay" id="video-watermark"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
            </div>
        <?php elseif (!empty($lesson['article_content'])): ?>
            <div class="lesson-article fade-in-up" style="animation-delay: 0.2s;">
                <?= $lesson['article_content'] /* This should be sanitized with a proper HTML purifier in production */ ?>
            </div>
        <?php else: ?>
            <div class="p-8 text-center bg-surface-light rounded-lg fade-in-up" style="animation-delay: 0.2s;">
                <p>No content available for this lesson.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($resources)): ?>
        <div class="resources-section fade-in-up" style="animation-delay: 0.3s;">
            <h3 class="resources-title">Lesson Resources</h3>
            <ul class="resources-list">
                <?php foreach ($resources as $resource): ?>
                    <li>
                        <a href="<?= htmlspecialchars($resource['file_path']) ?>" download>
                            <i class="fas fa-download"></i>
                            <span><?= htmlspecialchars($resource['file_name']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="lesson-nav fade-in-up" style="animation-delay: 0.4s;">
            <div>
                <?php if ($prev_lesson_id): ?>
                    <a href="?page=lesson&id=<?= $prev_lesson_id ?>" class="btn-nav prev">&larr; Previous Lesson</a>
                <?php else: ?>
                    <span class="btn-nav disabled">&larr; Previous Lesson</span>
                <?php endif; ?>
            </div>
            <div>
                <?php if (!$is_completed): ?>
                    <form method="POST" action="?page=lesson&id=<?= $lesson_id ?>" style="display: inline;">
                        <input type="hidden" name="action" value="mark_complete">
                        <input type="hidden" name="lesson_id_to_complete" value="<?= $lesson_id ?>">
                        <button type="submit" class="btn-action" style="background: var(--success); color: white;">Mark as Complete</button>
                    </form>
                <?php endif; ?>

                <?php if ($next_lesson_id): ?>
                    <a href="?page=lesson&id=<?= $next_lesson_id ?>" class="btn-nav next">Next Lesson &rarr;</a>
                <?php else: ?>
                    <a href="?page=my-courses" class="btn-nav next">Finish Course &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <aside class="learning-sidebar fade-in-up" style="animation-delay: 0.2s;">
        <h3 class="text-xl font-bold mb-4"><?= htmlspecialchars($lesson['course_title']) ?></h3>
        <ul class="curriculum-list">
            <?php 
            $current_type = '';
            foreach ($curriculum_items as $index => $item): 
                if ($item['type'] !== $current_type) {
                    $current_type = $item['type'];
                    echo '<li class="curriculum-header">' . htmlspecialchars(ucfirst($current_type)) . 's</li>';
                }

                    $is_current = ($item['type'] === 'lesson' && $item['id'] == $lesson_id);
                    $is_item_completed = ($item['type'] === 'lesson' && in_array($item['id'], $completed_lesson_ids));
                    $link = ($item['type'] === 'lesson') ? '?page=lesson&id=' . $item['id'] : '?page=quiz&id=' . $item['id'];
                    
                    $icon_class = 'fa-play-circle'; // Default for lesson
                    if ($item['type'] === 'quiz') $icon_class = 'fa-question-circle';
                    if ($is_item_completed) $icon_class = 'fa-check-circle';
                ?>
                <li class="curriculum-item fade-in-up <?= $is_current ? 'active' : '' ?> <?= $is_item_completed ? 'completed' : '' ?>" style="animation-delay: <?= $index * 0.05 ?>s;">
                    <i class="fas <?= $icon_class ?> icon"></i>
                    <a href="<?= $link ?>"><?= htmlspecialchars($item['title']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($course_resources)): ?>
        <div class="resources-section" style="margin-top: 2rem;">
            <h4 class="curriculum-header" style="border: none; padding: 0 0 0.5rem 0;">All Course Materials</h4>
            <ul class="resources-list">
                <?php foreach ($course_resources as $resource): ?>
                    <li>
                        <a href="<?= htmlspecialchars($resource['file_path']) ?>" download>
                            <i class="fas fa-download"></i>
                            <span><?= htmlspecialchars($resource['file_name']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const watermark = document.getElementById('video-watermark');
    if (watermark) {
        const videoContainer = watermark.parentElement;

        const moveWatermark = () => {
            if (!videoContainer) return;

            const containerWidth = videoContainer.offsetWidth;
            const containerHeight = videoContainer.offsetHeight;
            const watermarkWidth = watermark.offsetWidth;
            const watermarkHeight = watermark.offsetHeight;

            const newX = Math.floor(Math.random() * (containerWidth - watermarkWidth));
            const newY = Math.floor(Math.random() * (containerHeight - watermarkHeight));

            watermark.style.left = `${newX}px`;
            watermark.style.top = `${newY}px`;
        };

        setInterval(moveWatermark, 5000); // Move every 5 seconds
        setTimeout(moveWatermark, 100); // Set initial random position
    }

    <?php if ($quiz_popup_message): ?>
    // Using a simple alert for the popup as requested.
    // A more styled modal could be used here for better UX.
    alert('<?= addslashes(htmlspecialchars($quiz_popup_message)) ?>');
    // Clean up the URL
    const url = new URL(window.location);
    url.searchParams.delete('quiz_completed');
    window.history.replaceState({}, document.title, url);
    <?php endif; ?>
});
</script>