<?php
// --- AJAX Request Handler ---
// If 'ajax' param is set, only render the requested section and exit.
if (isset($_GET['ajax'])) {
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
        'manage' => __DIR__ . "/../api/courses/manage.php",
        'create-lesson' => __DIR__ . "/../api/lessons/create.php",
        'manage-lessons' => __DIR__ . "/../api/lessons/manage.php",
        'create-quiz' => __DIR__ . "/../api/quizzes/create.php",
        'manage-quizzes' => __DIR__ . "/../api/quizzes/manage.php",
        'users' => __DIR__ . "/../api/users/user-management.php",
    ];
    $page_path = $page_map[$section] ?? '';
    if ($page_path && file_exists($page_path)) { include $page_path; } 
    else { echo "<h2>Content not found.</h2>"; }
    exit; // IMPORTANT: Stop execution after sending the content fragment.
}

// --- Pre-render Logic ---
// Handle form submissions for included pages before any HTML is output.
$section = $_GET['page'] ?? 'overview';

if ($section === 'manage' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // This file contains logic for update/delete and will redirect if successful.
    include __DIR__ . '/../api/courses/manage-logic.php';
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
    'overview' => ['icon' => 'fas fa-chart-pie', 'text' => 'Overview', 'gradient' => 'linear-gradient(135deg, #b915ff, #8b5cf6)'],
    'users' => ['icon' => 'fas fa-users-cog', 'text' => 'User Management', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],
    'create-course' => ['icon' => 'fas fa-plus-circle', 'text' => 'Create Course', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'create-lesson' => ['icon' => 'fas fa-file-alt', 'text' => 'Create Lesson', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'create-quiz' => ['icon' => 'fas fa-plus-circle', 'text' => 'Create Quiz', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'manage' => ['icon' => 'fas fa-book-open', 'text' => 'Course Management', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'manage-lessons' => ['icon' => 'fas fa-tasks', 'text' => 'Manage Lessons', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'manage-quizzes' => ['icon' => 'fas fa-tasks', 'text' => 'Manage Quizzes', 'gradient' => 'linear-gradient(135deg, #f97316, #ea580c)'],
    'instructors' => ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Instructors', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'students' => ['icon' => 'fas fa-user-graduate', 'text' => 'Students', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)'],
    'content' => ['icon' => 'fas fa-file-video', 'text' => 'Content', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'],
    'reports' => ['icon' => 'fas fa-chart-bar', 'text' => 'Analytics', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'communication' => ['icon' => 'fas fa-comments', 'text' => 'Communication', 'gradient' => 'linear-gradient(135deg, #f97316, #ea580c)'],
    'settings' => ['icon' => 'fas fa-cogs', 'text' => 'Settings', 'gradient' => 'linear-gradient(135deg, #64748b, #475569)'],
    'logout' => ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'gradient' => 'linear-gradient(135deg, #ef4444, #b91c1c)']
];

$instructorMenu = [
    'overview' => ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'gradient' => 'linear-gradient(135deg, #b915ff, #8b5cf6)'],
    'create-lesson' => ['icon' => 'fas fa-file-alt', 'text' => 'Create Lesson', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'manage-lessons' => ['icon' => 'fas fa-tasks', 'text' => 'Manage Lessons', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'create-quiz' => ['icon' => 'fas fa-plus-circle', 'text' => 'Create Quiz', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'my-courses' => ['icon' => 'fas fa-book-open', 'text' => 'Manage Courses', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'students' => ['icon' => 'fas fa-users', 'text' => 'Students', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],
    'assessments' => ['icon' => 'fas fa-tasks', 'text' => 'Assessments', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'analytics' => ['icon' => 'fas fa-chart-line', 'text' => 'Analytics', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'communication' => ['icon' => 'fas fa-comments', 'text' => 'Messages', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)'],
    'payouts' => ['icon' => 'fas fa-wallet', 'text' => 'Earnings', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'profile' => ['icon' => 'fas fa-user-edit', 'text' => 'Profile', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'],
    'logout' => ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'gradient' => 'linear-gradient(135deg, #ef4444, #b91c1c)']
];

$studentMenu = [
    'overview' => ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'gradient' => 'linear-gradient(135deg, #b915ff, #8b5cf6)'],
    'my-courses' => ['icon' => 'fas fa-book-reader', 'text' => 'My Courses', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'browse-courses' => ['icon' => 'fas fa-search', 'text' => 'Explore', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],
    'grades' => ['icon' => 'fas fa-graduation-cap', 'text' => 'Grades', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'certificates' => ['icon' => 'fas fa-award', 'text' => 'Certificates', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'profile' => ['icon' => 'fas fa-user-cog', 'text' => 'Profile', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'],
    'support' => ['icon' => 'fas fa-headset', 'text' => 'Support', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)'],
    'logout' => ['icon' => 'fas fa-sign-out-alt', 'text' => 'Logout', 'gradient' => 'linear-gradient(135deg, #ef4444, #b91c1c)']
];

// Active menu based on role
$activeMenu = ($userRole === 'admin') ? $adminMenu : (($userRole === 'instructor') ? $instructorMenu : $studentMenu);

// Function to generate sidebar links
function generateNavLinks($menu, $section) {
    $html = '';
    foreach ($menu as $page => $details) {
        $isActive = ($page === $section);
        $href = ($page === 'logout') ? 'api/auth/logout.php' : '?page=' . $page;
        $html .= '<li class="nav-item">
                    <a href="' . $href . '" 
                       data-section="' . $page . '" 
                       class="nav-link ' . ($isActive ? 'active' : '') . '"
                       style="--gradient: ' . $details['gradient'] . '">
                        <div class="nav-icon-wrapper">
                            <i class="' . $details['icon'] . ' nav-icon"></i>
                        </div>
                        <span class="nav-text">' . $details['text'] . '</span>
                        <div class="nav-ripple"></div>
                    </a>
                  </li>';
    }
    return $html;
}

// Function to generate mobile bottom navigation
function generateMobileNav($menu, $section) {
    $html = '';
    $count = 0;
    foreach ($menu as $page => $details) {
        if ($count >= 5) break; // Limit to 5 items for bottom nav
        $isActive = ($page === $section);
        $href = ($page === 'logout') ? 'api/auth/logout.php' : '?page=' . $page;
        $html .= '<a href="' . $href . '" 
                     data-section="' . $page . '" 
                     class="mobile-nav-item ' . ($isActive ? 'active' : '') . '"
                     style="--gradient: ' . $details['gradient'] . '">
                    <div class="mobile-nav-icon">
                        <i class="' . $details['icon'] . '"></i>
                        <div class="mobile-nav-indicator"></div>
                    </div>
                    <span class="mobile-nav-text">' . $details['text'] . '</span>
                  </a>';
        $count++;
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
    <title>UNIES - Premium Learning Experience</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        /* Style for page transitions */
        body.is-transitioning {
            opacity: 0 !important;
            transform: translateY(10px);
            transition: opacity 0.2s ease-out, transform 0.2s ease-out;
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles" id="particles"></div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <div class="mobile-nav-container">
            <?= generateMobileNav($activeMenu, $section) ?>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" id="fab">
        <i class="fas fa-plus"></i>
    </button>

    <div class="dashboard-wrapper">
        <!-- Premium Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="home" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Unies</span>
                </a>
            </div>

            <div class="user-profile">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if ($userAvatar): ?>
                            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 16px;">
                        <?php else: ?>
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        <?php endif; ?>
                        <div class="notification-badge">3</div>
                    </div>
                    <div class="user-details">
                        <h3><?= htmlspecialchars($userName) ?></h3>
                        <p><?= htmlspecialchars($userRole) ?></p>
                    </div>
                </div>
            </div>

            <!-- <div class="time-display">
                <div class="current-time" id="currentTime"></div>
                <div class="current-date" id="currentDate"></div>
            </div> -->

            <nav>
                <ul class="nav-list">
                    <?= generateNavLinks($activeMenu, $section) ?>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <div class="content-body" style="opacity: 1; transform: translateY(0px); transition: opacity 0.3s ease-out, transform 0.3s ease-out;">
                <?php
                // This code only runs on a full page load. AJAX calls are handled at the top.
                $page_map = [
                    'overview' => __DIR__ . "/../api/templates/overview.php",
                    'create-course' => __DIR__ . "/../api/courses/create.php",
                    'manage' => __DIR__ . "/../api/courses/manage.php",
                    'create-lesson' => __DIR__ . "/../api/lessons/create.php",
                    'manage-lessons' => __DIR__ . "/../api/lessons/manage.php",
                    'create-quiz' => __DIR__ . "/../api/quizzes/create.php",
                    'manage-quizzes' => __DIR__ . "/../api/quizzes/manage.php",
                    'users' => __DIR__ . "/../api/users/user-management.php",
                    'logout' => __DIR__ . "/../api/auth/logout.php",
                ];
                $page_path = $page_map[$section] ?? '';

                if ($page_path && file_exists($page_path)) {
                    include $page_path;
                } else {
                    // Default/fallback content for overview or not found pages
                    echo '<section id="overview" class="dashboard-section active">';
                    echo '<div class="stats-grid">
                            <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-value">12,847</div><div class="stat-label">Total Students</div></div>
                            <div class="stat-card"><div class="stat-icon"><i class="fas fa-book-open"></i></div><div class="stat-value">256</div><div class="stat-label">Active Courses</div></div>
                            <div class="stat-card"><div class="stat-icon"><i class="fas fa-award"></i></div><div class="stat-value">8,942</div><div class="stat-label">Certificates</div></div>
                            <div class="stat-card"><div class="stat-icon"><i class="fas fa-chart-line"></i></div><div class="stat-value">97.8%</div><div class="stat-label">Success Rate</div></div>
                        </div>';
                    if (file_exists(__DIR__ . "/../api/templates/overview.php")) {
                        include __DIR__ . "/../api/templates/overview.php"; 
                    }
                    echo '</section>';
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        // Create animated background particles
        function createParticles() {
            const particles = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particles.appendChild(particle);
            }
        }

        // Time Display with smooth updates
        function updateTime() {
            const now = new Date();
            const timeOptions = { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const dateOptions = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            };
            
            const timeElement = document.getElementById('currentTime');
            const dateElement = document.getElementById('currentDate');
            
            const newTime = now.toLocaleTimeString('en-US', timeOptions);
            const newDate = now.toLocaleDateString('en-US', dateOptions);
            
            if (timeElement.textContent !== newTime) {
                timeElement.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    timeElement.textContent = newTime;
                    timeElement.style.transform = 'scale(1)';
                }, 150);
            }
            
            if (dateElement.textContent !== newDate) {
                dateElement.textContent = newDate;
            }
        }

        // Ripple effect for navigation links
        function createRipple(event) {
            const button = event.currentTarget;
            const ripple = button.querySelector('.nav-ripple');
            
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.transform = 'scale(0)';
            ripple.style.opacity = '1';
            
            ripple.animate([
                { transform: 'scale(0)', opacity: 1 },
                { transform: 'scale(4)', opacity: 0 }
            ], {
                duration: 600,
                easing: 'ease-out'
            });
        }

        function initNavigation() {
            const allNavLinks = document.querySelectorAll(".nav-link, .mobile-nav-item");
            const contentBody = document.querySelector('.content-body');
            const contentTitle = document.getElementById('contentTitle'); // Assuming you have this element
            const activeMenu = <?= json_encode($activeMenu) ?>;

            const loadContent = (url, pushState = true) => {
                const mainContent = document.querySelector('.main-content');
                mainContent.classList.add('is-transitioning');

                fetch(url + (url.includes('?') ? '&' : '?') + 'ajax=true')
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(html => {
                        setTimeout(() => {
                            contentBody.innerHTML = html;

                            if (pushState) {
                                history.pushState({ path: url }, '', url);
                            }

                            // Update page title
                            const urlParams = new URL(url, window.location.origin);
                            const page = urlParams.searchParams.get('page') || 'overview';
                            const menuItem = activeMenu[page];
                            if (menuItem && contentTitle) {
                                contentTitle.textContent = menuItem.text;
                                contentTitle.setAttribute('data-text', menuItem.text);
                            }

                            // Re-initialize any scripts within the new content
                            const scripts = contentBody.querySelectorAll('script');
                            scripts.forEach(oldScript => {
                                const newScript = document.createElement('script');
                                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                            });

                            mainContent.classList.remove('is-transitioning');
                        }, 200);
                    })
                    .catch(error => {
                        console.error('Failed to load page content:', error);
                        contentBody.innerHTML = `<div class='text-center p-8'><h2 class='text-2xl font-bold text-red-600'>❌ Error loading content.</h2><p class='text-gray-400'>Please try again or refresh the page.</p></div>`;
                        mainContent.classList.remove('is-transitioning');
                    });
            };

            allNavLinks.forEach(link => {
                link.addEventListener("click", function(e) {
                    // Let logout links navigate normally, without using AJAX.
                    // This ensures the session is properly destroyed.
                    if (this.dataset.section === 'logout') {
                        return;
                    }

                    e.preventDefault();
                    const targetUrl = this.href;

                    if (window.location.href === targetUrl) {
                        return;
                    }

                    // Update active link style immediately
                    allNavLinks.forEach(l => l.classList.remove('active'));
                    const section = this.dataset.section;
                    document.querySelector(`.nav-link[data-section="${section}"]`)?.classList.add('active');
                    document.querySelector(`.mobile-nav-item[data-section="${section}"]`)?.classList.add('active');

                    loadContent(targetUrl);
                });
            });

            window.addEventListener("popstate", function(e) {
                const path = e.state ? e.state.path : window.location.href;
                loadContent(path, false);

                // Update active link on popstate
                const urlParams = new URL(path, window.location.origin);
                const page = urlParams.searchParams.get('page') || 'overview';
                allNavLinks.forEach(l => l.classList.remove('active'));
                document.querySelector(`.nav-link[data-section="${page}"]`)?.classList.add('active');
                document.querySelector(`.mobile-nav-item[data-section="${page}"]`)?.classList.add('active');
            });
        }

        // Smooth scrolling and enhanced UX
        function initEnhancements() {
            // Add hover sound effect simulation
            const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-item');
            navLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.filter = 'brightness(1.1)';
                });
                
                link.addEventListener('mouseleave', function() {
                    this.style.filter = 'brightness(1)';
                });
            });

            // Add parallax effect to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            
            function handleMouseMove(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(20px)`;
            }
            
            function handleMouseLeave() {
                this.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
            }
            
            statCards.forEach(card => {
                card.addEventListener('mousemove', handleMouseMove);
                card.addEventListener('mouseleave', handleMouseLeave);
            });
        }

        // Handle back/forward buttons
        window.addEventListener("popstate", function(e) {
            // This is now handled by the new initNavigation logic
        });

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            initNavigation();
            initEnhancements();
            
            // Start time updates
            // updateTime(); // You can re-enable this if the function exists
            // setInterval(updateTime, 500);
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            // Recreate particles on resize for better distribution
            const particles = document.getElementById('particles');
            if (particles) {
                particles.innerHTML = '';
                createParticles();
            }
        });

        // Performance optimization
        let ticking = false;
        
        function optimizedAnimationFrame(callback) {
            if (!ticking) {
                requestAnimationFrame(callback);
                ticking = true;
            }
        }
        
        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>