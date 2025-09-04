<?php
// Path to the database connection file.
require_once __DIR__ . '../../includes/db.php';


// Check if the user is logged in and has the 'admin' role.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../../index.php?page=login_register');
    exit;
}

// Fetch dashboard data
// Use the mysqli helper functions from db.php
$total_students = db_select("SELECT COUNT(*) AS count FROM users WHERE role = 'student'")[0]['count'] ?? 0;
$total_instructors = db_select("SELECT COUNT(*) AS count FROM users WHERE role = 'instructor'")[0]['count'] ?? 0;
$total_courses = db_select("SELECT COUNT(*) AS count FROM courses")[0]['count'] ?? 0;
$total_revenue = db_select("SELECT SUM(amount) AS total FROM orders WHERE payment_status = 'success'")[0]['total'] ?? 0;
$total_active_users = db_select("SELECT COUNT(*) AS count FROM users WHERE updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")[0]['count'] ?? 0;
$total_inactive_users = db_select("SELECT COUNT(*) AS count FROM users WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)")[0]['count'] ?? 0;
$total_course_completions = db_select("SELECT COUNT(*) AS count FROM certificates")[0]['count'] ?? 0;
$total_enrollments = db_select("SELECT COUNT(*) AS count FROM enrollments")[0]['count'] ?? 0;

$recent_enrollments = db_select("SELECT 
    e.enrolled_at, u.name AS student_name, c.title AS course_title
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY e.enrolled_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
    </style>
</head>
<body class="bg-gray-100 font-poppins dark:bg-gray-900 dark:text-gray-200">
    <div class="flex h-screen">

        <!-- Sidebar -->
        <?php include 'admin_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="p-4 bg-white shadow-md flex items-center justify-between lg:hidden dark:bg-gray-800">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Admin CMS</h1>
                <button id="open-sidebar-btn" class="text-gray-500 hover:text-gray-900 text-2xl dark:text-gray-400 dark:hover:text-white">
                    <i class="fas fa-bars"></i>
                </button>
            </header>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 dark:bg-gray-900">
                <div class="container mx-auto max-w-7xl">
                    <header class="mb-10 text-center lg:text-left">
                        <h1 class="text-4xl font-extrabold mb-2" style="color: var(--primary-color);">Admin Dashboard</h1>
                        <p class="text-gray-400">Welcome, Admin. Here is an overview of your platform.</p>
                    </header>

                    <!-- Main Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                        <!-- Total Students -->
                        <div class="card p-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold"><?= htmlspecialchars($total_students) ?></h2>
                                <p class="text-gray-400">Total Students</p>
                            </div>
                            <div class="text-4xl text-purple-500">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                        <!-- Total Instructors -->
                        <div class="card p-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold"><?= htmlspecialchars($total_instructors) ?></h2>
                                <p class="text-gray-400">Total Instructors</p>
                            </div>
                            <div class="text-4xl text-blue-500">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                        <!-- Total Courses -->
                        <div class="card p-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold"><?= htmlspecialchars($total_courses) ?></h2>
                                <p class="text-gray-400">Total Courses</p>
                            </div>
                            <div class="text-4xl text-green-500">
                                <i class="fas fa-book-open"></i>
                            </div>
                        </div>
                        <!-- Total Revenue -->
                        <div class="card p-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold">$<?= number_format($total_revenue, 2) ?></h2>
                                <p class="text-gray-400">Total Revenue</p>
                            </div>
                            <div class="text-4xl text-yellow-500">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Metrics & Recent Activity Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                        <!-- User Status -->
                        <div class="card p-6 text-center">
                            <h2 class="text-xl font-semibold mb-4">Active vs. Inactive Users</h2>
                            <div class="flex items-center justify-center space-x-6">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-circle text-green-500 text-sm"></i>
                                    <span>Active: <b class="font-bold text-lg"><?= htmlspecialchars($total_active_users) ?></b></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-circle text-red-500 text-sm"></i>
                                    <span>Inactive: <b class="font-bold text-lg"><?= htmlspecialchars($total_inactive_users) ?></b></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Enrollment Stats -->
                        <div class="card p-6 text-center">
                            <h2 class="text-xl font-semibold mb-4">Enrollments & Completions</h2>
                            <div class="flex items-center justify-center space-x-6">
                                <div>
                                    <p class="text-3xl font-bold text-purple-500"><?= htmlspecialchars($total_enrollments) ?></p>
                                    <p class="text-gray-400">Total Enrollments</p>
                                </div>
                                <div>
                                    <p class="text-3xl font-bold text-blue-500"><?= htmlspecialchars($total_course_completions) ?></p>
                                    <p class="text-gray-400">Certificates Issued</p>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Enrollments -->
                        <div class="card p-6 col-span-1 lg:col-span-1">
                            <h2 class="text-xl font-semibold mb-4">Recent Enrollments</h2>
                            <?php if (empty($recent_enrollments)): ?>
                                <p class="text-gray-400">No recent enrollments to display.</p>
                            <?php else: ?>
                                <ul class="divide-y divide-gray-700">
                                    <?php foreach ($recent_enrollments as $enrollment): ?>
                                        <li class="py-3">
                                            <p class="text-white font-medium"><?= htmlspecialchars($enrollment['student_name']) ?></p>
                                            <p class="text-sm text-gray-400">enrolled in <?= htmlspecialchars($enrollment['course_title']) ?></p>
                                            <span class="text-xs text-gray-500"><?= htmlspecialchars(date('M d, Y', strtotime($enrollment['enrolled_at']))) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Management Quick Links -->
                    <div class="card p-8">
                        <h2 class="text-2xl font-bold mb-6 text-center" style="color: var(--primary-color);">CMS Management</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <a href="admin.php?page=user_management" class="flex items-center space-x-4 p-5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-users text-3xl text-purple-400"></i>
                                <span class="font-medium text-lg">User Management</span>
                            </a>
                            <a href="admin.php?page=course_management" class="flex items-center space-x-4 p-5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-book-open text-3xl text-blue-400"></i>
                                <span class="font-medium text-lg">Course Management</span>
                            </a>
                            <a href="admin.php?page=bundle_category_management" class="flex items-center space-x-4 p-5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-cubes text-3xl text-pink-400"></i>
                                <span class="font-medium text-lg">Bundles</span>
                            </a>
                            <a href="admin.php?page=enrollments_orders" class="flex items-center space-x-4 p-5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-dollar-sign text-3xl text-green-400"></i>
                                <span class="font-medium text-lg">Payments & Finance</span>
                            </a>
                            <a href="admin.php?page=reports_analytics" class="flex items-center space-x-4 p-5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-chart-line text-3xl text-yellow-400"></i>
                                <span class="font-medium text-lg">Reports & Analytics</span>
                            </a>
                            <a href="admin.php?page=settings" class="flex items-center space-x-4 p-5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-cog text-3xl text-gray-400"></i>
                                <span class="font-medium text-lg">Settings</span>
                            </a>
                            <a href="admin.php?page=communication_notifications" class="flex items-center space-x-4 p-5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-headset text-3xl text-red-400"></i>
                                <span class="font-medium text-lg">Support</span>
                            </a>
                            <a href="admin.php?page=content_media" class="flex items-center space-x-4 p-5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-upload text-3xl text-orange-400"></i>
                                <span class="font-medium text-lg">Content & Media</span>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
