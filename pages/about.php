<?php
// This is an about us page, no server-side logic is needed here for now.
// PHP is included to maintain file extension consistency with contact.php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Premium Design</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0f172a;
            --text-color: #e2e8f0;
            --text-strong: #ffffff;
            --text-muted: #94a3b8;
            --glass-bg: rgba(17, 25, 40, 0.75);
            --glass-border: rgba(255, 255, 255, 0.125);
            --section-bg: rgba(0, 0, 0, 0.2);
            --accent-color: #A435F0;
            --accent-hover: #8e2ddb;
            --accent-ring: rgba(164, 53, 240, 0.6);
            --background-gradient-dots: #4f46e5;
            --background-container: #0f172a;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        .glass-effect {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
        }
        .section-divider {
            border-color: var(--glass-border);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }

        /* Animated Background Shapes */
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

        /* Scroll Reveal Animations */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(3rem);
            transition: opacity 0.8s cubic-bezier(0.5, 0, 0, 1), transform 0.8s cubic-bezier(0.5, 0, 0, 1);
        }
        .scroll-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class=" transition-colors duration-300">

    <div class="absolute inset-0 -z-10 h-full w-full bg-[var(--background-container)] bg-[radial-gradient(var(--background-gradient-dots)_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <ul class="floating-shapes">
        <li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li>
    </ul>

    <div class="relative min-h-screen flex items-center justify-center p-4 py-16">
        
        <div class="w-full max-w-4xl mx-auto animate-fade-in">
            
            <div class="glass-effect rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-8 md:p-12">
                    <!-- Hero Section -->
                    <section class="text-center">
                        <h1 class="text-4xl md:text-5xl font-extrabold text-[var(--text-strong)] leading-tight">We Providing The <span class="text-[var(--accent-color)]">Best&nbsp;Quality Online&nbsp;Courses</span></h1>
                        <p class="mt-4 max-w-2xl mx-auto text-[var(--text-muted)]">Not only maintaining high quality of content, but also providing enough high-quality learning resources so that you can ace your goals faster than light itself!</p>
                        <div class="mt-6 inline-block bg-[var(--section-bg)] text-[var(--text-color)] font-semibold px-6 py-3 rounded-lg border border-[var(--glass-border)]">Fall 2024-2025: Join to Get Up to Date Content Each Semester Based on Your Curriculum</div>
                    </section>

                    <!-- Stats Section -->
                    <section class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-8 text-center scroll-reveal">
                        <div>
                            <p class="text-4xl font-bold text-[var(--accent-color)]">3</p>
                            <p class="mt-1 text-[var(--text-muted)]">Departments</p>
                        </div>
                        <div>
                            <p class="text-4xl font-bold text-[var(--accent-color)]">238</p>
                            <p class="mt-1 text-[var(--text-muted)]">Total Students</p>
                        </div>
                        <div>
                            <p class="text-4xl font-bold text-[var(--accent-color)]">7</p>
                            <p class="mt-1 text-[var(--text-muted)]">Total Course</p>
                        </div>
                    </section>

                    <hr class="my-12 section-divider scroll-reveal">

                    <!-- Features Section -->
                    <section class="grid md:grid-cols-3 gap-8 text-left scroll-reveal">
                        <div class="bg-[var(--section-bg)] p-6 rounded-lg border border-[var(--glass-border)]">
                            <h3 class="font-bold text-lg text-[var(--text-strong)]">High Quality Courses</h3>
                            <p class="mt-2 text-sm text-[var(--text-muted)]">Covering each topic ensuring top learning experience and quality content for the students.</p>
                        </div>
                        <div class="bg-[var(--section-bg)] p-6 rounded-lg border border-[var(--glass-border)]">
                            <h3 class="font-bold text-lg text-[var(--text-strong)]">One Year Access</h3>
                            <p class="mt-2 text-sm text-[var(--text-muted)]">1 Year Access for a lengthy period of availability.</p>
                        </div>
                        <div class="bg-[var(--section-bg)] p-6 rounded-lg border border-[var(--glass-border)]">
                            <h3 class="font-bold text-lg text-[var(--text-strong)]">Expert Instructors</h3>
                            <p class="mt-2 text-sm text-[var(--text-muted)]">Top Students empowered by top teaching tools providing top class teaching only for you to ace.</p>
                        </div>
                    </section>

                    <hr class="my-12 section-divider scroll-reveal">

                    <!-- Fact Section -->
                    <section class="text-center bg-[var(--section-bg)] p-8 rounded-lg border border-[var(--glass-border)] scroll-reveal">
                        <p class="font-bold text-[var(--text-muted)]">Fact</p>
                        <h2 class="mt-2 text-2xl md:text-3xl font-bold text-[var(--text-strong)]">Unies, The <span class="text-[var(--accent-color)]">First</span> Ever to Work on University Course Solution Professionally</h2>
                        <p class="mt-4 max-w-3xl mx-auto text-[var(--text-muted)]">We, Unies, realized the inevitable need for boundless guidance for university students to ace University Grade at their will!</p>
                    </section>
                    
                    <!-- More Stats -->
                    <section class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-8 text-center scroll-reveal">
                         <div>
                            <p class="text-3xl font-bold text-[var(--text-strong)]">7+</p>
                            <p class="mt-1 text-sm text-[var(--text-muted)]">TOTAL COURSES</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-[var(--text-strong)]">450</p>
                            <p class="mt-1 text-sm text-[var(--text-muted)]">CLASS COMPLETED</p>
                        </div>
                         <div>
                            <p class="text-3xl font-bold text-[var(--text-strong)]">3+</p>
                            <p class="mt-1 text-sm text-[var(--text-muted)]">DEPARTMENTS</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-[var(--text-strong)]">100%</p>
                            <p class="mt-1 text-sm text-[var(--text-muted)]">SATISFACTION RATE</p>
                        </div>
                    </section>

                </div>

                <!-- Final CTA Section -->
                <section class="bg-[var(--section-bg)] p-8 text-center scroll-reveal">
                    <h2 class="text-2xl font-bold text-[var(--text-strong)]">Get Your Quality Skills Certificate Through Unies</h2>
                    <button class="mt-4 bg-[var(--accent-color)] hover:bg-[var(--accent-hover)] text-white font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[var(--bg-color)] focus:ring-[var(--accent-color)]">
                        Get started now
                    </button>
                </section>
            </div>
            
            <footer class="text-center mt-8 px-4 scroll-reveal">
                <p class="font-bold text-[var(--text-strong)]">Unies</p>
                <p class="text-[var(--text-muted)] text-sm mt-2">Trade License Details</p>
                <p class="text-[var(--text-muted)] text-xs">License Number: TRAD/DNCC/046447/2023</p>
                <p class="text-[var(--text-muted)] text-xs">Registered Address: 978/1, East Monipur, Mirpur-2, Dhaka-1216</p>
                 <p class="text-[var(--text-muted)] text-sm mt-4">&copy; <?php echo date("Y"); ?> Unies. All Rights Reserved.</p>
            </footer>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Scroll Reveal Logic ---
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1 // Trigger when 10% of the element is visible
            });

            const elementsToReveal = document.querySelectorAll('.scroll-reveal');
            elementsToReveal.forEach(el => observer.observe(el));
        });
    </script>

</body>
</html>

