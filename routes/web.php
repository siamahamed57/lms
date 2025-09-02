<?php
// index.php


// Determine the requested page, default to 'home'
$page = $_GET['page'] ?? 'home';

// Include the header
include 'includes/header.php';
if ($page === 'logout') {
    // Clear session
    $_SESSION = [];
    session_destroy();

    // Redirect to login/account page
    header('Location: index.php?page=account');
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
    case 'admin_dashboard':
        include 'admin/dashboard.php';
        break;
    case 'admin_users':
        include 'admin/users.php';
        break;
    case 'admin_courses':
        include 'admin/courses.php';
        break;

    // Instructor Pages
    case 'instructor_dashboard':
        include 'instructor/dashboard.php';
        break;

    // Student Pages
    case 'student_dashboard':
        include 'student/dashboard.php';
        break;
    case 'account':
        include 'pages/account.php';
        break;
    case 'contact':
        include 'pages/contact.php';
        break;
    default:
        include 'pages/404.php';
        break;
    case 'course_details':
        include './api/courses/detail.php';
        break;

}

// Include the footer
include 'includes/footer.php';
