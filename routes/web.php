<?php
session_start();

// Routing param
$route = $_GET['_page'] ?? 'home';

// Pages that don't need the main site header/footer layout
$dashboard_pages = [
    'dashboard',
    'logout',
    'account',
    'enroll',
    'pay',
    'course_details',
    // Dashboard sections loaded via AJAX
    'overview',
    'users',
    'create-course', 'manage',
    'create-lesson', 'manage-lessons',
    'create-quiz', 'manage-quizzes',
    'my-courses', 'lesson', 'quiz',
    'lesson'
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
    case 'create-course':
        include 'api/courses/create.php';
        break;

    case 'create-lesson':
        include 'api/lessons/create.php';
        break;

     case 'create-quiz':
          include 'api/quizzes/create.php';
        break;
    case 'manage-quizzes':
        include 'api/quizzes/manage.php';
        break;

    // Student
    case 'account':
    case 'register':
    case 'login': // Consolidate login to account page
        include 'pages/account.php';
        break;
    case 'course_details':
        include './api/courses/detail.php';
        break;
    case 'enroll':
        include './api/enrollments/enroll.php';
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
    case 'pay':
        include 'api/payments/pay.php';

    default:
        include 'pages/404.php';
        break;
}

// Footer
if ($include_layout) {
    include 'includes/footer.php';
}
