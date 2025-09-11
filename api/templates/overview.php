<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';

$userRole = $_SESSION['user_role'] ?? 'student';
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'User';

function get_greeting() {
    $hour = date('G');
    if ($hour < 12) return "Good Morning";
    if ($hour < 17) return "Good Afternoon";
    return "Good Evening";
}

?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
}
.stat-card {
    background: rgba(255, 255, 255, 0.07);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}
.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.stat-label {
    font-size: 0.9rem;
    color: #a0a0a0;
    margin-bottom: 0.5rem;
}
.stat-icon {
    font-size: 1.5rem;
    color: #fff;
    opacity: 0.5;
}
.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.1;
}
.stat-card a {
    margin-top: 1rem;
    font-size: 0.85rem;
    color: #b915ff;
    font-weight: 500;
    text-decoration: none;
    transition: color 0.2s;
}
.stat-card a:hover {
    color: #fff;
}
.welcome-header {
    margin-bottom: 2rem;
}
.welcome-header h2 {
    font-size: 2rem;
    font-weight: 600;
}
.welcome-header p {
    color: #a0a0a0;
}
.quick-access-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}
.quick-access-card {
    background: rgba(255, 255, 255, 0.07);
    border-radius: 16px;
    padding: 1.5rem;
}
.quick-access-card h4 {
    font-weight: 500;
    margin-bottom: 1rem;
}
.quick-access-card ul {
    list-style: none;
    padding: 0;
}
.quick-access-card li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.quick-access-card li:last-child {
    border-bottom: none;
}
.course-title {
    color: #f0f0f0;
}
.course-progress {
    font-size: 0.8rem;
    color: #a0a0a0;
}
.progress-bar {
    width: 100%;
    height: 6px;
    background-color: rgba(0,0,0,0.3);
    border-radius: 3px;
    margin-top: 4px;
    overflow: hidden;
}
.progress-bar-inner {
    height: 100%;
    background: linear-gradient(90deg, #b915ff, #8b5cf6);
    border-radius: 3px;
}
</style>

<div class="welcome-header">
    <h2><?= get_greeting() ?>, <?= htmlspecialchars($userName) ?>!</h2>
    <p>Here's what's happening on your dashboard today.</p>
</div>

<?php
// =================================================================
// ADMIN OVERVIEW
// =================================================================
if ($userRole === 'admin') {
    // The new, detailed admin overview is in a separate file for better organization.
    include __DIR__ . '/../../admin/overview.php';
?>

<?php
// =================================================================
// INSTRUCTOR OVERVIEW
// =================================================================
} elseif ($userRole === 'instructor') {
    $my_courses_count = db_select("SELECT COUNT(id) as count FROM courses WHERE instructor_id = ?", 'i', [$userId])[0]['count'] ?? 0;
    $my_students_count = db_select("SELECT COUNT(DISTINCT e.student_id) as count FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = ?", 'i', [$userId])[0]['count'] ?? 0;
    $total_earnings_data = db_select("SELECT SUM(earned_amount) as total FROM instructor_earnings WHERE instructor_id = ?", 'i', [$userId]);
    $total_earnings = $total_earnings_data[0]['total'] ?? 0;
    $wallet_balance_data = db_select("SELECT balance FROM instructor_wallets WHERE instructor_id = ?", 'i', [$userId]);
    $wallet_balance = $wallet_balance_data[0]['balance'] ?? 0;
?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header"><div class="stat-label">Total Earnings</div><i class="fas fa-dollar-sign stat-icon"></i></div>
            <div class="stat-value">$<?= number_format($total_earnings, 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header"><div class="stat-label">Wallet Balance</div><i class="fas fa-wallet stat-icon"></i></div>
            <div class="stat-value">$<?= number_format($wallet_balance, 2) ?></div>
            <a href="dashboard?page=payouts">Request Payout &rarr;</a>
        </div>
        <div class="stat-card">
            <div class="stat-header"><div class="stat-label">Your Courses</div><i class="fas fa-book stat-icon"></i></div>
            <div class="stat-value"><?= $my_courses_count ?></div>
            <a href="dashboard?page=my-courses">Manage Courses &rarr;</a>
        </div>
        <div class="stat-card">
            <div class="stat-header"><div class="stat-label">Total Students</div><i class="fas fa-users stat-icon"></i></div>
            <div class="stat-value"><?= $my_students_count ?></div>
        </div>
    </div>

<?php
// =================================================================
// STUDENT OVERVIEW
// =================================================================
} else {
    $enrolled_courses = db_select("SELECT COUNT(id) as count FROM enrollments WHERE student_id = ?", 'i', [$userId])[0]['count'] ?? 0;
    $completed_courses = db_select("SELECT COUNT(id) as count FROM enrollments WHERE student_id = ? AND progress >= 100", 'i', [$userId])[0]['count'] ?? 0;
    $certificates_earned = db_select("SELECT COUNT(id) as count FROM certificates WHERE student_id = ?", 'i', [$userId])[0]['count'] ?? 0;
    $continue_learning = db_select("SELECT c.id, c.title, c.thumbnail, e.progress FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.student_id = ? AND e.progress < 100 ORDER BY e.enrolled_at DESC LIMIT 4", 'i', [$userId]);
?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header"><div class="stat-label">Enrolled Courses</div><i class="fas fa-book-reader stat-icon"></i></div>
            <div class="stat-value"><?= $enrolled_courses ?></div>
            <a href="dashboard?page=my-courses">View My Courses &rarr;</a>
        </div>
        <div class="stat-card">
            <div class="stat-header"><div class="stat-label">Completed Courses</div><i class="fas fa-check-circle stat-icon"></i></div>
            <div class="stat-value"><?= $completed_courses ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header"><div class="stat-label">Certificates Earned</div><i class="fas fa-award stat-icon"></i></div>
            <div class="stat-value"><?= $certificates_earned ?></div>
            <a href="dashboard?page=certificates">View Certificates &rarr;</a>
        </div>
    </div>

    <?php if (!empty($continue_learning)): ?>
    <div class="quick-access-grid">
        <div class="quick-access-card">
            <h4>Continue Learning</h4>
            <ul>
                <?php foreach($continue_learning as $course): ?>
                <li>
                    <div>
                        <a href="dashboard?page=lesson&course_id=<?= $course['id'] ?>" class="course-title"><?= htmlspecialchars($course['title']) ?></a>
                        <div class="course-progress">
                            <span><?= (int)$course['progress'] ?>% Complete</span>
                            <div class="progress-bar">
                                <div class="progress-bar-inner" style="width: <?= (int)$course['progress'] ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    <a href="dashboard?page=lesson&course_id=<?= $course['id'] ?>"><i class="fas fa-arrow-right"></i></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

<?php } ?>