<?php
// --- Pre-render Logic ---
// Handle form submissions for included pages before any HTML is output.
$section = $_GET['page'] ?? 'overview';

if ($section === 'manage' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // This file contains logic for update/delete and will redirect if successful.
    include __DIR__ . '/../api/courses/manage-logic.php';
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/account.php");
    exit;
}

// Role
$userRole = $_SESSION['user_role'] ?? 'student';

// Menus
$adminMenu = [
    'overview' => ['icon' => 'fas fa-chart-pie', 'text' => 'Overview', 'gradient' => 'linear-gradient(135deg, #b915ff, #8b5cf6)'],
    'users' => ['icon' => 'fas fa-users-cog', 'text' => 'User Management', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],
    'manage' => ['icon' => 'fas fa-book-open', 'text' => 'Course Management', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'create-course' => ['icon' => 'fas fa-plus-circle', 'text' => 'Create Course', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'instructors' => ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Instructors', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'students' => ['icon' => 'fas fa-user-graduate', 'text' => 'Students', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)'],
    'content' => ['icon' => 'fas fa-file-video', 'text' => 'Content', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'],
    'reports' => ['icon' => 'fas fa-chart-bar', 'text' => 'Analytics', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'communication' => ['icon' => 'fas fa-comments', 'text' => 'Communication', 'gradient' => 'linear-gradient(135deg, #f97316, #ea580c)'],
    'settings' => ['icon' => 'fas fa-cogs', 'text' => 'Settings', 'gradient' => 'linear-gradient(135deg, #64748b, #475569)']
];

$instructorMenu = [
    'overview' => ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'gradient' => 'linear-gradient(135deg, #b915ff, #8b5cf6)'],
    'my-courses' => ['icon' => 'fas fa-book-open', 'text' => 'My Courses', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'students' => ['icon' => 'fas fa-users', 'text' => 'Students', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],
    'assessments' => ['icon' => 'fas fa-tasks', 'text' => 'Assessments', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'analytics' => ['icon' => 'fas fa-chart-line', 'text' => 'Analytics', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'communication' => ['icon' => 'fas fa-comments', 'text' => 'Messages', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)'],
    'payouts' => ['icon' => 'fas fa-wallet', 'text' => 'Earnings', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
    'profile' => ['icon' => 'fas fa-user-edit', 'text' => 'Profile', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)']
];

$studentMenu = [
    'overview' => ['icon' => 'fas fa-home', 'text' => 'Dashboard', 'gradient' => 'linear-gradient(135deg, #b915ff, #8b5cf6)'],
    'my-courses' => ['icon' => 'fas fa-book-reader', 'text' => 'My Courses', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
    'browse-courses' => ['icon' => 'fas fa-search', 'text' => 'Explore', 'gradient' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)'],
    'grades' => ['icon' => 'fas fa-graduation-cap', 'text' => 'Grades', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
    'certificates' => ['icon' => 'fas fa-award', 'text' => 'Certificates', 'gradient' => 'linear-gradient(135deg, #84cc16, #65a30d)'],
    'profile' => ['icon' => 'fas fa-user-cog', 'text' => 'Profile', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)'],
    'support' => ['icon' => 'fas fa-headset', 'text' => 'Support', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)'],
    'logout' => ['icon' => 'fas fa-headset', 'text' => 'Logout', 'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)']
];

// Active menu based on role
$activeMenu = ($userRole === 'admin') ? $adminMenu : (($userRole === 'instructor') ? $instructorMenu : $studentMenu);

// Function to generate sidebar links
function generateNavLinks($menu, $section) {
    $html = '';
    foreach ($menu as $page => $details) {
        $isActive = ($page === $section);
        $html .= '<li class="nav-item">
                    <a href="?page=' . $page . '" 
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
        $html .= '<a href="?page=' . $page . '" 
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

            <div class="time-display">
                <div class="current-time" id="currentTime"></div>
                <div class="current-date" id="currentDate"></div>
            </div>

            <nav>
                <ul class="nav-list">
                    <?= generateNavLinks($activeMenu, $section) ?>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <div class="content-body">
                <!-- Overview Section -->
                <section id="overview" class="dashboard-section <?= ($section==='overview')?'active':'' ?>">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value">12,847</div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="stat-value">256</div>
                            <div class="stat-label">Active Courses</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <div class="stat-value">8,942</div>
                            <div class="stat-label">Certificates</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-value">97.8%</div>
                            <div class="stat-label">Success Rate</div>
                        </div>
                    </div>
                    <?php 
                    if (file_exists(__DIR__ . "/../api/templates/overview.php")) {
                        include __DIR__ . "/../api/templates/overview.php"; 
                    }
                    ?>
                </section>

                <!-- Create Course Section -->
                <section id="create-course" class="dashboard-section <?= ($section==='create-course')?'active':'' ?>">
                    <div class="stats-grid">
                       
                    </div>
                    <?php 
                    if (file_exists(__DIR__ . "/../api/courses/create.php")) {
                        include __DIR__ . "/../api/courses/create.php"; 
                    }
                    ?>
                </section>

             <section id="manage" class="dashboard-section <?= ($section==='manage')?'active':'' ?>">
                    <div class="stats-grid">
                       
                    </div>
                    <?php 
                    if (file_exists(__DIR__ . "/../api/courses/manage.php")) {
                        include __DIR__ . "/../api/courses/manage.php"; 
                    }
                    ?>
                </section>
               <section id="edit" class="dashboard-section <?= ($section==='edit')?'active':'' ?>">
                    <div class="stats-grid">
                       
                    </div>
                    <?php 
                    if (file_exists(__DIR__ . "/../api/courses/update.php")) {
                        include __DIR__ . "/../api/courses/update.php"; 
                    }
                    ?>
                </section>
                
                <!-- User Management Section -->
                <section id="users" class="dashboard-section <?= ($section==='users')?'active':'' ?>">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <div class="stat-value">Admin</div>
                            <div class="stat-label">User Control</div>
                        </div>
                    </div>
                    <?php 
                    if (file_exists(__DIR__ . "/../api/users/user-management.php")) {
                        include __DIR__ . "/../api/users/user-management.php"; 
                    }
                    ?>
                </section>

                <!-- Generate sections for all other menu items -->
               
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

        // Enhanced navigation handling
        function initNavigation() {
            const allNavLinks = document.querySelectorAll(".nav-link, .mobile-nav-item");
            
            allNavLinks.forEach(link => {
                // Add ripple effect to desktop nav
                if (link.classList.contains('nav-link')) {
                    link.addEventListener('click', createRipple);
                }
                
                link.addEventListener("click", function(e) {
                    e.preventDefault();
                    let target = this.getAttribute("data-section");

                    // Hide all sections with fade out
                    document.querySelectorAll(".dashboard-section").forEach(sec => {
                        sec.style.opacity = '0';
                        sec.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            sec.classList.remove("active");
                        }, 300);
                    });

                    // Show selected section with fade in
                    setTimeout(() => {
                        const targetSection = document.getElementById(target);
                        if (targetSection) {
                            targetSection.classList.add("active");
                            setTimeout(() => {
                                targetSection.style.opacity = '1';
                                targetSection.style.transform = 'translateY(0)';
                            }, 50);
                        }
                    }, 300);

                    // Update sidebar active state
                    document.querySelectorAll(".nav-link").forEach(l => l.classList.remove("active"));
                    document.querySelectorAll(".mobile-nav-item").forEach(l => l.classList.remove("active"));
                    
                    const desktopLink = document.querySelector('.nav-link[data-section="'+target+'"]');
                    const mobileLink = document.querySelector('.mobile-nav-item[data-section="'+target+'"]');
                    
                    if (desktopLink) desktopLink.classList.add("active");
                    if (mobileLink) mobileLink.classList.add("active");

                    // Update page title with glitch effect
                    const contentTitle = document.getElementById('contentTitle');
                    const activeMenu = <?= json_encode($activeMenu) ?>;
                    const menuItem = activeMenu[target];
                    
                    if (menuItem && contentTitle) {
                        contentTitle.style.opacity = '0';
                        setTimeout(() => {
                            contentTitle.textContent = menuItem.text;
                            contentTitle.setAttribute('data-text', menuItem.text);
                            contentTitle.style.opacity = '1';
                        }, 200);
                    }

                    // Update URL
                    history.pushState({section: target}, "", "?page=" + target);
                });
            });
        }

        // Floating Action Button
        function initFAB() {
            const fab = document.getElementById('fab');
            let rotation = 0;
            
            fab.addEventListener('click', function() {
                rotation += 45;
                this.style.transform = `translateY(-4px) scale(1.1) rotate(${rotation}deg)`;
                
                // Add some sparkle effect
                for (let i = 0; i < 6; i++) {
                    const sparkle = document.createElement('div');
                    sparkle.style.cssText = `
                        position: absolute;
                        width: 4px;
                        height: 4px;
                        background: #b915ff;
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 1000;
                    `;
                    
                    const rect = this.getBoundingClientRect();
                    sparkle.style.left = (rect.left + rect.width/2) + 'px';
                    sparkle.style.top = (rect.top + rect.height/2) + 'px';
                    
                    document.body.appendChild(sparkle);
                    
                    const angle = (i / 6) * Math.PI * 2;
                    const distance = 50;
                    
                    sparkle.animate([
                        { 
                            transform: 'translate(0, 0) scale(1)', 
                            opacity: 1 
                        },
                        { 
                            transform: `translate(${Math.cos(angle) * distance}px, ${Math.sin(angle) * distance}px) scale(0)`, 
                            opacity: 0 
                        }
                    ], {
                        duration: 800,
                        easing: 'ease-out'
                    }).onfinish = () => sparkle.remove();
                }
                
                setTimeout(() => {
                    this.style.transform = `translateY(-4px) scale(1.1) rotate(${rotation}deg)`;
                }, 100);
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
            let section = e.state?.section || "overview";

            // Hide all sections
            document.querySelectorAll(".dashboard-section").forEach(sec => {
                sec.style.opacity = '0';
                setTimeout(() => sec.classList.remove("active"), 300);
            });

            // Show correct section
            setTimeout(() => {
                const targetSection = document.getElementById(section);
                if (targetSection) {
                    targetSection.classList.add("active");
                    setTimeout(() => {
                        targetSection.style.opacity = '1';
                        targetSection.style.transform = 'translateY(0)';
                    }, 50);
                }
            }, 300);

            // Update navigation
            document.querySelectorAll(".nav-link, .mobile-nav-item").forEach(l => l.classList.remove("active"));
            
            const desktopLink = document.querySelector('.nav-link[data-section="'+section+'"]');
            const mobileLink = document.querySelector('.mobile-nav-item[data-section="'+section+'"]');
            
            if (desktopLink) desktopLink.classList.add("active");
            if (mobileLink) mobileLink.classList.add("active");

            // Update page title
            const contentTitle = document.getElementById('contentTitle');
            const activeMenu = <?= json_encode($activeMenu) ?>;
            const menuItem = activeMenu[section];
            
            if (menuItem && contentTitle) {
                contentTitle.textContent = menuItem.text;
                contentTitle.setAttribute('data-text', menuItem.text);
            }
        });

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            initNavigation();
            initFAB();
            initEnhancements();
            
            // Start time updates
            updateTime();
            setInterval(updateTime, 1000);
            
            // Initialize current section on page load
            const urlParams = new URLSearchParams(window.location.search);
            const currentPage = urlParams.get('page') || 'overview';
            
            const currentSection = document.getElementById(currentPage);
            if (currentSection) {
                document.querySelectorAll(".dashboard-section").forEach(sec => sec.classList.remove("active"));
                currentSection.classList.add("active");
                currentSection.style.opacity = '1';
                currentSection.style.transform = 'translateY(0)';
            }

            const desktopActiveLink = document.querySelector('.nav-link[data-section="'+currentPage+'"]');
            const mobileActiveLink = document.querySelector('.mobile-nav-item[data-section="'+currentPage+'"]');
            
            if (desktopActiveLink) {
                document.querySelectorAll(".nav-link").forEach(l => l.classList.remove("active"));
                desktopActiveLink.classList.add("active");
            }
            
            if (mobileActiveLink) {
                document.querySelectorAll(".mobile-nav-item").forEach(l => l.classList.remove("active"));
                mobileActiveLink.classList.add("active");
            }
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