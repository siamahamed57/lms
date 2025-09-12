<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Denied!</h2><p>This page is for students only.</p></div>";
    exit;
}
$student_id = $_SESSION['user_id'];

// --- Data Fetching ---
// 1. Get all enrolled courses and their lesson progress
$courses_sql = "
    SELECT 
        c.id AS course_id,
        c.title AS course_title,
        (SELECT COUNT(id) FROM lessons WHERE course_id = c.id) AS total_lessons,
        (SELECT COUNT(slc.id) 
         FROM student_lesson_completion slc 
         JOIN lessons l ON slc.lesson_id = l.id 
         WHERE slc.student_id = e.student_id AND l.course_id = c.id) AS completed_lessons
    FROM 
        enrollments e
    JOIN 
        courses c ON e.course_id = c.id
    WHERE 
        e.student_id = ?
    ORDER BY 
        c.title ASC
";
$enrolled_courses = db_select($courses_sql, "i", [$student_id]);

// 2. For each course, get quiz results
$grades_details = [];
foreach ($enrolled_courses as $course) {
    $course_id = $course['course_id'];
    
    $quizzes_sql = "
        SELECT 
            q.id AS quiz_id,
            q.title AS quiz_title,
            (SELECT SUM(marks) FROM questions WHERE quiz_id = q.id) AS total_marks,
            (SELECT MAX(score) FROM student_quiz_attempts WHERE quiz_id = q.id AND student_id = ?) AS best_score
        FROM 
            quizzes q
        WHERE 
            q.course_id = ?
    ";
    $course['quizzes'] = db_select($quizzes_sql, 'ii', [$student_id, $course_id]);
    
    $grades_details[] = $course;
}
?>

<style>
    /* Styles for the Grades page */
    .grades-container { padding: 2rem; }
    .grades-header { margin-bottom: 2rem; }
    .grades-header h1 { font-size: 2.25rem; font-weight: 600; }
    .grades-header p { color: var(--text-secondary); }

    .course-grade-card {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        margin-bottom: 2rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .course-grade-card:hover {
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        border-color: var(--primary-color);
    }
    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--glass-border);
    }
    .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .progress-container {
        margin-top: 1rem;
    }
    .progress-text {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-bottom: 0.25rem;
    }
    .progress-bar {
        width: 100%;
        height: 8px;
        background-color: rgba(0,0,0,0.3);
        border-radius: 4px;
        overflow: hidden;
    }
    .progress-bar-inner {
        height: 100%;
        background: linear-gradient(90deg, #b915ff, #8b5cf6);
        border-radius: 4px;
        transition: width 0.5s ease;
    }
    .card-body {
        padding: 1.5rem;
    }
    .card-body h4 {
        font-size: 1.1rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }
    .quiz-table {
        width: 100%;
        border-collapse: collapse;
    }
    .quiz-table th, .quiz-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--glass-border);
    }
    .quiz-table th {
        font-size: 0.8rem;
        color: var(--text-secondary);
        text-transform: uppercase;
    }
    .quiz-table tr:last-child td {
        border-bottom: none;
    }
    .score {
        font-weight: 600;
    }
    .score-not-taken {
        color: var(--text-secondary);
        font-style: italic;
    }
    .empty-state {
        text-align: center;
        color: var(--text-secondary);
        padding: 3rem;
    }
    .certificate-link {
        display: inline-block;
        margin-top: 1rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .certificate-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
</style>

<div class="grades-container">
    <div class="grades-header">
        <h1>My Grades & Progress</h1>
        <p>An overview of your performance in all enrolled courses.</p>
    </div>

    <?php if (empty($grades_details)): ?>
        <div class="course-grade-card">
            <div class="empty-state">
                <i class="fas fa-book-open" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <p>You are not enrolled in any courses yet.</p>
                <a href="?page=browse-courses" class="certificate-link" style="background: var(--primary-color);">Explore Courses</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($grades_details as $detail): ?>
            <?php
                $progress_percentage = 0;
                if ($detail['total_lessons'] > 0) {
                    $progress_percentage = round(($detail['completed_lessons'] / $detail['total_lessons']) * 100);
                }
            ?>
            <div class="course-grade-card">
                <div class="card-header">
                    <h2 class="card-title"><?= htmlspecialchars($detail['course_title']) ?></h2>
                    <div class="progress-container">
                        <div class="progress-text">
                            <span>Overall Progress</span>
                            <span><?= $progress_percentage ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-bar-inner" style="width: <?= $progress_percentage ?>%;"></div>
                        </div>
                        <div class="progress-text" style="margin-top: 0.25rem;">
                            <span><?= $detail['completed_lessons'] ?> of <?= $detail['total_lessons'] ?> lessons completed</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h4>Quiz Results</h4>
                    <?php if (empty($detail['quizzes'])): ?>
                        <p class="score-not-taken">No quizzes in this course.</p>
                    <?php else: ?>
                        <table class="quiz-table">
                            <thead>
                                <tr>
                                    <th>Quiz Title</th>
                                    <th>Your Best Score</th>
                                    <th>Total Marks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail['quizzes'] as $quiz): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($quiz['quiz_title']) ?></td>
                                        <td>
                                            <?php if ($quiz['best_score'] !== null): ?>
                                                <span class="score"><?= htmlspecialchars($quiz['best_score']) ?></span>
                                            <?php else: ?>
                                                <span class="score-not-taken">Not taken yet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($quiz['total_marks'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if ($progress_percentage >= 100): ?>
                        <a href="?page=certificate&course_id=<?= $detail['course_id'] ?>" class="certificate-link" target="_blank">
                            <i class="fas fa-award"></i> View Certificate
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>