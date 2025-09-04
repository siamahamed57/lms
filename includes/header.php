<?php
// Start the session to access session variables


// Check login status and user role from the session
$is_logged_in = $_SESSION['is_logged_in'] ?? false;
$user_role    = $_SESSION['user_role'] ?? null; 
// Auto-detect the current page to apply the 'active' class to the correct nav link
$current_page = basename($_SERVER['PHP_SELF'], ".php"); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNIES - Online Learning Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./assets/css/main.css">
    <style>
        /* Any custom styles from your original file would go here */
    </style>
</head>
<body data-theme="dark">

<header class="sticky top-0 z-50 glass-header"> 
    <div class="container mx-auto max-w-7xl px-4 py-4 flex justify-between items-center"> 
        <div class="logo"> 
            <a href="home" class="font-bold text-3xl text-[#b915ff] hover:text-[#9c00e6] transition-colors duration-300">UNIES</a> 
        </div> 

        <nav class="hidden lg:flex items-center space-x-8"> 
            <ul class="flex items-center space-x-8 relative"> 
                <li> 
                    <a href="home" class="nav-link relative text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200 <?= $current_page == 'home' ? 'active text-[#b915ff]' : '' ?>">Home</a> 
                </li> 
                <li> 
                    <a href="about" class="nav-link relative text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200 <?= $current_page == 'about' ? 'active text-[#b915ff]' : '' ?>">About</a> 
                </li> 
                <li class="relative group-hover-menu"> 
                    <a href="courses" class="nav-link relative text-card-color hover:text-[#b915ff] font-medium dropdown-toggle transition-colors duration-200">Courses</a> 
                    </li> 
                <li> 
                    <a href="contact" class="nav-link relative text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200 <?= $current_page == 'contact' ? 'active text-[#b915ff]' : '' ?>">Contact</a> 
                </li> 
            </ul> 
        </nav> 

        <div class="flex items-center space-x-6"> 
            <button id="theme-toggle" class="text-gray-400 hover:text-[#60a5fa] focus:outline-none transition-colors duration-300"> 
                <i id="theme-icon" class="fas fa-sun text-2xl"></i> 
            </button> 
            
            <div class="hidden lg:flex items-center space-x-2 relative user-profile cursor-pointer group-hover-menu"> 
                <span class="profile-icon"> 
                    <i class="fas fa-user-circle text-3xl text-[#b915ff] transition-colors duration-300"></i> 
                </span> 
                <ul id="profile-dropdown" class="dropdown-menu absolute top-full right-0 mt-4 w-56 rounded-xl overflow-hidden transition-all duration-300 transform scale-95 origin-top-right z-20"> 
                    <?php if ($is_logged_in): ?>
                        
                        <?php if ($user_role === 'admin'): ?>
                            <li><a href="dashboard" class="block px-6 py-3 text-card-color hover:bg-card-hover-bg transition-colors duration-200"><i class="fas fa-tools mr-2 text-[#b915ff]"></i> Admin Dashboard</a></li>
                            <li class="border-t border-gray-700"><a href="logout" class="block px-6 py-3 text-red-400 hover:bg-red-950 transition-colors duration-200"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a></li>

                        <?php elseif ($user_role === 'instructor'): ?>
                            <li><a href="dashboard.php" class="block px-6 py-3 text-card-color hover:bg-card-hover-bg transition-colors duration-200"><i class="fas fa-chalkboard-teacher mr-2 text-[#b915ff]"></i> Dashboard</a></li>
                            <li><a href="dashboard.php?page=my-courses" class="block px-6 py-3 text-card-color hover:bg-card-hover-bg transition-colors duration-200"><i class="fas fa-book mr-2 text-[#60a5fa]"></i> My Courses</a></li>
                            <li class="border-t border-gray-700"><a href="logout" class="block px-6 py-3 text-red-400 hover:bg-red-950 transition-colors duration-200"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a></li>

                        <?php elseif ($user_role === 'student'): ?>
                            <li><a href="dashboard" class="block px-6 py-3 text-card-color hover:bg-card-hover-bg transition-colors duration-200"><i class="fas fa-th-large mr-2 text-[#b915ff]"></i> Dashboard</a></li>
                            <li><a href="dashboard.php?page=my-courses" class="block px-6 py-3 text-card-color hover:bg-card-hover-bg transition-colors duration-200"><i class="fas fa-book-open mr-2 text-[#60a5fa]"></i> Enrolled Courses</a></li>
                            <li><a href="dashboard.php?page=certificates" class="block px-6 py-3 text-card-color hover:bg-card-hover-bg transition-colors duration-200"><i class="fas fa-award mr-2 text-yellow-500"></i> Certificates</a></li>
                            <li><a href="dashboard.php?page=profile" class="block px-6 py-3 text-card-color hover:bg-card-hover-bg transition-colors duration-200"><i class="fas fa-cog mr-2 text-gray-400"></i> Profile Settings</a></li>
                            <li class="border-t border-gray-700"><a href="logout" class="block px-6 py-3 text-red-400 hover:bg-red-950 transition-colors duration-200"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a></li>

                        <?php endif; ?>

                    <?php else: // User is not logged in ?>
                        <li><a href="account" class="block px-6 py-3 text-card-color hover:bg-card-hover-bg transition-colors duration-200"><i class="fas fa-sign-in-alt mr-2 text-green-500"></i> Login</a></li>
                        <li><a href="account" class="block px-6 py-3 text-card-color hover:bg-card-hover-bg transition-colors duration-200"><i class="fas fa-user-plus mr-2 text-[#60a5fa]"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </div> 
            
            <div class="lg:hidden"> 
                <button id="mobile-menu-btn" class="text-[#b915ff] text-3xl hover:text-[#60a5fa] focus:outline-none transition-colors duration-300"> 
                    <i class="fas fa-bars"></i> 
                </button> 
            </div> 
        </div> 
    </div> 
</header> 

<div id="mobile-menu-container" class="mobile-menu-container fixed top-0 right-0 w-80 max-w-full h-full bg-card-bg shadow-2xl z-[100] transform translate-x-full transition-transform duration-500 ease-in-out px-6 py-8"> 
    <div class="flex justify-between items-center mb-10"> 
        <a href="/home" class="font-bold text-3xl text-[#b915ff] hover:text-[#9c00e6] transition-colors duration-300">UNIES</a> 
        <button id="close-menu-btn" class="text-card-color hover:text-[#b915ff] text-3xl focus:outline-none transition-colors duration-200"> 
            <i class="fas fa-times"></i> 
        </button> 
    </div> 
    <ul class="space-y-6 text-xl"> 
        <li><a href="/home" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-home mr-3 text-sm text-[#b915ff]"></i>Home</a></li> 
        <li><a href="/about" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-info-circle mr-3 text-sm text-[#60a5fa]"></i>About</a></li> 
        <li> 
            <div class="accordion-header flex justify-between items-center cursor-pointer text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"> 
                <span><i class="fas fa-book mr-3 text-sm text-purple-400"></i>Courses</span> 
                <i class="fas fa-chevron-down text-sm transition-transform duration-300"></i> 
            </div> 
            <ul class="accordion-content space-y-3 pl-4 pt-4 hidden"> 
                <li><a href="/courses/aiub" class="block text-gray-400 hover:text-gray-200 transition-colors duration-200">AIUB</a></li> 
                <li><a href="/courses/nsu" class="block text-gray-400 hover:text-gray-200 transition-colors duration-200">NSU</a></li> 
                <li><a href="/courses/brac" class="block text-gray-400 hover:text-gray-200 transition-colors duration-200">BRAC</a></li> 
            </ul> 
        </li> 
        <li><a href="/contact" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-envelope mr-3 text-sm text-[#60a5fa]"></i>Contact</a></li> 

        <?php if ($is_logged_in): ?> 
            <li class="border-t pt-6 mt-6 border-gray-700"></li> 

            <?php if ($user_role === 'admin'): ?> 
                <li><a href="dashboard.php" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-tools mr-3 text-sm text-[#b915ff]"></i> Admin Dashboard</a></li> 
                <li><a href="/logout" class="block text-red-400 hover:text-red-300 font-medium transition-colors duration-200"><i class="fas fa-sign-out-alt mr-3 text-sm"></i> Logout</a></li> 

            <?php elseif ($user_role === 'instructor'): ?> 
                <li><a href="dashboard.php" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-chalkboard-teacher mr-3 text-sm text-[#b915ff]"></i> Dashboard</a></li> 
                <li><a href="dashboard.php?page=my-courses" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-book mr-3 text-sm text-[#60a5fa]"></i> My Courses</a></li> 
                <li><a href="/logout" class="block text-red-400 hover:text-red-300 font-medium transition-colors duration-200"><i class="fas fa-sign-out-alt mr-3 text-sm"></i> Logout</a></li> 

            <?php else: // Default to student if role is not admin or instructor ?> 
                <li><a href="dashboard.php" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-th-large mr-3 text-sm text-[#b915ff]"></i> Dashboard</a></li> 
                <li><a href="dashboard.php?page=my-courses" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-book-open mr-3 text-sm text-[#60a5fa]"></i> Enrolled Courses</a></li> 
                <li><a href="dashboard.php?page=certificates" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-award mr-3 text-sm text-yellow-500"></i> Certificates</a></li> 
                <li><a href="dashboard.php?page=profile" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-cog mr-3 text-sm text-gray-400"></i> Profile Settings</a></li> 
                <li><a href="/logout" class="block text-red-400 hover:text-red-300 font-medium transition-colors duration-200"><i class="fas fa-sign-out-alt mr-3 text-sm"></i> Logout</a></li> 
            <?php endif; ?> 

        <?php else: // User is not logged in ?> 
            <li class="border-t pt-6 mt-6 border-gray-700"></li> 
            <li><a href="account" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-sign-in-alt mr-3 text-sm text-green-500"></i> Login</a></li> 
            <li><a href="account" class="block text-card-color hover:text-[#b915ff] font-medium transition-colors duration-200"><i class="fas fa-user-plus mr-3 text-sm text-[#60a5fa]"></i> Register</a></li> 
        <?php endif; ?> 
    </ul> 
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const closeMenuBtn = document.getElementById('close-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu-container');

        // GSAP Timeline for Mobile Menu Animation
        const mobileTimeline = gsap.timeline({ paused: true, reversed: true });
        mobileTimeline.to(mobileMenu, {
            x: '0%',
            duration: 0.5,
            ease: 'power3.inOut'
        }).from('#mobile-menu-container ul li', {
            opacity: 0,
            x: 20,
            stagger: 0.05
        }, '-=0.3');

        mobileMenuBtn.addEventListener('click', () => {
            mobileTimeline.play();
        });

        closeMenuBtn.addEventListener('click', () => {
            mobileTimeline.reverse();
        });

        // GSAP for Desktop Dropdowns
        const profileTrigger = document.querySelector('.user-profile');
        const profileMenu = document.getElementById('profile-dropdown');

        const profileTimeline = gsap.timeline({ paused: true, reversed: true });
        profileTimeline.to(profileMenu, {
            opacity: 1,
            scale: 1,
            visibility: 'visible',
            pointerEvents: 'auto',
            duration: 0.2
        });

        let profileTimeout;

        profileTrigger.addEventListener('mouseenter', () => {
            clearTimeout(profileTimeout);
            profileTimeline.play();
        });
        profileTrigger.addEventListener('mouseleave', () => {
            profileTimeout = setTimeout(() => profileTimeline.reverse(), 200);
        });
        
        // Mobile Accordion for Courses
        const accordionHeader = document.querySelector('.accordion-header');
        const accordionContent = document.querySelector('.accordion-content');
        const accordionIcon = accordionHeader.querySelector('i.fa-chevron-down');

        accordionHeader.addEventListener('click', () => {
            if (accordionContent.classList.contains('hidden')) {
                accordionContent.classList.remove('hidden');
                gsap.fromTo(accordionContent.children, { opacity: 0, y: -10 }, { opacity: 1, y: 0, stagger: 0.1, duration: 0.3 });
                gsap.to(accordionIcon, { rotate: 180, duration: 0.3 });
            } else {
                gsap.to(accordionIcon, { rotate: 0, duration: 0.3 });
                gsap.to(accordionContent.children, {
                    opacity: 0,
                    y: -10,
                    stagger: {
                        each: 0.1,
                        from: 'end'
                    },
                    onComplete: () => accordionContent.classList.add('hidden')
                });
            }
        });

        // Theme Toggle Logic
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        // Load theme from localStorage on page load
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.body.dataset.theme = storedTheme;
            themeIcon.classList.remove('fa-sun', 'fa-moon');
            themeIcon.classList.add(storedTheme === 'dark' ? 'fa-sun' : 'fa-moon');
        }

        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.body.dataset.theme;
            if (currentTheme === 'dark') {
                document.body.dataset.theme = 'light';
                localStorage.setItem('theme', 'light');
                gsap.to(themeIcon, { rotate: 360, duration: 0.5, onComplete: () => themeIcon.classList.replace('fa-sun', 'fa-moon') });
            } else {
                document.body.dataset.theme = 'dark';
                localStorage.setItem('theme', 'dark');
                gsap.to(themeIcon, { rotate: -360, duration: 0.5, onComplete: () => themeIcon.classList.replace('fa-moon', 'fa-sun') });
            }
        });
    });
</script>