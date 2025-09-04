<?php
// Path to the database connection file.
require_once __DIR__ . '../../includes/db.php';


// Check if the user is logged in and has the 'student' role.
// Assuming 'student' is the default and a valid role in your system.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'student') {
    header('Location: ../../index.php?page=login_register');
    exit;
}

// Set the active page for the sidebar
$page = 'dashboard';

// Fetch dashboard data for the logged-in student
$student_id = $_SESSION['user_id'];

// Total courses enrolled
$total_enrolled_courses = db_select("SELECT COUNT(*) AS count FROM enrollments WHERE student_id = ?", 'i', [$student_id])[0]['count'] ?? 0;

// Total completed courses
$total_completed_courses = db_select("SELECT COUNT(*) AS count FROM enrollments WHERE student_id = ? AND progress = 100.00", 'i', [$student_id])[0]['count'] ?? 0;

// Upcoming live classes
$upcoming_classes = db_select("SELECT lc.title, lc.start_time, c.title AS course_title
    FROM live_classes lc
    JOIN courses c ON lc.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND lc.start_time > NOW()
    ORDER BY lc.start_time ASC LIMIT 3", 'i', [$student_id]);

// Pending assignments
$pending_assignments = db_select("SELECT a.title, a.due_date, c.title AS course_title
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND NOT EXISTS (
        SELECT 1 FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = ?
    )
    ORDER BY a.due_date ASC LIMIT 3", 'ii', [$student_id, $student_id]);

// Courses with progress
$courses_with_progress = db_select("SELECT c.title, e.progress, e.course_id
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ?
    ORDER BY e.enrolled_at DESC LIMIT 5", 'i', [$student_id]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #b915ff;
            --secondary-color: #7c3aed;
        }

        [data-theme="dark"] {
            --bg-color: #0d1117;
            --text-color: #d7d8db;
            --header-bg: #111827b3;
            --header-border: #ffffff1a;
            --card-bg: #1f2937;
            --card-hover-bg: #374151;
        }
        [data-theme="light"] {
            --bg-color: #e6e7eb;
            --text-color: #000000;
            --header-bg: #ffffffb3;
            --header-border: #d1d5db;
            --card-bg: #ffffff;
            --card-hover-bg: #e5e7eb;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s ease;
        }
        .card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        .active-link {
            background-color: var(--primary-color);
            color: white;
        }
        .progress-bar-container {
            height: 8px;
            background-color: #4b5563; /* Gray-600 */
            border-radius: 9999px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100 font-poppins dark:bg-gray-900 dark:text-gray-200">
    <div class="flex h-screen">

        <!-- Sidebar -->
        <?php include 'student_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="p-4 bg-white shadow-md flex items-center justify-between lg:hidden dark:bg-gray-800">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Student Dashboard</h1>
                <button id="open-sidebar-btn" class="text-gray-500 hover:text-gray-900 text-2xl dark:text-gray-400 dark:hover:text-white">
                    <i class="fas fa-bars"></i>
                </button>
            </header>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 dark:bg-gray-900">
                <div class="container mx-auto max-w-7xl">
                    <header class="mb-10 text-center lg:text-left">
                        <h1 class="text-4xl font-extrabold mb-2" style="color: var(--primary-color);">Hello, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?>!</h1>
                        <p class="text-gray-600 dark:text-gray-400">Welcome back. Here is a summary of your academic journey.</p>
                    </header>

                    <!-- Main Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                        <!-- Total Courses Enrolled -->
                        <div class="card p-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold"><?= htmlspecialchars($total_enrolled_courses) ?></h2>
                                <p class="text-gray-400">Courses Enrolled</p>
                            </div>
                            <div class="text-4xl text-purple-500">
                                <i class="fas fa-book-reader"></i>
                            </div>
                        </div>
                        <!-- Completed Courses -->
                        <div class="card p-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold"><?= htmlspecialchars($total_completed_courses) ?></h2>
                                <p class="text-gray-400">Courses Completed</p>
                            </div>
                            <div class="text-4xl text-green-500">
                                <i class="fas fa-award"></i>
                            </div>
                        </div>
                        <!-- Quizzes Completed (Placeholder) -->
                        <div class="card p-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold">12</h2>
                                <p class="text-gray-400">Quizzes Attempted</p>
                            </div>
                            <div class="text-4xl text-yellow-500">
                                <i class="fas fa-question-circle"></i>
                            </div>
                        </div>
                        <!-- Certificates Earned -->
                        <div class="card p-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold"><?= htmlspecialchars($total_course_completions) ?></h2>
                                <p class="text-gray-400">Certificates Earned</p>
                            </div>
                            <div class="text-4xl text-blue-500">
                                <i class="fas fa-certificate"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Classes & Assignments Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
                        <!-- Upcoming Live Classes -->
                        <div class="card p-6">
                            <h2 class="text-xl font-semibold mb-4">Upcoming Live Classes</h2>
                            <?php if (empty($upcoming_classes)): ?>
                                <p class="text-gray-400">No upcoming live classes.</p>
                            <?php else: ?>
                                <ul class="divide-y divide-gray-700">
                                    <?php foreach ($upcoming_classes as $class): ?>
                                        <li class="py-3">
                                            <p class="text-white font-medium"><?= htmlspecialchars($class['title']) ?></p>
                                            <p class="text-sm text-gray-400">Course: <?= htmlspecialchars($class['course_title']) ?></p>
                                            <span class="text-xs text-gray-500">On <?= htmlspecialchars(date('M d, Y \a\t h:i A', strtotime($class['start_time']))) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Pending Assignments -->
                        <div class="card p-6">
                            <h2 class="text-xl font-semibold mb-4">Pending Assignments</h2>
                            <?php if (empty($pending_assignments)): ?>
                                <p class="text-gray-400">No pending assignments.</p>
                            <?php else: ?>
                                <ul class="divide-y divide-gray-700">
                                    <?php foreach ($pending_assignments as $assignment): ?>
                                        <li class="py-3">
                                            <p class="text-white font-medium"><?= htmlspecialchars($assignment['title']) ?></p>
                                            <p class="text-sm text-gray-400">Course: <?= htmlspecialchars($assignment['course_title']) ?></p>
                                            <span class="text-xs text-red-500">Due: <?= htmlspecialchars(date('M d, Y', strtotime($assignment['due_date']))) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- My Progress Section -->
                    <div class="card p-6">
                        <h2 class="text-xl font-semibold mb-4">My Course Progress</h2>
                        <?php if (empty($courses_with_progress)): ?>
                            <p class="text-gray-400">You are not enrolled in any courses yet.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($courses_with_progress as $course): ?>
                                    <div class="flex flex-col space-y-2">
                                        <a href="student.php?page=course_details&id=<?= htmlspecialchars($course['course_id']) ?>" class="font-medium text-gray-200 hover:text-purple-400 transition-colors duration-200">
                                            <?= htmlspecialchars($course['title']) ?>
                                        </a>
                                        <div class="flex items-center space-x-4">
                                            <div class="progress-bar-container flex-grow">
                                                <div class="progress-bar" style="width: <?= htmlspecialchars($course['progress']) ?>%;"></div>
                                            </div>
                                            <span class="text-sm font-semibold"><?= htmlspecialchars(number_format($course['progress'], 0)) ?>%</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const openSidebarBtn = document.getElementById('open-sidebar-btn');
            const closeSidebarBtn = document.getElementById('close-sidebar-btn');

            if (openSidebarBtn && closeSidebarBtn && sidebar) {
                openSidebarBtn.addEventListener('click', () => {
                    sidebar.classList.remove('-translate-x-full');
                });

                closeSidebarBtn.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                });
            }
        });
    </script>
</body>
</html>
