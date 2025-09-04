<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['user_role'] ?? 'student'; // Default to student if not set
$current_page = basename($_GET['page'] ?? 'overview');
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="logo-link">
            <span class="logo-text">UNIES LMS</span>
        </a>
    </div>
    <nav class="sidebar-nav">
        <?php if ($user_role === 'admin'): ?>
            <span class="nav-section-title">Admin Menu</span>
            <ul>
                <li><a href="dashboard.php?page=overview" class="<?= $current_page == 'overview' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Overview</a></li>
                <li><a href="dashboard.php?page=users" class="<?= $current_page == 'users' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li><a href="dashboard.php?page=courses" class="<?= $current_page == 'courses' ? 'active' : '' ?>"><i class="fas fa-book"></i> Course Management</a></li>
                <li><a href="dashboard.php?page=instructors" class="<?= $current_page == 'instructors' ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher"></i> Instructor Management</a></li>
                <li><a href="dashboard.php?page=students" class="<?= $current_page == 'students' ? 'active' : '' ?>"><i class="fas fa-user-graduate"></i> Student Management</a></li>
                <li><a href="dashboard.php?page=content" class="<?= $current_page == 'content' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Content & Assessments</a></li>
                <li><a href="dashboard.php?page=reports" class="<?= $current_page == 'reports' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> Reporting & Analytics</a></li>
                <li><a href="dashboard.php?page=communication" class="<?= $current_page == 'communication' ? 'active' : '' ?>"><i class="fas fa-bullhorn"></i> Communication</a></li>
                <li><a href="dashboard.php?page=settings" class="<?= $current_page == 'settings' ? 'active' : '' ?>"><i class="fas fa-cogs"></i> System Settings</a></li>
            </ul>
        <?php elseif ($user_role === 'instructor'): ?>
            <span class="nav-section-title">Instructor Menu</span>
            <ul>
                <li><a href="dashboard.php?page=overview" class="<?= $current_page == 'overview' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="dashboard.php?page=my-courses" class="<?= $current_page == 'my-courses' ? 'active' : '' ?>"><i class="fas fa-book-open"></i> My Courses</a></li>
                <li><a href="dashboard.php?page=students" class="<?= $current_page == 'students' ? 'active' : '' ?>"><i class="fas fa-users"></i> Student Management</a></li>
                <li><a href="dashboard.php?page=assessments" class="<?= $current_page == 'assessments' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Assessments</a></li>
                <li><a href="dashboard.php?page=analytics" class="<?= $current_page == 'analytics' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="dashboard.php?page=communication" class="<?= $current_page == 'communication' ? 'active' : '' ?>"><i class="fas fa-comments"></i> Communication</a></li>
                <li><a href="dashboard.php?page=payouts" class="<?= $current_page == 'payouts' ? 'active' : '' ?>"><i class="fas fa-dollar-sign"></i> Monetization</a></li>
                <li><a href="dashboard.php?page=profile" class="<?= $current_page == 'profile' ? 'active' : '' ?>"><i class="fas fa-user-edit"></i> Profile & Settings</a></li>
            </ul>
        <?php else: // Student is the default ?>
            <span class="nav-section-title">Student Menu</span>
            <ul>
                <li><a href="dashboard.php?page=overview" class="<?= $current_page == 'overview' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="dashboard.php?page=my-courses" class="<?= $current_page == 'my-courses' ? 'active' : '' ?>"><i class="fas fa-book-reader"></i> My Courses</a></li>
                <li><a href="courses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : '' ?>"><i class="fas fa-search"></i> Browse Courses</a></li>
                <li><a href="dashboard.php?page=grades" class="<?= $current_page == 'grades' ? 'active' : '' ?>"><i class="fas fa-graduation-cap"></i> Grades & Feedback</a></li>
                <li><a href="dashboard.php?page=certificates" class="<?= $current_page == 'certificates' ? 'active' : '' ?>"><i class="fas fa-certificate"></i> My Certificates</a></li>
                <li><a href="dashboard.php?page=profile" class="<?= $current_page == 'profile' ? 'active' : '' ?>"><i class="fas fa-user-cog"></i> Account & Profile</a></li>
                <li><a href="dashboard.php?page=support" class="<?= $current_page == 'support' ? 'active' : '' ?>"><i class="fas fa-question-circle"></i> Support</a></li>
            </ul>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="../actions/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>