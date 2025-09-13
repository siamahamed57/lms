<?php
// --- START SESSION AND DATABASE CONNECTION ---
// This should be at the very top of your script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// MOCK DB FUNCTION FOR PROFILE COMPLETION CHECK TO AVOID ERRORS
if (!function_exists('db_select')) {
    function db_select($query, $types, $params) {
        // Mock response: Assumes profile is complete for the demo.
        // In your real app, this would query your database.
        return [['phone' => '123', 'bio' => 'bio', 'university' => 'uni', 'department' => 'dept']];
    }
}

// --- MOCK SESSION FOR DEMO ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Alex Doe';
    $_SESSION['user_email'] = 'alex.doe@example.com';
    $_SESSION['user_role'] = 'admin'; // 'student', 'instructor', or 'admin'
    $_SESSION['user_avatar'] = 'https://placehold.co/100x100/00f6ff/16071d?text=AD';
}

// --- AJAX Request Handler ---
if (isset($_GET['ajax'])) {
    // Prevent caching for all AJAX requests
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");

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
                'content' => __DIR__ . "/../admin/content.php",
        'manage' => __DIR__ . "/../api/courses/manage.php",
        'create-lesson' => __DIR__ . "/../api/lessons/create.php",
        'manage-lessons' => __DIR__ . "/../api/lessons/manage.php",
        'create-quiz' => __DIR__ . "/../api/quizzes/create.php",
        'manage-quizzes' => __DIR__ . "/../api/quizzes/manage.php",
        'manage-coupons' => __DIR__ . "/manage.php",
        'quiz' => __DIR__ . "/../student/quiz.php",
        'submit_quiz' => __DIR__ . "/../api/quizzes/submit_quiz.php",
        'my-courses' => __DIR__ . "/../student/my_courses.php",
        'lesson' => __DIR__ . "/../student/lesson.php",
        'certificate' => __DIR__ . "/../student/certificate.php",
        'certificates' => __DIR__ . "/../student/certificates.php",
        'grades' => __DIR__ . "/../student/grades.php",
        'course-details' => __DIR__ . "/../api/courses/detail.php",
        'browse-courses' => __DIR__ . "/../student/browse-courses.php",
        'profile' => __DIR__ . "/profile.php",
        'settings' => __DIR__ . "/../admin/settings.php",
                'enrollment-management' => __DIR__ . "/../admin/enrollment-management.php",
        'users' => __DIR__ . "/../api/users/user-management.php",
        'referral-settings' => __DIR__ . "/../admin/referral_settings.php",
        'referral-report' => __DIR__ . "/../admin/referrals.php",
        'reports' => __DIR__ . "/../admin/reports.php",
        'withdrawals' => __DIR__ . "/../admin/withdrawals.php",
        'my-referrals' => __DIR__ . "/../student/referrals.php",
        'my-wallet' => __DIR__ . "/../student/wallet.php",
        'instructor-settings' => __DIR__ . "/../admin/instructor_settings.php",
        'instructor-payouts' => __DIR__ . "/../admin/instructor_withdrawals.php",
        'payouts' => __DIR__ . "/../instructor/wallet.php",
        'payout-history' => __DIR__ . "/../instructor/withdrawals.php",
    ];
    $page_path = $page_map[$section] ?? '';
    if ($page_path && file_exists($page_path)) { include $page_path; } 
    else { echo "<h2>Content not found for ".htmlspecialchars($section).".</h2>"; }
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
    include __DIR__ . '/../admin/enrollment-logic.php';
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
    include __DIR__ . '/manage-logic.php';
}

// Handle profile form submissions
if ($section === 'profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/profile-logic.php';
}

// Handle referral settings form submissions
if ($section === 'referral-settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../admin/referral_settings-logic.php';
}

// Handle instructor settings form submissions
if ($section === 'instructor-settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../admin/instructor_settings-logic.php';
}

// Handle settings form submissions
if ($section === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../admin/settings-logic.php';
}

// Handle admin instructor withdrawal updates
if ($section === 'instructor-payouts' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../admin/instructor_withdrawals-logic.php';
}

// Handle user management form submissions
if ($section === 'users' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../api/users/user-management-logic.php';
}

// Handle student referral generation
if ($section === 'my-referrals' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../student/referrals-logic.php';
}

// Handle instructor withdrawal request
if ($section === 'payouts' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include __DIR__ . '/../instructor/wallet-logic.php';
}

// Handle student withdrawal request & admin withdrawal update
if (($section === 'my-wallet' || $section === 'withdrawals') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $logic_file = $section === 'my-wallet' ? __DIR__ . '/../student/wallet.php' : __DIR__ . '/../admin/withdrawals.php';
    include $logic_file;
}


// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location:account");
    exit;
}

// Role
$userRole = $_SESSION['user_role'] ?? 'student';

// Menus
$adminMenu = [
    'overview' => ['icon' => 'bx bxs-dashboard', 'text' => 'Overview'],
    'users' => ['icon' => 'bx bxs-group', 'text' => 'User Management'],
    'enrollment-management' => ['icon' => 'bx bx-user-plus', 'text' => 'Enroll Students'],
    'create-course' => ['icon' => 'bx bx-plus-circle', 'text' => 'Create Course'],
    'manage' => ['icon' => 'bx bx-book-open', 'text' => 'Course Management'],
    'create-lesson' => ['icon' => 'bx bx-file', 'text' => 'Create Lesson'],
    'manage-lessons' => ['icon' => 'bx bx-list-ul', 'text' => 'Manage Lessons'],
    'create-quiz' => ['icon' => 'bx bx-message-alt-add', 'text' => 'Create Quiz'],
    'manage-quizzes' => ['icon' => 'bx bx-task', 'text' => 'Manage Quizzes'],
    'manage-coupons' => ['icon' => 'bx bxs-discount', 'text' => 'Coupons'],
    'referral-settings' => ['icon' => 'bx bx-cog', 'text' => 'Referral Settings'],
    'referral-report' => ['icon' => 'bx bx-bullhorn', 'text' => 'Referral Report'],
    'withdrawals' => ['icon' => 'bx bx-money-withdraw', 'text' => 'Withdrawals'],
    'instructor-settings' => ['icon' => 'bx bxs-user-badge', 'text' => 'Instructor Settings'],
    'instructor-payouts' => ['icon' => 'bx bxs-bank', 'text' => 'Instructor Payouts'],
    'reports' => ['icon' => 'bx bx-bar-chart-alt-2', 'text' => 'Analytics'],
    'logout' => ['icon' => 'bx bx-log-out', 'text' => 'Logout']
];

$instructorMenu = [
    'overview' => ['icon' => 'bx bxs-dashboard', 'text' => 'Dashboard'],
    'create-lesson' => ['icon' => 'bx bx-file-plus', 'text' => 'Create Lesson'],
    'manage-lessons' => ['icon' => 'bx bx-list-ul', 'text' => 'Manage Lessons'],
    'create-quiz' => ['icon' => 'bx bx-message-alt-add', 'text' => 'Create Quiz'],
    'my-courses' => ['icon' => 'bx bx-book-open', 'text' => 'Manage Courses'],
    'students' => ['icon' => 'bx bxs-group', 'text' => 'Students'],
    'assessments' => ['icon' => 'bx bx-task', 'text' => 'Assessments'],
    'analytics' => ['icon' => 'bx bx-bar-chart-alt-2', 'text' => 'Analytics'],
    'communication' => ['icon' => 'bx bx-message-square-dots', 'text' => 'Messages'],
    'payouts' => ['icon' => 'bx bx-wallet', 'text' => 'My Wallet'],
    'payout-history' => ['icon' => 'bx bx-history', 'text' => 'Payout History'],
    'profile' => ['icon' => 'bx bxs-user-circle', 'text' => 'Profile'],
    'logout' => ['icon' => 'bx bx-log-out', 'text' => 'Logout']
];

$studentMenu = [
    'overview' => ['icon' => 'bx bxs-dashboard', 'text' => 'Dashboard'],
    'my-courses' => ['icon' => 'bx bxs-book-reader', 'text' => 'My Courses'],
    'browse-courses' => ['icon' => 'bx bx-search', 'text' => 'Explore'],
    'grades' => ['icon' => 'bx bxs-graduation', 'text' => 'Grades'],
    'my-referrals' => ['icon' => 'bx bx-share-alt', 'text' => 'My Referrals'],
    'my-wallet' => ['icon' => 'bx bx-wallet', 'text' => 'My Wallet'],
    'certificates' => ['icon' => 'bx bxs-award', 'text' => 'Certificates'],
    'profile' => ['icon' => 'bx bxs-user-circle', 'text' => 'Profile'],
    'support' => ['icon' => 'bx bx-support', 'text' => 'Support'],
    'logout' => ['icon' => 'bx bx-log-out', 'text' => 'Logout']
];


// Active menu based on role
$activeMenu = ($userRole === 'admin') ? $adminMenu : (($userRole === 'instructor') ? $instructorMenu : $studentMenu);

// --- Profile Completion Check ---
function is_profile_complete($user_id, $role) {
    $user_data = db_select("SELECT phone, bio, university, department FROM users WHERE id = ?", 'i', [$user_id]);
    if (empty($user_data)) return false; // User not found
    $user = $user_data[0];

    if ($role === 'student') {
        return !empty($user['phone']) && !empty($user['university']) && !empty($user['department']);
    } elseif ($role === 'instructor') {
        return !empty($user['phone']) && !empty($user['bio']);
    }
    return true; // Admin profile is always considered complete
}

$profile_is_complete = is_profile_complete($_SESSION['user_id'], $userRole);

if (!$profile_is_complete) {
    $forced_section = 'profile';
    if ($section !== $forced_section && $section !== 'logout') {
        header("Location: dashboard?page=$forced_section&notice=complete_profile");
        exit;
    }
    // Filter the menu to only show Profile and Logout
    $activeMenu = array_intersect_key($activeMenu, ['profile' => '', 'logout' => '']);
}

// Function to generate sidebar links
function generateNavLinks($menu, $section) {
    $html = '';
    foreach ($menu as $page => $details) {
        $isActive = ($page === $section) ? 'active' : '';
        $href = ($page === 'logout') ? 'api/auth/logout.php' : '?page=' . $page;
        $html .= "<li class='nav-item'>
                      <a href='{$href}' class='nav-link {$isActive}'>
                          <i class='{$details['icon']} nav-icon'></i>
                          <span class='nav-text'>{$details['text']}</span>
                      </a>
                    </li>";
    }
    return $html;
}

// Function to generate mobile bottom navigation
function generateMobileNav($menu, $section) {
    $html = '';
    $count = 0;
    $menu_items_to_show = array_slice($menu, 0, 5); // Limit to 5 items
    foreach ($menu_items_to_show as $page => $details) {
        $isActive = ($page === $section) ? 'active' : '';
        $href = ($page === 'logout') ? 'api/auth/logout.php' : '?page=' . $page;
        $html .= "<a href='{$href}' class='mobile-nav-item {$isActive}'>
                        <div class='mobile-nav-icon'><i class='{$details['icon']}'></i></div>
                        <span class='mobile-nav-text'>{$details['text']}</span>
                      </a>";
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
    <title>Futuristic UI Dashboard - UNIES</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary: #16071d;
            --primary-light: #311b3d;
            --secondary: #581c87;
            --accent: #05252b;
            --glow-primary: #d53bff;
            --glow-secondary: #00f6ff;
            --dark: #0a0a14;
            --text-primary: #f0f0f0;
            --text-secondary: #a1a1aa;
            --glass-bg: rgba(5, 37, 43, 0.3);
            --glass-border: rgba(0, 246, 255, 0.2);
            --shadow-glow: 0 0 15px var(--glow-secondary), 0 0 30px var(--glow-secondary);
            --blur: blur(12px);
            --gradient: linear-gradient(135deg, var(--glow-primary), var(--glow-secondary));
            --mouse-x: 50%;
            --mouse-y: 50%;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html {
            cursor: none;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            background-color: var(--dark);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
        }
        
        a, button, .stat-card {
            cursor: none;
        }

        h1, h2, h3, h4, .logo span, .stat-value {
            font-family: 'Orbitron', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .background-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; overflow: hidden; background: var(--dark); }
        .background-container::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                repeating-linear-gradient(0deg, rgba(0, 246, 255, 0.05), rgba(0, 246, 255, 0.05) 1px, transparent 1px, transparent 4px),
                repeating-linear-gradient(90deg, rgba(0, 246, 255, 0.05), rgba(0, 246, 255, 0.05) 1px, transparent 1px, transparent 4px);
            opacity: 0.5;
            z-index: -1;
        }
        
        #constellation-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; opacity: 0.8; }

        .dashboard-wrapper { display: flex; min-height: 100vh; position: relative; opacity: 0; }
        
        .sidebar {
            width: 280px;
            background: rgba(22, 7, 29, 0.5);
            backdrop-filter: var(--blur); -webkit-backdrop-filter: var(--blur);
            border-right: 1px solid var(--glass-border);
            position: fixed; height: 100%; left: 0; top: 0; z-index: 1000;
            transform: translateX(-100%);
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 0 30px rgba(0, 246, 255, 0.2);
            clip-path: polygon(0 0, 100% 0, 100% 100%, 10px 100%, 0 99%);
            display: flex; flex-direction: column;
        }
        .sidebar-content { overflow-y: auto; overflow-x: hidden; padding-bottom: 24px; }
        .sidebar-content::-webkit-scrollbar { width: 4px; }
        .sidebar-content::-webkit-scrollbar-track { background: transparent; }
        .sidebar-content::-webkit-scrollbar-thumb { background: var(--glow-secondary); border-radius: 2px; }
        .sidebar.open { transform: translateX(0); }
        
        .sidebar-header { padding: 32px 24px; border-bottom: 1px solid var(--glass-border); flex-shrink: 0; }
        .logo { display: flex; align-items: center; gap: 16px; font-size: 24px; font-weight: 900; color: var(--glow-secondary); text-decoration: none; text-shadow: 0 0 10px var(--glow-secondary); }
        .logo i { font-size: 28px; color: var(--glow-secondary); }

        .user-profile { padding: 24px; flex-shrink: 0;}
        .user-info { display: flex; align-items: center; gap: 16px; }
        .user-avatar {
            width: 50px; height: 50px; border-radius: 50%; background: var(--gradient);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 20px;
            border: 2px solid var(--glow-secondary);
            box-shadow: 0 0 15px var(--glow-secondary);
            overflow: hidden;
        }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-details h3 { font-size: 16px; font-weight: 700; margin-bottom: 4px; color: var(--text-primary); }
        .user-details p { font-size: 14px; color: var(--glow-secondary); text-transform: uppercase; font-weight: 700; }
        
        .time-display { flex-shrink: 0; margin: 0 24px 16px 24px; padding: 20px; background: rgba(0,0,0,0.2); border-radius: 0; border: 1px solid var(--glass-border); transition: all 0.3s ease; clip-path: polygon(0 10px, 10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%); }
        .current-time { font-size: 22px; font-weight: 700; text-align: center; font-variant-numeric: tabular-nums; color: var(--glow-secondary); text-shadow: 0 0 5px var(--glow-secondary); }
        .current-date { font-size: 13px; color: var(--text-secondary); text-align: center; font-weight: 500; }
        
        nav { flex-grow: 1; }
        .nav-list { list-style: none; padding: 0 16px; }
        .nav-item { margin: 6px 0; }
        .nav-link { display: flex; align-items: center; gap: 16px; padding: 14px 20px; text-decoration: none; color: var(--text-secondary); border-radius: 0; transition: all 0.3s ease; position: relative; font-weight: 700; text-transform: uppercase; }
        .nav-link:hover { color: var(--glow-secondary); background: var(--glass-bg); text-shadow: 0 0 5px var(--glow-secondary); }
        .nav-link.active { color: var(--glow-secondary); background: var(--primary-light); border-right: 3px solid var(--glow-secondary); box-shadow: inset 0 0 15px rgba(0, 246, 255, 0.2); }
        .nav-icon { font-size: 20px; }
        .nav-text { font-size: 15px; }
        
        .main-content { flex: 1; margin-left: 280px; min-height: 100vh; transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94); position: relative; z-index: 10; }
        .content-header { background: transparent; padding: 24px 40px; border-bottom: 1px solid var(--glass-border); position: sticky; top: 0; z-index: 100; backdrop-filter: var(--blur); -webkit-backdrop-filter: var(--blur); }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .sidebar-toggle { display: none; background: none; border: none; color: var(--glow-secondary); font-size: 24px; cursor: pointer; }
        .content-title { font-size: 32px; font-weight: 900; color: var(--glow-secondary); text-shadow: 0 0 10px var(--glow-secondary); }
        .content-subtitle { color: var(--text-secondary); font-size: 16px; font-weight: 500; }
        .content-body { padding: 32px 40px 120px 40px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
        .stat-card { background: var(--glass-bg); border: 1px solid var(--glass-border); padding: 28px; position: relative; overflow: hidden; transition: all 0.4s ease; clip-path: polygon(0 15px, 15px 0, 100% 0, 100% calc(100% - 15px), calc(100% - 15px) 100%, 0 100%); }
        .stat-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-glow); border-color: var(--glow-secondary); }
        .stat-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(0deg, transparent, var(--glow-secondary), transparent); animation: rotate 4s linear infinite; }
        @keyframes rotate { 100% { transform: rotate(360deg); } }
        
        .stat-card-content { position: relative; z-index: 2; background: var(--primary); padding: 26px; clip-path: polygon(0 15px, 15px 0, 100% 0, 100% calc(100% - 15px), calc(100% - 15px) 100%, 0 100%); margin: 2px; }
        
        .stat-header { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
        .stat-icon { width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--dark); background: var(--glow-secondary); box-shadow: 0 0 15px var(--glow-secondary); clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%); }
        .stat-label { color: var(--text-secondary); font-size: 15px; font-weight: 700; text-transform: uppercase; }
        .stat-value { font-size: 40px; font-weight: 900; color: var(--text-primary); margin-bottom: 4px; font-variant-numeric: tabular-nums; }
        
        .mobile-bottom-nav { display: none; position: fixed; bottom: 0; left: 0; right: 0; background: var(--primary); backdrop-filter: var(--blur); -webkit-backdrop-filter: var(--blur); border-top: 1px solid var(--glass-border); padding: 8px 8px 24px 8px; z-index: 1000; box-shadow: 0 0 20px var(--glow-primary); }
        .mobile-nav-container { display: flex; justify-content: space-around; max-width: 500px; margin: 0 auto; }
        .mobile-nav-item { display: flex; flex-direction: column; align-items: center; gap: 4px; text-decoration: none; color: var(--text-muted); transition: all 0.3s ease; padding: 8px; border-radius: 12px; min-width: 60px; }
        .mobile-nav-icon i { font-size: 22px; transition: all 0.3s ease; }
        .mobile-nav-text { font-size: 11px; font-weight: 700; text-transform: uppercase;}
        .mobile-nav-item.active { color: var(--glow-secondary); text-shadow: 0 0 5px var(--glow-secondary);}
        .mobile-nav-item.active .mobile-nav-icon { transform: translateY(-4px); }
        .mobile-nav-item:active { transform: scale(0.95); }

        /* --- CUSTOM CURSOR --- */
        #cursor-dot, #cursor-outline { position: fixed; top: 0; left: 0; transform: translate(-50%, -50%); border-radius: 50%; pointer-events: none; z-index: 9998; }
        #cursor-dot { width: 8px; height: 8px; background-color: var(--glow-secondary); }
        #cursor-outline { width: 40px; height: 40px; border: 2px solid var(--glow-secondary); transition: transform 0.2s ease-out, opacity 0.2s ease-out; }
        #cursor-outline.hover { transform: translate(-50%, -50%) scale(1.5); opacity: 0.5; }

        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%) !important; }
            .sidebar.open { transform: translateX(0) !important; }
            .main-content { margin-left: 0; }
            .sidebar-toggle { display: block; }
            .mobile-bottom-nav { display: block; }
            .content-header { padding: 16px 20px; }
            .content-title { font-size: 28px; }
            .content-body { padding: 24px 20px 120px 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            #cursor-dot, #cursor-outline { display: none; }
            html { cursor: auto; }
            a, button, .stat-card { cursor: pointer; }
        }

        @media (max-width: 480px) {
            .content-header { padding: 16px; }
            .content-title { font-size: 24px; }
            .content-body { padding: 16px 16px 120px 16px; }
            .stat-value { font-size: 36px; }
            .stat-icon { width: 48px; height: 48px; }
        }
    </style>
</head>
<body>
    <div id="cursor-dot"></div>
    <div id="cursor-outline"></div>

    <div class="background-container">
        <canvas id="constellation-canvas"></canvas>
    </div>

    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="home" class="logo">
                    <i class='bx bxs-graduation'></i>
                    <span>Unies</span>
                </a>
            </div>
            <div class="sidebar-content">
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-avatar">
                             <?php if ($userAvatar): ?>
                                 <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar">
                            <?php else: ?>
                                 <?= strtoupper(substr($userName, 0, 2)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <h3><?= htmlspecialchars($userName) ?></h3>
                            <p><?= htmlspecialchars($userRole) ?></p>
                        </div>
                    </div>
                </div>
                <div class="time-display">
                    <div id="current-time" class="current-time">12:00:00 AM</div>
                    <div id="current-date" class="current-date">Saturday, January 1</div>
                </div>
                <nav>
                    <ul class="nav-list">
                        <?= generateNavLinks($activeMenu, $section) ?>
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <div>
                        <h1 id="content-title" class="content-title"><?= htmlspecialchars(ucfirst($activeMenu[$section]['text'] ?? $section)) ?></h1>
                        <p id="content-subtitle" class="content-subtitle">Welcome back, <?= htmlspecialchars($userName) ?>!</p>
                    </div>
                    <button class="sidebar-toggle" id="sidebar-toggle">
                        <i class='bx bx-menu'></i>
                    </button>
                </div>
            </header>
            <div class="content-body" id="content-body">
                <?php
                // This code only runs on a full page load.
                $page_map = [
                    'overview' => __DIR__ . "/../api/templates/overview.php",
                    'create-course' => __DIR__ . "/../api/courses/create.php",
                    'content' => __DIR__ . "/../admin/content.php",
                    'manage' => __DIR__ . "/../api/courses/manage.php",
                    'create-lesson' => __DIR__ . "/../api/lessons/create.php",
                    'manage-lessons' => __DIR__ . "/../api/lessons/manage.php",
                    'create-quiz' => __DIR__ . "/../api/quizzes/create.php",
                    'manage-quizzes' => __DIR__ . "/../api/quizzes/manage.php",
                    'manage-coupons' => __DIR__ . "/manage.php",
                    'quiz' => __DIR__ . "/../student/quiz.php",
                    'submit_quiz' => __DIR__ . "/../api/quizzes/submit_quiz.php",
                    'my-courses' => __DIR__ . "/../student/my_courses.php",
                    'lesson' => __DIR__ . "/../student/lesson.php",
                    'certificate' => __DIR__ . "/../student/certificate.php",
                    'certificates' => __DIR__ . "/../student/certificates.php",
                    'grades' => __DIR__ . "/../student/grades.php",
                    'course-details' => __DIR__ . "/../api/courses/detail.php",
                    'browse-courses' => __DIR__ . "/../student/browse-courses.php",
                    'profile' => __DIR__ . "/profile.php",
                    'settings' => __DIR__ . "/../admin/settings.php",
                    'enrollment-management' => __DIR__ . "/../admin/enrollment-management.php",
                    'users' => __DIR__ . "/../api/users/user-management.php",
                    'referral-settings' => __DIR__ . "/../admin/referral_settings.php",
                    'referral-report' => __DIR__ . "/../admin/referrals.php",
                    'reports' => __DIR__ . "/../admin/reports.php",
                    'withdrawals' => __DIR__ . "/../admin/withdrawals.php",
                    'my-referrals' => __DIR__ . "/../student/referrals.php",
                    'my-wallet' => __DIR__ . "/../student/wallet.php",
                    'instructor-settings' => __DIR__ . "/../admin/instructor_settings.php",
                    'instructor-payouts' => __DIR__ . "/../admin/instructor_withdrawals.php",
                    'payouts' => __DIR__ . "/../instructor/wallet.php",
                    'payout-history' => __DIR__ . "/../instructor/withdrawals.php",
                ];
                $page_path = $page_map[$section] ?? '';

                if ($page_path && file_exists($page_path)) {
                    include $page_path;
                } else {
                    echo "<h2>Content for " . htmlspecialchars(ucfirst($section)) . " loaded initially.</h2>";
                     echo '<div class="stats-grid" style="margin-top: 2rem;">
                                <div class="stat-card">
                                    <div class="stat-card-content">
                                        <div class="stat-header">
                                            <div class="stat-icon"><i class="bx bxs-group"></i></div>
                                            <span class="stat-label">Total Students</span>
                                        </div>
                                        <div class="stat-value">12,847</div>
                                    </div>
                                </div>
                                <div class="stat-card">
                                  <div class="stat-card-content">
                                        <div class="stat-header">
                                            <div class="stat-icon"><i class="bx bxs-book-reader"></i></div>
                                            <span class="stat-label">Active Courses</span>
                                        </div>
                                        <div class="stat-value">256</div>
                                    </div>
                                </div>
                            </div>';
                }
                ?>
            </div>
        </main>
    </div>

    <nav class="mobile-bottom-nav">
        <div class="mobile-nav-container">
            <?= generateMobileNav($activeMenu, $section) ?>
        </div>
    </nav>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const dashboardWrapper = document.querySelector('.dashboard-wrapper');
        const sidebar = document.getElementById('sidebar');

        // --- INITIAL ANIMATION ---
        const tl = gsap.timeline();
        tl.to(dashboardWrapper, { opacity: 1, duration: 0.3 })
          .fromTo(sidebar, { x: '-100%' }, { x: '0%', duration: 0.5, ease: 'power3.out' })
          .fromTo('.user-profile, .time-display, .nav-item', { opacity: 0, x: -20 }, { opacity: 1, x: 0, duration: 0.3, stagger: 0.05, ease: 'power2.out' }, "-=0.3")
          .fromTo('.content-header', { opacity: 0, y: -15 }, { opacity: 1, y: 0, duration: 0.4 }, "-=0.4")
          .fromTo('.content-body > *', { opacity: 0, y: 15 }, { opacity: 1, y: 0, duration: 0.4, stagger: 0.07 }, "-=0.3");

        // --- CUSTOM CURSOR ---
        const cursorDot = document.getElementById('cursor-dot');
        const cursorOutline = document.getElementById('cursor-outline');
        
        const dotX = gsap.quickTo(cursorDot, "x", { duration: 0.4, ease: "power3" });
        const dotY = gsap.quickTo(cursorDot, "y", { duration: 0.4, ease: "power3" });
        const outlineX = gsap.quickTo(cursorOutline, "x", { duration: 0.8, ease: "power3" });
        const outlineY = gsap.quickTo(cursorOutline, "y", { duration: 0.8, ease: "power3" });

        window.addEventListener('mousemove', e => {
            dotX(e.clientX);
            dotY(e.clientY);
            outlineX(e.clientX);
            outlineY(e.clientY);
        });

        document.querySelectorAll('a, button, .stat-card').forEach(el => {
            el.addEventListener('mouseenter', () => cursorOutline.classList.add('hover'));
            el.addEventListener('mouseleave', () => cursorOutline.classList.remove('hover'));
        });
        
        // --- SOUND EFFECTS ---
        const sounds = {
            hover: new Audio("data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA"),
            click: new Audio("data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU constricted"),
        };
        sounds.hover.volume = 0.1;
        sounds.click.volume = 0.2;

        document.body.addEventListener('mouseenter', (e) => {
            if (e.target.closest('a, button, .stat-card')) {
                sounds.hover.currentTime = 0;
                sounds.hover.play().catch(()=>{});
            }
        }, true);
        
        // Global click sound
        document.body.addEventListener('click', () => {
             sounds.click.currentTime = 0;
             sounds.click.play().catch(()=>{});
        });

        // --- EXISTING LOGIC ---
        const timeElement = document.getElementById('current-time');
        const dateElement = document.getElementById('current-date');
        function updateClock() {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit'});
            dateElement.textContent = now.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        document.getElementById('sidebar-toggle').addEventListener('click', () => sidebar.classList.toggle('open'));
        
        // --- AJAX Navigation Logic ---
        const contentBody = document.getElementById('content-body');
        const contentTitle = document.getElementById('content-title');
        const contentSubtitle = document.getElementById('content-subtitle');
        const activeMenu = <?= json_encode($activeMenu) ?>;

        const loadContent = (url, pushState = true) => {
            gsap.to(contentBody, { opacity: 0, y: 10, duration: 0.2, ease: 'power2.in', onComplete: () => {
                const cacheBustUrl = url + (url.includes('?') ? '&' : '?') + 'ajax=true';
                fetch(cacheBustUrl, {
                    cache: 'no-store', // Forces the browser to always request from the network
                    headers: {
                        'Cache-Control': 'no-cache' // For older browsers/proxies
                    }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(html => {
                        contentBody.innerHTML = html;
                        if (pushState) {
                            history.pushState({ path: url }, '', url);
                        }
                        const page = new URL(url, window.location.origin).searchParams.get('page') || 'overview';
                        const menuItem = activeMenu[page];
                        if (menuItem) {
                            contentTitle.textContent = menuItem.text;
                            contentSubtitle.textContent = `SYSTEM > ${menuItem.text.toUpperCase()}`;
                        }
                        gsap.fromTo(contentBody, { opacity: 0, y: -10 }, { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out' });
                        gsap.fromTo('.content-body > *, .stats-grid > *', { opacity: 0, y: 15 }, { opacity: 1, y: 0, duration: 0.3, stagger: 0.05, ease: 'power2.out', delay: 0.1 });
                    })
                    .catch(error => {
                        console.error('Failed to load page content:', error);
                        contentBody.innerHTML = `<h2>Error loading content.</h2>`;
                        gsap.fromTo(contentBody, { opacity: 0, y: -10 }, { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out' });
                    });
            }});
        };

        document.body.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            // Ensure the link is internal and not for logout
            if (!link || !link.getAttribute('href') || !link.getAttribute('href').startsWith('?page=')) return;
            
            e.preventDefault();
            // Do not reload if the link is the same as the current page
            if(window.location.href === link.href) return;

            // Update active state on all navs
            document.querySelectorAll(".nav-link, .mobile-nav-item").forEach(l => l.classList.remove('active'));
            const section = new URL(link.href).searchParams.get('page');
            document.querySelectorAll(`a[href="?page=${section}"]`).forEach(l => l.classList.add('active'));

            // On mobile, close sidebar after clicking a link
            if (window.innerWidth <= 900) sidebar.classList.remove('open');
            
            loadContent(link.href);
        });
        
        window.addEventListener("popstate", (e) => {
            // Update active menu on back/forward
            const section = new URL(location.href).search_params.get('page') || 'overview';
            document.querySelectorAll(".nav-link, .mobile-nav-item").forEach(l => l.classList.remove('active'));
            document.querySelectorAll(`a[href="?page=${section}"]`).forEach(l => l.classList.add('active'));

            loadContent(e.state ? e.state.path : window.location.href, false);
        });


        const canvas = document.getElementById('constellation-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        const mouse = { x: null, y: null };

        function setCanvasSize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        setCanvasSize();

        window.addEventListener('resize', ()=>{
            setCanvasSize();
            initParticles();
        });
        window.addEventListener('mousemove', (e) => { mouse.x = e.x; mouse.y = e.y; });
        window.addEventListener('mouseout', () => { mouse.x = null; mouse.y = null; });

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 1.5 + 0.5;
                this.speedX = Math.random() * 0.4 - 0.2;
                this.speedY = Math.random() * 0.4 - 0.2;
                this.color = `rgba(0, 246, 255, ${Math.random() * 0.5 + 0.2})`;
            }
            update() {
                if (this.x > canvas.width || this.x < 0) this.speedX *= -1;
                if (this.y > canvas.height || this.y < 0) this.speedY *= -1;
                this.x += this.speedX;
                this.y += this.speedY;
            }
            draw() {
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        function initParticles() {
            const particleCount = Math.floor((canvas.width * canvas.height) / 15000);
            particles = [];
            for (let i = 0; i < particleCount; i++) {
                particles.push(new Particle());
            }
        }
        initParticles();

        function connectParticles() {
            for (let a = 0; a < particles.length; a++) {
                for (let b = a; b < particles.length; b++) {
                    let distance = Math.hypot(particles[a].x - particles[b].x, particles[a].y - particles[b].y);
                    if (distance < 120) {
                        const opacityValue = 1 - (distance / 120);
                        ctx.strokeStyle = `rgba(0, 246, 255, ${opacityValue * 0.3})`;
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(particles[a].x, particles[a].y);
                        ctx.lineTo(particles[b].x, particles[b].y);
                        ctx.stroke();
                    }
                }
            }
            if (mouse.x && mouse.y) {
                 for (let i = 0; i < particles.length; i++) {
                     let distance = Math.hypot(mouse.x - particles[i].x, mouse.y - particles[i].y);
                      if (distance < 250) {
                          const opacityValue = 1 - (distance / 250);
                          ctx.strokeStyle = `rgba(213, 59, 255, ${opacityValue * 0.8})`;
                          ctx.lineWidth = 1;
                          ctx.beginPath();
                          ctx.moveTo(mouse.x, mouse.y);
                          ctx.lineTo(particles[i].x, particles[i].y);
                          ctx.stroke();
                      }
                 }
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => { p.update(); p.draw(); });
            connectParticles();
            requestAnimationFrame(animate);
        }
        animate();
    });
    </script>
</body>
</html>

