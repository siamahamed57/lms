<?php
// Path to the database connection file.
require_once __DIR__ . '../../includes/db.php';
session_start();
include 'admin_sidebar.php';
// Check if the user is logged in and has the 'admin' role.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../../index.php?page=login_register');
    exit;
}

// Global variable for database connection
global $conn;

// Constants
$users_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $users_per_page;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = '%' . $search_query . '%';

// Dynamic SQL query building
$where_clauses = [];
$sql_params = [];
if (!empty($search_query)) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ?)";
    $sql_params[] = $search_param;
    $sql_params[] = $search_param;
}
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch total user count for pagination
$count_query = "SELECT COUNT(*) FROM users" . $where_sql;
$stmt_count = $conn->prepare($count_query);
if (!empty($search_query)) {
    $stmt_count->bind_param("ss", $search_param, $search_param);
}
$stmt_count->execute();
$stmt_count->bind_result($total_users);
$stmt_count->fetch();
$stmt_count->close();
$total_pages = ceil($total_users / $users_per_page);

// Fetch users for the current page
$query = "SELECT id, name, email, role FROM users" . $where_sql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
if (!empty($search_query)) {
    $stmt->bind_param("ssii", $search_param, $search_param, $users_per_page, $offset);
} else {
    $stmt->bind_param("ii", $users_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | User Management</title>
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
        .modal-content {
            background-color: var(--card-bg);
            border: 1px solid var(--header-border);
        }
    </style>
</head>
<body class="bg-gray-100 font-poppins dark:bg-gray-900 dark:text-gray-200">
    <div class="flex h-screen">

        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-gray-800 text-white p-6 space-y-6 flex-shrink-0 lg:flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
            <div class="flex items-center justify-between lg:justify-center">
                <a href="index.php?page=home" class="font-bold text-3xl text-purple-400">UNIES</a>
                <button id="close-sidebar-btn" class="lg:hidden text-gray-400 hover:text-white text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="mt-8 flex-grow">
                <ul class="space-y-2">
                    <li><a href="admin.php?page=dashboard" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-th-large"></i><span>Dashboard</span></a></li>
                    <li><a href="admin.php?page=user_management" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 active-link"><i class="fas fa-users"></i><span>User Management</span></a></li>
                    <li><a href="admin.php?page=course_management" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-book-open"></i><span>Course Management</span></a></li>
                    <li><a href="admin.php?page=bundle_category_management" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-cubes"></i><span>Bundles & Categories</span></a></li>
                    <li><a href="admin.php?page=enrollments_orders" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-dollar-sign"></i><span>Enrollments & Orders</span></a></li>
                    <li><a href="admin.php?page=quiz_assignment_management" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-pen-square"></i><span>Quizzes & Assignments</span></a></li>
                    <li><a href="admin.php?page=live_classes" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-video"></i><span>Live Classes</span></a></li>
                    <li><a href="admin.php?page=communication_notifications" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-bell"></i><span>Communication</span></a></li>
                    <li><a href="admin.php?page=certificates" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-award"></i><span>Certificates</span></a></li>
                    <li><a href="admin.php?page=reviews_feedback" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-comments"></i><span>Reviews & Feedback</span></a></li>
                    <li><a href="admin.php?page=reports_analytics" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-chart-line"></i><span>Reports & Analytics</span></a></li>
                    <li><a href="admin.php?page=settings" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200"><i class="fas fa-cog"></i><span>Settings</span></a></li>
                </ul>
            </nav>
            <div class="border-t border-gray-700 pt-4 mt-auto">
                <a href="index.php?page=logout" class="flex items-center space-x-3 p-3 rounded-lg text-red-400 hover:bg-gray-700 hover:text-red-300 transition-colors duration-200">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </div>
        </aside>

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
                    <h1 class="text-4xl font-extrabold mb-4" style="color: var(--primary-color);">User Management</h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-8">Manage all user accounts, roles, and permissions.</p>

                    <div class="card p-6">
                        <!-- Controls and Search -->
                        <div class="flex flex-col md:flex-row items-center justify-between mb-6 space-y-4 md:space-y-0">
                            <form action="" method="get" class="w-full md:w-1/2">
                                <div class="relative">
                                    <input type="text" name="search" placeholder="Search users by name or email..." value="<?= htmlspecialchars($search_query) ?>"
                                        class="w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none bg-gray-50 dark:bg-gray-700 dark:border-gray-600">
                                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </form>
                            <div class="flex space-x-2">
                                <button onclick="openModal('add-user-modal')" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center space-x-2 transition-colors duration-200">
                                    <i class="fas fa-plus"></i><span>Add User</span>
                                </button>
                                <button class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center space-x-2 transition-colors duration-200">
                                    <i class="fas fa-file-export"></i><span>Export</span>
                                </button>
                                <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center space-x-2 transition-colors duration-200">
                                    <i class="fas fa-file-import"></i><span>Import</span>
                                </button>
                            </div>
                        </div>

                        <!-- User Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-700 dark:divide-gray-600">
                                <thead class="bg-gray-700 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700 dark:divide-gray-600">
                                    <?php if (empty($users)): ?>
                                        <tr><td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-400">No users found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['name']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['email']) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <a href="#" onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" class="text-blue-500 hover:text-blue-700 mr-4">Edit</a>
                                                    <a href="#" onclick="openDeleteModal(<?= $user['id'] ?>)" class="text-red-500 hover:text-red-700">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6 flex justify-between items-center">
                            <span class="text-sm text-gray-400">Showing page <?= htmlspecialchars($current_page) ?> of <?= htmlspecialchars($total_pages) ?></span>
                            <div class="flex space-x-2">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?= $i ?>&search=<?= urlencode($search_query) ?>" class="px-3 py-1 border rounded-lg hover:bg-gray-700 transition-colors duration-200 <?= $i == $current_page ? 'bg-purple-600 text-white' : 'text-gray-400' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="add-edit-user-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center">
        <div class="modal-content w-full max-w-lg p-8 rounded-lg shadow-2xl">
            <h2 id="modal-title" class="text-2xl font-bold mb-6">Add New User</h2>
            <form id="user-form" action="" method="post">
                <input type="hidden" name="user_id" id="user-id">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium">Full Name</label>
                        <input type="text" id="name" name="name" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-gray-700 border-gray-600">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium">Email</label>
                        <input type="email" id="email" name="email" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-gray-700 border-gray-600">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium">Role</label>
                        <select id="role" name="role" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-gray-700 border-gray-600">
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium">Password</label>
                        <input type="password" id="password" name="password" class="w-full mt-1 px-3 py-2 border rounded-lg bg-gray-700 border-gray-600">
                        <p class="text-xs text-gray-500 mt-1" id="password-note">Leave blank to keep current password.</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('add-edit-user-modal')" class="px-4 py-2 rounded-lg bg-gray-600 hover:bg-gray-700 text-white transition-colors duration-200">Cancel</button>
                    <button type="submit" name="action" id="submit-button" value="add" class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold transition-colors duration-200">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="delete-user-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center">
        <div class="modal-content w-full max-w-md p-8 rounded-lg shadow-2xl text-center">
            <h2 class="text-2xl font-bold mb-4">Confirm Deletion</h2>
            <p class="text-gray-400">Are you sure you want to delete this user? This action cannot be undone.</p>
            <form id="delete-form" action="" method="post" class="mt-6 flex justify-center space-x-4">
                <input type="hidden" name="delete_user_id" id="delete-user-id">
                <button type="button" onclick="closeModal('delete-user-modal')" class="px-4 py-2 rounded-lg bg-gray-600 hover:bg-gray-700 text-white transition-colors duration-200">Cancel</button>
                <button type="submit" name="action" value="delete" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition-colors duration-200">Delete User</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
        function openEditModal(user) {
            document.getElementById('modal-title').textContent = 'Edit User';
            document.getElementById('user-id').value = user.id;
            document.getElementById('name').value = user.name;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('password-note').classList.remove('hidden');
            document.getElementById('submit-button').value = 'update';
            document.getElementById('submit-button').textContent = 'Update User';
            openModal('add-edit-user-modal');
        }
        function openDeleteModal(id) {
            document.getElementById('delete-user-id').value = id;
            openModal('delete-user-modal');
        }

        // Sidebar toggle for mobile
        const sidebar = document.getElementById('sidebar');
        const openSidebarBtn = document.getElementById('open-sidebar-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar-btn');

        openSidebarBtn.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
        });

        closeSidebarBtn.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
        });
    </script>
</body>
</html>
