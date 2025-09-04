<?php
// This file is a self-contained sidebar component for the student panel.
// It assumes the $page variable is set by the parent PHP file (e.g., student_dashboard.php)
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
            <li><a href="student.php?page=dashboard" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'dashboard' ? 'active-link' : '' ?>"><i class="fas fa-th-large"></i><span>Dashboard</span></a></li>
            <li><a href="student.php?page=enrolled_courses" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'enrolled_courses' ? 'active-link' : '' ?>"><i class="fas fa-book-open"></i><span>My Courses</span></a></li>
            <li><a href="student.php?page=assignments" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'assignments' ? 'active-link' : '' ?>"><i class="fas fa-tasks"></i><span>Assignments</span></a></li>
            <li><a href="student.php?page=quizzes" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'quizzes' ? 'active-link' : '' ?>"><i class="fas fa-question-circle"></i><span>Quizzes & Exams</span></a></li>
            <li><a href="student.php?page=live_classes" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'live_classes' ? 'active-link' : '' ?>"><i class="fas fa-video"></i><span>Live Classes</span></a></li>
            <li><a href="student.php?page=certificates" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'certificates' ? 'active-link' : '' ?>"><i class="fas fa-award"></i><span>Certificates</span></a></li>
            <li><a href="student.php?page=profile" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'profile' ? 'active-link' : '' ?>"><i class="fas fa-user-circle"></i><span>Profile Management</span></a></li>
            <li><a href="student.php?page=payments" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'payments' ? 'active-link' : '' ?>"><i class="fas fa-dollar-sign"></i><span>Orders & Payments</span></a></li>
            <li><a href="student.php?page=reviews" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'reviews' ? 'active-link' : '' ?>"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="student.php?page=notifications" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'notifications' ? 'active-link' : '' ?>"><i class="fas fa-bell"></i><span>Notifications</span></a></li>
            <li><a href="student.php?page=support" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?= $page == 'support' ? 'active-link' : '' ?>"><i class="fas fa-headset"></i><span>Support</span></a></li>
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
