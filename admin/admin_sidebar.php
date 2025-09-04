<?php
// This file is a self-contained sidebar component for the admin panel.
// It assumes the $page variable is set by the parent PHP file (e.g., admin.php)
// to determine which link should be active.
$page = $page ?? 'dashboard';
?>
<aside id="sidebar" class="w-64 bg-gray-800 text-white p-6 space-y-6 flex-shrink-0 lg:flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="flex items-center justify-between lg:justify-center">
        <a href="index.php?page=home" class="font-bold text-3xl text-purple-400">UNIES</a>
        <button id="close-sidebar-btn" class="lg:hidden text-gray-400 hover:text-white text-2xl">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="mt-8 flex-grow">
        <ul class="space-y-2">
            <li><a href="admin.php?page=dashboard" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'dashboard' ? 'active-link' : '' ?>"><i class="fas fa-th-large"></i><span>Dashboard</span></a></li>
            <li><a href="admin.php?page=user_management" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'user_management' ? 'active-link' : '' ?>"><i class="fas fa-users"></i><span>User Management</span></a></li>
            <li><a href="admin.php?page=course_management" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'course_management' ? 'active-link' : '' ?>"><i class="fas fa-book-open"></i><span>Course Management</span></a></li>
            <li><a href="admin.php?page=bundle_category_management" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'bundle_category_management' ? 'active-link' : '' ?>"><i class="fas fa-cubes"></i><span>Bundles & Categories</span></a></li>
            <li><a href="admin.php?page=enrollments_orders" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'enrollments_orders' ? 'active-link' : '' ?>"><i class="fas fa-dollar-sign"></i><span>Enrollments & Orders</span></a></li>
            <li><a href="admin.php?page=quiz_assignment_management" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'quiz_assignment_management' ? 'active-link' : '' ?>"><i class="fas fa-pen-square"></i><span>Quizzes & Assignments</span></a></li>
            <li><a href="admin.php?page=live_classes" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'live_classes' ? 'active-link' : '' ?>"><i class="fas fa-video"></i><span>Live Classes</span></a></li>
            <li><a href="admin.php?page=communication_notifications" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'communication_notifications' ? 'active-link' : '' ?>"><i class="fas fa-bell"></i><span>Communication</span></a></li>
            <li><a href="admin.php?page=certificates" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'certificates' ? 'active-link' : '' ?>"><i class="fas fa-award"></i><span>Certificates</span></a></li>
            <li><a href="admin.php?page=reviews_feedback" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'reviews_feedback' ? 'active-link' : '' ?>"><i class="fas fa-comments"></i><span>Reviews & Feedback</span></a></li>
            <li><a href="admin.php?page=reports_analytics" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'reports_analytics' ? 'active-link' : '' ?>"><i class="fas fa-chart-line"></i><span>Reports & Analytics</span></a></li>
            <li><a href="admin.php?page=settings" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'settings' ? 'active-link' : '' ?>"><i class="fas fa-cog"></i><span>Settings</span></a></li>
        </ul>
    </nav>
    <div class="border-t border-gray-700 pt-4 mt-auto">
        <a href="index.php?page=logout" class="flex items-center space-x-3 p-3 rounded-lg text-red-400 hover:bg-gray-700 hover:text-red-300 transition-colors duration-200">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const openSidebarBtn = document.getElementById('open-sidebar-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar-btn');

        openSidebarBtn.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
        });

        closeSidebarBtn.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
        });
    });
</script>
