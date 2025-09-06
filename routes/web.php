<?php
// index.php
session_start();

// Determine the requested page, default to 'home'
$page = $_GET['page'] ?? 'home';

// Pages where we **don't want header & footer**
$dashboard_pages = [
    'admin_dashboard',
    'admin_users',
    'admin_courses',
    'instructor_dashboard',
    'dashboard',
    'my-courses', // ✅ student_v1 page will NOT load header/footer
];

// Check if header/footer should be included
$include_layout = !in_array($page, $dashboard_pages);

// Include header only if needed
if ($include_layout) {
    include 'includes/header.php';
}

// Handle logout
if ($page === 'logout') {
    $_SESSION = [];
    session_destroy();

    // Redirect to home
    header("Location: index.php");
    exit;
}

// Routing
switch ($page) {
    case 'home':
        include 'pages/home.php';
        break;
    case 'about':
        include 'pages/about.php';
        break;
    case 'contact':
        include 'pages/contact.php';
        break;
    case 'terms':
        include 'pages/terms.php';
        break;
    case 'privacy':
        include 'pages/privacy.php';
        break;
    case 'faq':
        include 'pages/faq.php';
        break;
    case 'aiub_courses':
        include 'pages/aiub_courses.php';
        break;
    case 'nsu_courses':
        include 'pages/nsu_courses.php';
        break;
    case 'brac_courses':
        include 'pages/brac_courses.php';
        break;
    case 'courses':
        include 'pages/courses.php';
        break;

    // Admin Pages
    case 'dashboard':
        include 'pages/dashboard.php';
        break;
    case 'admin_users':
        include 'admin/users.php';
        break;
    case 'admin_courses':
        include 'admin/courses.php';
        break;

    // Instructor Pages
     case 'my-courses':
        include 'student/my_courses.php';
        break;

    // Student Pages

    case 'account':
        include 'pages/account.php';
        break;
    case 'register':
            include 'pages/account.php';
            break;
    case 'course_details':
        include './api/courses/detail.php';
        break;
    case 'course_management':
        include './api/courses/admin';
        break;
    case 'enroll':
        include './api/enrollments/enroll.php';
        break;
    case 'my_courses':
        include './api/enrollments/my_courses.php';
        break;
    case 'progress':
        include './api/enrollments/progress.php';
        break;
    case 'lesson':
        include './api/lessons/lesson.php';
        break;
    case 'quiz':
        include './api/quizzes/quiz.php';
        break;      
    case 'take_quiz':
        include './api/quizzes/take_quiz.php';
        break;
    case 'submit_quiz':
        include './api/quizzes/submit_quiz.php';
        break;
    default:
        include 'pages/404.php';
        break;
}

// Include footer only if needed
if ($include_layout) {
    include 'includes/footer.php';
}
