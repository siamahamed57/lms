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
// Fetch all courses the student has completed (progress >= 100)
// and check if a certificate has been issued.
$completed_courses_sql = "
    SELECT
        c.id AS course_id,
        c.title,
        c.thumbnail,
        cert.issued_at
    FROM
        enrollments e
    JOIN
        courses c ON e.course_id = c.id
    LEFT JOIN
        certificates cert ON cert.student_id = e.student_id AND cert.course_id = e.course_id
    WHERE
        e.student_id = ? AND e.progress >= 100
    ORDER BY
        c.title ASC
";
$completed_courses = db_select($completed_courses_sql, "i", [$student_id]);
?>

<style>
    .certificates-container { padding: 2rem; }
    .certificates-header { margin-bottom: 2rem; }
    .certificates-header h1 { font-size: 2.25rem; font-weight: 600; }
    .certificates-header p { color: var(--text-secondary); }

    .certificate-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .certificate-card {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .certificate-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        border-color: #84cc16; /* Certificate green */
    }
    .card-thumbnail {
        height: 180px;
        width: 100%;
        object-fit: cover;
        border-bottom: 1px solid var(--glass-border);
    }
    .card-content {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        flex-grow: 1;
    }
    .card-issued-date {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-bottom: 1rem;
    }
    .btn-view-cert {
        display: block;
        width: 100%;
        text-align: center;
        padding: 0.75rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        background: linear-gradient(135deg, #84cc16, #65a30d);
        color: white;
        transition: all 0.3s ease;
        margin-top: auto;
    }
    .btn-view-cert:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(132, 204, 22, 0.3);
    }
    .empty-state {
        text-align: center;
        color: var(--text-secondary);
        padding: 3rem;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
    }
</style>

<div class="certificates-container">
    <div class="certificates-header">
        <h1>My Certificates</h1>
        <p>A collection of all the certificates you have earned.</p>
    </div>

    <?php if (empty($completed_courses)): ?>
        <div class="empty-state">
            <i class="fas fa-award" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>You haven't earned any certificates yet.</p>
            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Complete a course to earn your first certificate!</p>
        </div>
    <?php else: ?>
        <div class="certificate-grid">
            <?php foreach ($completed_courses as $course): ?>
                <div class="certificate-card">
                    <img src="<?= htmlspecialchars($course['thumbnail'] ?? 'assets/images/default_course.jpg') ?>" 
                         alt="Thumbnail for <?= htmlspecialchars($course['title']) ?>" 
                         class="card-thumbnail"
                         onerror="this.onerror=null;this.src='assets/images/default_course.jpg';">
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="card-issued-date">
                            <?php if ($course['issued_at']): ?>
                                Issued on: <?= date('F j, Y', strtotime($course['issued_at'])) ?>
                            <?php else: ?>
                                Completed
                            <?php endif; ?>
                        </p>
                        <a href="dashboard?page=certificate&course_id=<?= $course['course_id'] ?>" class="btn-view-cert" target="_blank">
                            <i class="fas fa-eye"></i> View Certificate
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>