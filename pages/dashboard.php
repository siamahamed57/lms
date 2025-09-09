<?php
// --- AJAX Request Handler ---
// If 'ajax' param is set, only render the requested section and exit.
if (isset($_GET['ajax'])) {
    // Need to set up session and role check again for direct AJAX calls
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        echo "<h2>Session expired. Please log in again.</h2>";
        exit;
    }
    
    $section = $_GET['page'] ?? 'overview';
    $page_map = [
        'overview' => __DIR__ . "/../api/templates/overview.php",
        'create-course' => __DIR__ . "/../api/courses/create.php",
        'manage' => __DIR__ . "/../api/courses/manage.php",
        'create-lesson' => __DIR__ . "/../api/lessons/create.php",
        'manage-lessons' => __DIR__ . "/../api/lessons/manage.php",
        'create-quiz' => __DIR__ . "/../api/quizzes/create.php",
        'manage-quizzes' => __DIR__ . "/../api/quizzes/manage.php",
        'manage-coupons' => __DIR__ . "/../api/coupons/manage.php",
        'profile' => __DIR__ . "/../student/profile.php",
        'quiz' => __DIR__ . "/../student/quiz.php",
        'submit_quiz' => __DIR__ . "/../api/quizzes/submit_quiz.php",
        'my-courses' => __DIR__ . "/../student/my_courses.php",
        'lesson' => __DIR__ . "/../student/lesson.php",
        'certificate' => __DIR__ . "/../student/certificate.php",
        'enrollment-management' => __DIR__ . "/../student/enrollment-management.php",
        'users' => __DIR__ . "/../api/users/user-management.php",
    ];
    $page_path = $page_map[$section] ?? '';
    if ($page_path && file_exists($page_path)) { include $page_path; } 
    else { echo "<h2>Content not found.</h2>"; }
    exit; // IMPORTANT: Stop execution after sending the content fragment.
}

// --- Pre-render Logic ---
// Handle form submissions for included pages before any HTML is output.
$section = $_GET['page'] ?? 'overview';

// Handle lesson completion before any HTML is output
if ($section === 'lesson' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/lesson-logic.php';
}

// Handle quiz submission before any HTML is output
if ($section === 'submit_quiz' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../api/quizzes/submit_quiz.php';
}

// Handle enrollment management form submissions
if ($section === 'enrollment-management' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../student/enrollment-logic.php';
}

// Handle lesson management form submissions
if ($section === 'manage-lessons' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../api/lessons/manage-logic.php';
}
if ($section === 'manage' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // This file contains logic for update/delete and will redirect if successful.
    include __DIR__ . '/../api/courses/manage-logic.php';
}
if ($section === 'manage-coupons' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // This file contains logic for coupon management and will redirect.
    include __DIR__ . '/../api/coupons/manage-logic.php';
}
if ($section === 'profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // This file contains logic for profile updates and will redirect.
    include __DIR__ . '/../student/profile-logic.php';
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location:account");
    exit;
}
$user_id = $_SESSION['user_id'];

// Role
$userRole = $_SESSION['user_role'] ?? 'student';

// --- Profile Completion Check for Students ---
$is_profile_complete = true; // Assume complete by default
if ($userRole === 'student') {
    $user_profile_data = db_select("SELECT phone, university, department FROM users WHERE id = ?", "i", [$user_id]);
    $profile = $user_profile_data[0] ?? null;
    if (!$profile || empty($profile['phone']) || empty($profile['university']) || empty($profile['department'])) {
        $is_profile_complete = false;
    }

    // If profile is incomplete and user is trying to access a page other than the profile page or logout
    if (!$is_profile_complete && $section !== 'profile' && $section !== 'logout') {
    // Display a modern dark blocking modal and stop further page rendering
    echo <<<HTML
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        .profile-enforce-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: linear-gradient(135deg, #030303ff 0%, #000000ff 100%);
            backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            animation: fadeIn 0.4s ease-out;
        }
        
        .profile-enforce-modal {
            background: linear-gradient(145deg, #1a1a2e2a 0%, #18191aff 100%);
            backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem 2.5rem;
            border-radius: 24px;
            text-align: center;
            max-width: 480px;
            width: 90%;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.8),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .profile-enforce-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        }
        
        .profile-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #b615ff, #7c3aed);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 32px rgba(182, 21, 255, 0.3);
        }
        
        .profile-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }
        
        .profile-enforce-modal h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #ffffff;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }
        
        .profile-enforce-modal p {
            margin-bottom: 2.5rem;
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
            line-height: 1.6;
            font-weight: 400;
        }
        
        .profile-enforce-modal a {
            background: linear-gradient(135deg, #b615ff, #7c3aed);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 4px 14px 0 rgba(182, 21, 255, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .profile-enforce-modal a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .profile-enforce-modal a:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 8px 28px 0 rgba(182, 21, 255, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
        
        .profile-enforce-modal a:hover::before {
            opacity: 1;
        }
        
        .profile-enforce-modal a:active {
            transform: translateY(0);
        }
        
        .pulse-dot {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 8px;
            height: 8px;
            background: linear-gradient(45deg, #10b981, #059669);
            border-radius: 50%;
            animation: pulse 3s infinite;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }
        
        .pulse-dot::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(16, 185, 129, 0.3);
            border-radius: 50%;
            animation: pulse 3s infinite reverse;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes dotGrid {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 30px); }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(40px) scale(0.9);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 1;
            }
            50% { 
                transform: scale(1.1);
                opacity: 0.8;
            }
        }
        
        @media (max-width: 640px) {
            .profile-enforce-modal {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .profile-enforce-modal h2 {
                font-size: 1.5rem;
            }
            
            .profile-enforce-modal a {
                padding: 0.875rem 2rem;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <div class="profile-enforce-overlay">
        <div class="profile-enforce-modal">
            <div class="pulse-dot"></div>
            <div class="profile-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h2>Complete Your Profile</h2>
            <p>Unlock full access to your personalized dashboard and premium course content by completing your profile.</p>
            <a href="?page=profile">
                Complete Profile
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
HTML;
    exit(); // Stop the script to prevent the requested page from loading
}
}

// Menus
$adminMenu = [
    'overview' => ['icon' => 'fas fa-chart-pie', 'text' => 'Overview', 'gradient' => 'linear-gradient(135deg, #b915ff, #8b5cf6)'],

    'users' => ['icon' => 'fas fa-users-cog', 'text' => 'User Management', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],
    'students' => ['icon' => 'fas fa-user-graduate', 'text' => 'Students', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)'],
    'instructors' => ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Instructors', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'enrollment-management' => ['icon' => 'fas fa-user-plus', 'text' => 'Enroll Students', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'communication' => ['icon' => 'fas fa-comments', 'text' => 'Communication', 'gradient' => 'linear-gradient(135deg, #f97316, #ea580c)'],

    'create-course' => ['icon' => 'fas fa-plus-circle', 'text' => 'Create Course', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'manage' => ['icon' => 'fas fa-book-open', 'text' => 'Course Management', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'create-lesson' => ['icon' => 'fas fa-file-alt', 'text' => 'Create Lesson', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'manage-lessons' => ['icon' => 'fas fa-tasks', 'text' => 'Manage Lessons', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'create-quiz' => ['icon' => 'fas fa-question-circle', 'text' => 'Create Quiz', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'manage-quizzes' => ['icon' => 'fas fa-tasks', 'text' => 'Manage Quizzes', 'gradient' => 'linear-gradient(135deg, #f97316, #ea580c)'],
    'content' => ['icon' => 'fas fa-file-video', 'text' => 'Content', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'],
    'manage-coupons' => ['icon' => 'fas fa-tags', 'text' => 'Create Coupons', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],

    'reports' => ['icon' => 'fas fa-chart-bar', 'text' => 'Analytics', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],

    'settings' => ['icon' => 'fas fa-cogs', 'text' => 'Settings', 'gradient' => 'linear-gradient(135deg, #64748b, #475569)'],
    'logout' => ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'gradient' => 'linear-gradient(135deg, #ef4444, #b91c1c)']
];

$instructorMenu = [
    'overview' => ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'gradient' => 'linear-gradient(135deg, #b915ff, #8b5cf6)'],
    'create-lesson' => ['icon' => 'fas fa-file-alt', 'text' => 'Create Lesson', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'manage-lessons' => ['icon' => 'fas fa-tasks', 'text' => 'Manage Lessons', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'create-quiz' => ['icon' => 'fas fa-plus-circle', 'text' => 'Create Quiz', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'my-courses' => ['icon' => 'fas fa-book-open', 'text' => 'Manage Courses', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'students' => ['icon' => 'fas fa-users', 'text' => 'Students', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],
    'assessments' => ['icon' => 'fas fa-tasks', 'text' => 'Assessments', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'analytics' => ['icon' => 'fas fa-chart-line', 'text' => 'Analytics', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'communication' => ['icon' => 'fas fa-comments', 'text' => 'Messages', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)'],
    'payouts' => ['icon' => 'fas fa-wallet', 'text' => 'Earnings', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'profile' => ['icon' => 'fas fa-user-edit', 'text' => 'Profile', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'],
    'logout' => ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'gradient' => 'linear-gradient(135deg, #ef4444, #b91c1c)']
];

$studentMenu = [
    'overview' => ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'gradient' => 'linear-gradient(135deg, #b915ff, #8b5cf6)'],
    'my-courses' => ['icon' => 'fas fa-book-reader', 'text' => 'My Courses', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'browse-courses' => ['icon' => 'fas fa-search', 'text' => 'Explore', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],
    'grades' => ['icon' => 'fas fa-graduation-cap', 'text' => 'Grades', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'certificates' => ['icon' => 'fas fa-award', 'text' => 'Certificates', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'profile' => ['icon' => 'fas fa-user-cog', 'text' => 'Profile', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'],
    'support' => ['icon' => 'fas fa-headset', 'text' => 'Support', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)'],
    'logout' => ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'gradient' => 'linear-gradient(135deg, #ef4444, #b91c1c)']
];

// If student profile is not complete, show only essential menu items
if ($userRole === 'student' && !$is_profile_complete) {
    $studentMenu = [
        'profile' => $studentMenu['profile'], // Keep the original profile item
        'logout' => $studentMenu['logout']   // Keep the original logout item
    ];
}

// Active menu based on role
$activeMenu = ($userRole === 'admin') ? $adminMenu : (($userRole === 'instructor') ? $instructorMenu : $studentMenu);

// Function to generate sidebar links
function generateNavLinks($menu, $section) {
    $html = '';
    foreach ($menu as $page => $details) {
        $isActive = ($page === $section);
        $href = ($page === 'logout') ? 'api/auth/logout.php' : '?page=' . $page;
        $html .= '<li class="nav-item">
                    <a href="' . $href . '" 
                       data-section="' . $page . '" 
                       class="nav-link ' . ($isActive ? 'active' : '') . '"
                       style="--gradient: ' . $details['gradient'] . '">
                        <div class="nav-icon-wrapper">
                            <i class="' . $details['icon'] . ' nav-icon"></i>
                        </div>
                        <span class="nav-text">' . $details['text'] . '</span>
                        <div class="nav-ripple"></div>
                    </a>
                  </li>';
    }
    return $html;
}

// Function to generate mobile bottom navigation
function generateMobileNav($menu, $section) {
    $html = '';
    $count = 0;
    foreach ($menu as $page => $details) {
        if ($count >= 5) break; // Limit to 5 items for bottom nav
        $isActive = ($page === $section);
        $href = ($page === 'logout') ? 'api/auth/logout.php' : '?page=' . $page;
        $html .= '<a href="' . $href . '" 
                     data-section="' . $page . '" 
                     class="mobile-nav-item ' . ($isActive ? 'active' : '') . '"
                     style="--gradient: ' . $details['gradient'] . '">
                    <div class="mobile-nav-icon">
                        <i class="' . $details['icon'] . '"></i>
                        <div class="mobile-nav-indicator"></div>
                    </div>
                    <span class="mobile-nav-text">' . $details['text'] . '</span>
                  </a>';
        $count++;
    }
    return $html;
}

// Get user info for display
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$userAvatar = $_SESSION['user_avatar'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNIES - Premium Learning Experience</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        /* Style for page transitions */
        body.is-transitioning {
            opacity: 0 !important;
            transform: translateY(10px);
            transition: opacity 0.2s ease-out, transform 0.2s ease-out;
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles" id="particles"></div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <div class="mobile-nav-container">
            <?= generateMobileNav($activeMenu, $section) ?>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" id="fab">
        <i class="fas fa-plus"></i>
    </button>

    <div class="dashboard-wrapper">
        <!-- Premium Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="home" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Unies</span>
                </a>
            </div>

            <div class="user-profile">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if ($userAvatar): ?>
                            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 16px;">
                        <?php else: ?>
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        <?php endif; ?>
                        <div class="notification-badge">3</div>
                    </div>
                    <div class="user-details">
                        <h3><?= htmlspecialchars($userName) ?></h3>
                        <p><?= htmlspecialchars($userRole) ?></p>
                    </div>
                </div>
            </div>

            <!-- <div class="time-display">
                <div class="current-time" id="currentTime"></div>
                <div class="current-date" id="currentDate"></div>
            </div> -->

            <nav>
                <ul class="nav-list">
                    <?= generateNavLinks($activeMenu, $section) ?>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <div class="content-body" style="opacity: 1; transform: translateY(0px); transition: opacity 0.3s ease-out, transform 0.3s ease-out;">
                <?php
                // This code only runs on a full page load. AJAX calls are handled at the top.
                $page_map = [
                    'overview' => __DIR__ . "/../api/templates/overview.php",
                    'create-course' => __DIR__ . "/../api/courses/create.php",
                    'manage' => __DIR__ . "/../api/courses/manage.php",
                    'create-lesson' => __DIR__ . "/../api/lessons/create.php",
                    'manage-lessons' => __DIR__ . "/../api/lessons/manage.php",
                    'create-quiz' => __DIR__ . "/../api/quizzes/create.php",
                    'manage-quizzes' => __DIR__ . "/../api/quizzes/manage.php",
                    'manage-coupons' => __DIR__ . "/../api/coupons/manage.php",
                    'quiz' => __DIR__ . "/../student/quiz.php",
                    'submit_quiz' => __DIR__ . "/../api/quizzes/submit_quiz.php",
                    'my-courses' => __DIR__ . "/../student/my_courses.php",
                    'profile' => __DIR__ . "/../student/profile.php",
                    'lesson' => __DIR__ . "/../student/lesson.php",
                    'certificate' => __DIR__ . "/../student/certificate.php",
                    'enrollment-management' => __DIR__ . "/../student/enrollment-management.php",
                    'users' => __DIR__ . "/../api/users/user-management.php",
                ];
                $page_path = $page_map[$section] ?? '';

                if ($page_path && file_exists($page_path)) {
                    include $page_path;
                } else {
                    // Default/fallback content for overview or not found pages
                    echo '<section id="overview" class="dashboard-section active">';
                    echo '<div class="stats-grid">
                            <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-value">12,847</div><div class="stat-label">Total Students</div></div>
                            <div class="stat-card"><div class="stat-icon"><i class="fas fa-book-open"></i></div><div class="stat-value">256</div><div class="stat-label">Active Courses</div></div>
                            <div class="stat-card"><div class="stat-icon"><i class="fas fa-award"></i></div><div class="stat-value">8,942</div><div class="stat-label">Certificates</div></div>
                            <div class="stat-card"><div class="stat-icon"><i class="fas fa-chart-line"></i></div><div class="stat-value">97.8%</div><div class="stat-label">Success Rate</div></div>
                        </div>';
                    if (file_exists(__DIR__ . "/../api/templates/overview.php")) {
                        include __DIR__ . "/../api/templates/overview.php"; 
                    }
                    echo '</section>';
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        // Create animated background particles
        function createParticles() {
            const particles = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particles.appendChild(particle);
            }
        }

        // Time Display with smooth updates
        function updateTime() {
            const now = new Date();
            const timeOptions = { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const dateOptions = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            };
            
            const timeElement = document.getElementById('currentTime');
            const dateElement = document.getElementById('currentDate');
            
            const newTime = now.toLocaleTimeString('en-US', timeOptions);
            const newDate = now.toLocaleDateString('en-US', dateOptions);
            
            if (timeElement.textContent !== newTime) {
                timeElement.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    timeElement.textContent = newTime;
                    timeElement.style.transform = 'scale(1)';
                }, 150);
            }
            
            if (dateElement.textContent !== newDate) {
                dateElement.textContent = newDate;
            }
        }

        // Ripple effect for navigation links
        function createRipple(event) {
            const button = event.currentTarget;
            const ripple = button.querySelector('.nav-ripple');
            
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.transform = 'scale(0)';
            ripple.style.opacity = '1';
            
            ripple.animate([
                { transform: 'scale(0)', opacity: 1 },
                { transform: 'scale(4)', opacity: 0 }
            ], {
                duration: 600,
                easing: 'ease-out'
            });
        }

        function initNavigation() {
            const contentBody = document.querySelector('.content-body');
            const contentTitle = document.getElementById('contentTitle'); // Assuming you have this element
            const activeMenu = <?= json_encode($activeMenu) ?>;

            const loadContent = (url, pushState = true) => {
                const mainContent = document.querySelector('.main-content');
                mainContent.classList.add('is-transitioning');

                fetch(url + (url.includes('?') ? '&' : '?') + 'ajax=true')
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(html => {
                        setTimeout(() => {
                            contentBody.innerHTML = html;

                            if (pushState) {
                                history.pushState({ path: url }, '', url);
                            }

                            // Update page title
                            const urlParams = new URL(url, window.location.origin);
                            const page = urlParams.searchParams.get('page') || 'overview';
                            const menuItem = activeMenu[page];
                            if (menuItem && contentTitle) {
                                contentTitle.textContent = menuItem.text;
                                contentTitle.setAttribute('data-text', menuItem.text);
                            }

                            // Re-initialize any scripts within the new content
                            const scripts = contentBody.querySelectorAll('script');
                            scripts.forEach(oldScript => {
                                const newScript = document.createElement('script');
                                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                            });

                            mainContent.classList.remove('is-transitioning');
                        }, 200);
                    })
                    .catch(error => {
                        console.error('Failed to load page content:', error);
                        contentBody.innerHTML = `<div class='text-center p-8'><h2 class='text-2xl font-bold text-red-600'>❌ Error loading content.</h2><p class='text-gray-400'>Please try again or refresh the page.</p></div>`;
                        mainContent.classList.remove('is-transitioning');
                    });
            };

            // Use event delegation for all internal links
            document.body.addEventListener('click', function(e) {
                const link = e.target.closest('a');

                // Ignore if not a link or if it's an external/logout/anchor link
                if (!link) return;
                const href = link.getAttribute('href');
                if (!href || !href.startsWith('?page=')) {
                    return;
                }

                e.preventDefault();
                const targetUrl = link.href;

                if (window.location.href === targetUrl) {
                    return;
                }

                // Update active link style for main navigation
                if (link.matches('.nav-link, .mobile-nav-item')) {
                    document.querySelectorAll(".nav-link, .mobile-nav-item").forEach(l => l.classList.remove('active'));
                    const section = link.dataset.section;
                    document.querySelector(`.nav-link[data-section="${section}"]`)?.classList.add('active');
                    document.querySelector(`.mobile-nav-item[data-section="${section}"]`)?.classList.add('active');
                }

                loadContent(targetUrl);
            });

            window.addEventListener("popstate", function(e) {
                const path = e.state ? e.state.path : window.location.href;
                loadContent(path, false);

                // Update active link on popstate
                const urlParams = new URL(path, window.location.origin);
                const page = urlParams.searchParams.get('page') || 'overview';
                const allNavLinks = document.querySelectorAll(".nav-link, .mobile-nav-item");
                allNavLinks.forEach(l => l.classList.remove('active'));
                document.querySelector(`.nav-link[data-section="${page}"]`)?.classList.add('active');
                document.querySelector(`.mobile-nav-item[data-section="${page}"]`)?.classList.add('active');
            });
        }

        // Smooth scrolling and enhanced UX
        function initEnhancements() {
            // Add hover sound effect simulation
            const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-item');
            navLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.filter = 'brightness(1.1)';
                });
                
                link.addEventListener('mouseleave', function() {
                    this.style.filter = 'brightness(1)';
                });
            });

            // Add parallax effect to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            
            function handleMouseMove(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(20px)`;
            }
            
            function handleMouseLeave() {
                this.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
            }
            
            statCards.forEach(card => {
                card.addEventListener('mousemove', handleMouseMove);
                card.addEventListener('mouseleave', handleMouseLeave);
            });
        }

        // Handle back/forward buttons
        window.addEventListener("popstate", function(e) {
            // This is now handled by the new initNavigation logic
        });

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            initNavigation();
            initEnhancements();
            
            // Start time updates
            // updateTime(); // You can re-enable this if the function exists
            // setInterval(updateTime, 500);
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            // Recreate particles on resize for better distribution
            const particles = document.getElementById('particles');
            if (particles) {
                particles.innerHTML = '';
                createParticles();
            }
        });

        // Performance optimization
        let ticking = false;
        
        function optimizedAnimationFrame(callback) {
            if (!ticking) {
                requestAnimationFrame(callback);
                ticking = true;
            }
        }
        
        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>