<?php
// Path to the database connection file (if needed for dynamic data in the future).
// require_once __DIR__ . '/../includes/db.php';
// For this template, data is hardcoded as per the request.
// In a real application, you would fetch courses, departments, etc., from the database here.
// e.g., $featured_courses = db_select("SELECT * FROM courses WHERE is_featured = 1 LIMIT 4");
?>

<!-- Additional fonts and scripts for the home page -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

<style>
    /* Page-specific styles for home.php */
    /* The main theme variables (dark/light) are now inherited from header.php and main.css */
    :root, [data-theme="dark"] {
        --background-gradient-dots: #4f46e5;
        --background-container: #0f172a;
    }

    [data-theme="light"] {
        --background-gradient-dots: #818cf8;
        --background-container: #e2e8f0;
    }

        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
        }

    .glass-strong {
        background: var(--surface, rgba(22, 22, 27, 0.8));
        backdrop-filter: blur(30px);
        -webkit-backdrop-filter: blur(30px);
        border: 1px solid var(--border, rgba(255, 255, 255, 0.15));
    }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.3);
        }

        .modern-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modern-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            border-color: var(--primary, #6366f1);
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--text-primary, #ffffff), var(--text-secondary, #a1a1aa));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-text-primary {
            background: linear-gradient(135deg, var(--primary, #6366f1), var(--secondary, #a855f7));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Accordion Styles */
        .accordion-item {
            border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.1));
            transition: all 0.3s ease;
        }
        .accordion-header {
            transition: background 0.3s ease;
        }
        .accordion-header:hover {
            background: var(--surface);
        }
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .accordion-icon.rotate {
            transform: rotate(180deg);
        }

    /* Animated Background Shapes from about.php */
    .floating-shapes {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: -1;
    }
    .floating-shapes li {
        position: absolute;
        display: block;
        list-style: none;
        width: 20px;
        height: 20px;
        background: rgba(164, 53, 240, 0.2);
        animation: animate-bg 25s linear infinite;
        bottom: -200px;
    }
    .floating-shapes li:nth-child(1){ left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
    .floating-shapes li:nth-child(2){ left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
    .floating-shapes li:nth-child(3){ left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
    .floating-shapes li:nth-child(4){ left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
    .floating-shapes li:nth-child(5){ left: 65%; width: 20px; height: 20px; animation-delay: 0s; }
    .floating-shapes li:nth-child(6){ left: 75%; width: 110px; height: 110px; animation-delay: 3s; }
    .floating-shapes li:nth-child(7){ left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
    .floating-shapes li:nth-child(8){ left: 50%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s; }
    .floating-shapes li:nth-child(9){ left: 20%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s; }
    .floating-shapes li:nth-child(10){ left: 85%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s; }

    @keyframes animate-bg {
        0% { transform: translateY(0) rotate(0deg); opacity: 1; border-radius: 0; }
        100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; border-radius: 50%; }
    }
    </style>

<div class="absolute inset-0 -z-10 h-full w-full bg-[var(--background-container)] bg-[radial-gradient(var(--background-gradient-dots)_1px,transparent_1px)] [background-size:16px_16px]"></div>
<ul class="floating-shapes">
    <li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li>
</ul>
<section class="relative z-10 py-24 px-6 text-center overflow-hidden">
        <div class="container mx-auto max-w-5xl">
            <h1 class="text-5xl md:text-8xl font-black uppercase text-[var(--text-primary)] tracking-wider hero-title">WELCOME TO <span class="gradient-text-primary">UNIES</span></h1>
            <p class="text-xl md:text-2xl mt-4 text-[var(--text-secondary)] hero-subtitle">Your Compass For University Success</p>
            <p class="mt-6 max-w-2xl mx-auto text-[var(--text-muted)] hero-p">Everything You Need in One Platform. Say goodbye to the endless search for curriculum-based content.</p>

            <div class="mt-12 flex flex-wrap justify-center items-center gap-4 hero-unis">
                <?php
                $universities = ['AIUB', 'BRAC', 'NSU', 'EWU', 'DIU', 'IUB'];
                foreach ($universities as $uni) :
                ?>
                    <div class="glass rounded-xl px-5 py-3 text-lg font-bold text-gray-300 transition-all duration-300 hover:text-white hover:border-purple-500 cursor-pointer">
                        <?= htmlspecialchars($uni) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <main class="container mx-auto max-w-7xl px-6 py-16 space-y-24">

        <section class="courses-section">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold gradient-text">Top Courses for Spring 2024-2025</h2>
                <p class="text-[var(--text-secondary)] mt-2">Curated for CSE & EEE Departments</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                $courses = [
                    ['title' => 'Object Oriented Programming (JAVA)', 'students' => '85+', 'price' => '899', 'thumbnail' => 'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=600&q=80'],
                    ['title' => 'Introduction To Programming Using C++', 'students' => '70+', 'price' => '699', 'thumbnail' => 'https://images.unsplash.com/photo-1605379399642-870262d3d051?w=600&q=80'],
                    ['title' => 'Physics 1', 'students' => '49+', 'price' => '449', 'thumbnail' => 'https://images.unsplash.com/photo-1532187643623-dbf2f5a73b13?w=600&q=80'],
                    ['title' => 'Introduction To Electrical Circuits', 'students' => '50+', 'price' => '699', 'thumbnail' => 'https://images.unsplash.com/photo-1581092921462-420005a4d4b8?w=600&q=80'],
                    ['title' => 'CSE Fresher Pack', 'students' => '25+', 'price' => '1249', 'thumbnail' => 'https://images.unsplash.com/photo-1550439062-609e1531270e?w=600&q=80'],
                    ['title' => 'EEE Fresher Pack', 'students' => '80+', 'price' => '1149', 'thumbnail' => 'https://images.unsplash.com/photo-1517420704952-d9f39e95b43e?w=600&q=80'],
                ];
                foreach ($courses as $course) :
                ?>
                    <div class="glass-strong rounded-3xl p-6 modern-card flex flex-col">
                        <img src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['title']) ?>" class="w-full h-40 object-cover rounded-2xl mb-5">
                        <h3 class="text-xl font-bold text-[var(--text-primary)] flex-grow"><?= htmlspecialchars($course['title']) ?></h3>
                        <div class="flex justify-between items-center mt-4 text-[var(--text-secondary)]">
                            <span><i class="fas fa-users mr-2 text-primary"></i><?= htmlspecialchars($course['students']) ?> Students</span>
                            <span class="text-2xl font-black text-[var(--text-primary)]">৳<?= htmlspecialchars($course['price']) ?></span>
                        </div>
                        <a href="#" class="btn-primary w-full text-center py-3 px-6 mt-5 rounded-xl font-semibold text-[var(--text-primary)]">
                            View Course <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="departments-section">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold gradient-text">Available Departments</h2>
                <p class="text-[var(--text-secondary)] mt-2">More departments coming soon!</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <?php
                $departments = [
                    ['name' => 'CSE', 'status' => '7 Courses', 'icon' => 'fa-laptop-code', 'color' => 'from-purple-500 to-indigo-500'],
                    ['name' => 'EEE', 'status' => '4 Courses', 'icon' => 'fa-bolt', 'color' => 'from-cyan-500 to-blue-500'],
                    ['name' => 'BBA', 'status' => 'On Production', 'icon' => 'fa-chart-line', 'color' => 'from-yellow-500 to-orange-500'],
                    ['name' => 'IPE', 'status' => 'Upcoming', 'icon' => 'fa-industry', 'color' => 'from-gray-500 to-gray-600'],
                    ['name' => 'Pharmacy', 'status' => 'On Production', 'icon' => 'fa-pills', 'color' => 'from-green-500 to-emerald-500'],
                ];
                foreach ($departments as $dept) :
                ?>
                    <div class="glass-strong rounded-3xl p-6 text-center modern-card cursor-pointer">
                        <div class="w-20 h-20 mx-auto bg-gradient-to-br <?= htmlspecialchars($dept['color']) ?> rounded-2xl flex items-center justify-center mb-4">
                            <i class="fas <?= htmlspecialchars($dept['icon']) ?> text-3xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-[var(--text-primary)]"><?= htmlspecialchars($dept['name']) ?></h3>
                        <p class="text-[var(--text-secondary)]"><?= htmlspecialchars($dept['status']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="cta-section glass-strong rounded-3xl p-12 modern-card">
            <div class="grid md:grid-cols-2 gap-10 items-center">
                <div>
                    <p class="font-semibold gradient-text-primary">FALL 2024-2025</p>
                    <h2 class="text-4xl font-bold text-[var(--text-primary)] mt-2">Join to Get Up-to-Date Content Each Semester</h2>
                    <p class="text-[var(--text-secondary)] mt-4">We align with your university's curriculum, ensuring you get the most relevant materials to excel in your exams and projects.</p>
                </div>
                <div class="grid grid-cols-3 gap-6 text-center">
                    <div>
                        <span class="text-5xl font-black gradient-text-primary">3</span>
                        <p class="text-[var(--text-secondary)] mt-1">Departments</p>
                    </div>
                    <div>
                        <span class="text-5xl font-black gradient-text-primary">715+</span>
                        <p class="text-[var(--text-secondary)] mt-1">Students</p>
                    </div>
                    <div>
                        <span class="text-5xl font-black gradient-text-primary">9</span>
                        <p class="text-[var(--text-secondary)] mt-1">Total Courses</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="faq-section">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold gradient-text">Frequently Asked Questions</h2>
            </div>
            <div class="max-w-4xl mx-auto glass-strong rounded-3xl p-8">
                <div class="accordion-item">
                    <div class="accordion-header p-6 cursor-pointer flex justify-between items-center">
                        <h3 class="text-lg font-semibold">What is UNIES?</h3>
                        <i class="fas fa-chevron-down transition-transform duration-300 accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <p class="p-6 pt-0 text-[var(--text-secondary)]">UNIES is an online learning platform designed specifically for university students. We offer a variety of courses, resources, and tools to help you succeed in your academic journey and beyond.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header p-6 cursor-pointer flex justify-between items-center">
                        <h3 class="text-lg font-semibold">What kind of courses do you offer?</h3>
                        <i class="fas fa-chevron-down transition-transform duration-300 accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <p class="p-6 pt-0 text-[var(--text-secondary)]">We offer curriculum-based courses for major departments like CSE, EEE, BBA, and more. Our content is tailored to match what you're learning in your university classes each semester.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header p-6 cursor-pointer flex justify-between items-center">
                        <h3 class="text-lg font-semibold">Who are your instructors?</h3>
                        <i class="fas fa-chevron-down transition-transform duration-300 accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <p class="p-6 pt-0 text-[var(--text-secondary)]">Our instructors are experienced professionals and senior students who have excelled in these subjects. They understand the curriculum and know how to teach concepts effectively for university-level exams.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header p-6 cursor-pointer flex justify-between items-center">
                        <h3 class="text-lg font-semibold">How much do your courses cost?</h3>
                        <i class="fas fa-chevron-down transition-transform duration-300 accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <p class="p-6 pt-0 text-[var(--text-secondary)]">Our courses are priced affordably for university students. We also offer "Fresher Packs" which provide great value by bundling multiple essential courses for a new semester.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="text-center py-16">
             <h2 class="text-3xl font-bold text-white">Feel free to reach out Anytime</h2>
             <p class="text-[var(--text-secondary)] mt-2">At Unies, we're here for you. Just drop us a line anytime!</p>
             <button class="btn-primary py-3 px-8 mt-6 rounded-xl text-lg font-semibold text-[var(--text-primary)]">
                Contact Us
             </button>
        </section>

    </main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Accordion Logic
        const accordionHeaders = document.querySelectorAll('.accordion-header');
        accordionHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const content = header.nextElementSibling;
                const icon = header.querySelector('.accordion-icon');
                const isExpanded = content.style.maxHeight && content.style.maxHeight !== '0px';

                // Close all other items
                document.querySelectorAll('.accordion-content').forEach(item => {
                    item.style.maxHeight = '0px';
                    const prevIcon = item.previousElementSibling.querySelector('.accordion-icon');
                    if (prevIcon) {
                            prevIcon.classList.remove('rotate');
                    }
                });

                // Open the clicked item if it was closed
                if (!isExpanded) {
                    content.style.maxHeight = content.scrollHeight + "px";
                    if (icon) {
                        icon.classList.add('rotate');
                    }
                }
            });
        });

        // GSAP Animations
        gsap.registerPlugin(ScrollTrigger);

        gsap.from('.hero-title', { opacity: 0, y: -40, duration: 1.2, ease: "power4.out" });
        gsap.from('.hero-subtitle', { opacity: 0, y: -30, duration: 1.2, ease: "power4.out", delay: 0.2 });
        gsap.from('.hero-p', { opacity: 0, y: -20, duration: 1.2, ease: "power4.out", delay: 0.4 });
        gsap.from('.hero-unis > div', { opacity: 0, y: 30, duration: 1, ease: "power2.out", stagger: 0.1, delay: 0.6 });

        const animateUp = (elem) => {
                gsap.fromTo(elem, 
                { opacity: 0, y: 50 },
                { opacity: 1, y: 0, duration: 1, ease: "power2.out", 
                    scrollTrigger: {
                    trigger: elem,
                    start: "top 85%",
                    toggleActions: "play none none none"
                    }
                });
        };

        document.querySelectorAll('section > div > h2, .modern-card, .faq-section > div').forEach(sectionEl => {
            animateUp(sectionEl);
        });
    });
</script>