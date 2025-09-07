<?php
session_start();

// Routing param
$route = $_GET['_page'] ?? 'home';

// Reserved dashboard pages
$dashboard_pages = [
    'admin_dashboard',
    'admin_users',
    'admin_courses',
    'instructor_dashboard',
    'dashboard',
    'logout',
    'account',
    'my-courses'
    
];

// Layout?
$include_layout = !in_array($route, $dashboard_pages);

// Include header
if ($include_layout) {
    include 'includes/header.php';
}


// Router
switch ($route) {

    //pages
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
    case 'logout':
        include 'api/auth/logout.php';
        break;
    case 'login':
        include 'api/auth/login.php';
        break;

    // Instructor
    case 'my-courses':
        include 'student/my_courses.php';
        break;
    // Dashboard template
    case 'overview':
        include'api/templates/overview.php';
        break;

        
    //Course Management
    case 'manage':
        include 'api/courses/manage.php';
        break;





    // Student
    case 'account':
    case 'register':
        include 'pages/account.php';
        break;
    case 'course_details':
        include './api/courses/detail.php';
        break;
    case 'course_management':
        include './api/courses/admin.php';
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

// Footer
if ($include_layout) {
    include 'includes/footer.php';
}
