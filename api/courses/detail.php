<?php
// Path to the database connection file.
require_once __DIR__ . '/../../includes/db.php';

// Use the mysqli helper functions from db.php
global $conn;

// Get the course ID from the URL.
$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    echo "<div class='text-center text-red-600 mt-10'>Course ID is missing.</div>";
    exit;
}

// Fetch course details from the database using db_select helper function.
$course = db_select("SELECT c.*, u.name AS instructor_name, u.avatar, u.bio AS instructor_bio, cat.name AS category_name, uni.name AS university_name
                     FROM courses c
                     JOIN users u ON c.instructor_id = u.id
                     LEFT JOIN categories cat ON c.category_id = cat.id
                     LEFT JOIN universities uni ON c.university_id = uni.id
                     WHERE c.id = ?", 'i', [$course_id]);

$course = $course[0] ?? null;

if (!$course) {
    echo "<div class='text-center text-red-600 mt-10'>Course not found.</div>";
    exit;
}

// Fetch course curriculum (lessons, quizzes, assignments)
$curriculum_items = db_select("SELECT 'lesson' as type, title, description, duration, order_no FROM lessons WHERE course_id = ?
                                UNION ALL
                                SELECT 'quiz' as type, title, NULL as description, NULL as duration, NULL as order_no FROM quizzes WHERE course_id = ?
                                UNION ALL
                                SELECT 'assignment' as type, title, description, NULL as duration, NULL as order_no FROM assignments WHERE course_id = ?
                                ORDER BY order_no ASC, type DESC", 'iii', [$course_id, $course_id, $course_id]);

// Calculate total course duration (placeholder for a more complex calculation)
$total_duration = array_sum(array_column($curriculum_items, 'duration'));
$total_lectures = count(array_filter($curriculum_items, fn($item) => $item['type'] === 'lesson'));
$total_quizzes = count(array_filter($curriculum_items, fn($item) => $item['type'] === 'quiz'));
$total_assignments = count(array_filter($curriculum_items, fn($item) => $item['type'] === 'assignment'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <link rel="stylesheet" href="./">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #a855f7;
            --accent: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-primary: #0c0c0e;
            --bg-secondary: #111115;
            --bg-tertiary: #16161b;
            --surface: rgba(255, 255, 255, 0.05);
            --surface-hover: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --text-muted: #71717a;
            --glass-bg: rgba(17, 17, 21, 0.7);
            --glass-border: rgba(255, 255, 255, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Dot Grid Background */
        .dot-grid-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: var(--bg-primary);
            background-image: radial-gradient(var(--border) 1px, transparent 1px);
            background-size: 16px 16px;
        }

        /* Animated Gradient Overlay
        .animated-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(120, 119, 198, 0.3), transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(255, 119, 198, 0.3), transparent 50%),
                radial-gradient(ellipse at 0% 95%, rgba(232, 121, 249, 0.3), transparent 50%),
                radial-gradient(ellipse at 20% 20%, rgba(168, 85, 247, 0.2), transparent 50%),
                radial-gradient(ellipse at 80% 95%, rgba(99, 102, 241, 0.3), transparent 50%),
                radial-gradient(ellipse at 0% 20%, rgba(6, 182, 212, 0.2), transparent 50%);
            background-size: 400% 400%;
            animation: gradient-shift 15s ease infinite;
        } */

        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Glass morphism effects */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.37),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .glass-strong {
            background: rgba(17, 17, 21, 0.85);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        /* Hero section with animated elements */
        .hero-section {
            position: relative;
            min-height: 70vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .hero-floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .floating-shape {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        .floating-shape:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 8s;
        }

        .floating-shape:nth-child(2) {
            top: 60%;
            right: 15%;
            animation-delay: 2s;
            animation-duration: 10s;
        }

        .floating-shape:nth-child(3) {
            bottom: 30%;
            left: 70%;
            animation-delay: 4s;
            animation-duration: 12s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* Modern buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.4);
        }

        /* Advanced card hover effects */
        .modern-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
        }

        .modern-card:hover {
            transform: translateY(-8px);
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.1);
        }

        /* Animated progress indicators */
        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            stroke: var(--primary);
            stroke-linecap: round;
            transition: stroke-dashoffset 0.35s;
            transform-origin: 50% 50%;
        }

       

        /* Accordion improvements */
        .accordion-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .accordion-item:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.15);
        }

        .accordion-header {
            background: var(--surface);
            transition: all 0.3s ease;
            position: relative;
        }

        .accordion-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: linear-gradient(var(--primary), var(--secondary));
            transform: scaleY(0);
            transition: transform 0.3s ease;
            transform-origin: bottom;
        }

        .accordion-item:hover .accordion-header::before {
            transform: scaleY(1);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--bg-tertiary);
        }

        .accordion-content.active {
            max-height: 300px;
            padding: 1.5rem;
        }

        /* Micro-interactions */
        .interactive-element {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .interactive-element:hover {
            transform: scale(1.05);
        }

        .interactive-element:active {
            transform: scale(0.98);
        }

        /* Typography enhancements */
        .gradient-text {
            background: linear-gradient(135deg, #ffffff, #a1a1aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-text-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Responsive design improvements */
        @media (max-width: 768px) {
            .hero-section {
                min-height: 60vh;
                padding: 2rem 1rem;
            }
            
            .modern-card:hover {
                transform: translateY(-4px);
            }
        }
    </style>
</head>
<body>
    <div class="dot-grid-bg"></div>
    <div class="animated-overlay"></div>
    
    <!-- Hero Section -->
    <section class="relative z-10 py-20 px-6">
        <div class="container mx-auto max-w-7xl">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="space-y-8">
                    <div class="space-y-4">
                        <div class="inline-flex items-center px-4 py-2 rounded-full glass text-sm font-medium">
                            <i class="fas fa-star text-yellow-400 mr-2"></i>
                            <span class="gradient-text-primary">Premium Course</span>
                        </div>
                        <h1 class="text-5xl md:text-7xl font-black leading-tight gradient-text hero-title">
                            <?= htmlspecialchars($course['title']) ?>
                        </h1>
                        <p class="text-xl text-gray-300 leading-relaxed hero-subtitle">
                            <?= htmlspecialchars($course['subtitle'] ?? 'Transform your skills with cutting-edge knowledge') ?>
                        </p>
                    </div>
                    
                    <div class="flex flex-wrap gap-6 text-sm hero-meta">
                        <div class="flex items-center space-x-2 px-4 py-2 glass rounded-lg">
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>
                            <span class="text-gray-300"><?= htmlspecialchars($course['instructor_name']) ?></span>
                        </div>
                        <div class="flex items-center space-x-2 px-4 py-2 glass rounded-lg">
                            <div class="w-8 h-8 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-university text-white text-xs"></i>
                            </div>
                            <span class="text-gray-300"><?= htmlspecialchars($course['university_name'] ?? 'Independent') ?></span>
                        </div>
                        <div class="flex items-center space-x-2 px-4 py-2 glass rounded-lg">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-white text-xs"></i>
                            </div>
                            <span class="text-gray-300"><?= $total_duration ?> minutes</span>
                        </div>
                    </div>
                </div>
                
                <div class="relative hero-visual">
                    <div class="glass-strong rounded-3xl p-8 transform hover:scale-105 transition-all duration-500">
                        <img src="<?= htmlspecialchars($course['thumbnail'] ?? 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&h=600&fit=crop&crop=top') ?>" 
                             alt="<?= htmlspecialchars($course['title']) ?>"
                             class="w-full h-64 object-cover rounded-2xl mb-6 shadow-2xl">
                        
                        <div class="text-center space-y-4">
                            <div class="text-4xl font-black gradient-text-primary">
                                ৳<?= htmlspecialchars(number_format($course['price'], 2)) ?>
                            </div>
                            <a href="enroll?course_id=<?= $course_id ?>">
                                <button class="btn-primary w-full py-4 px-6 rounded-xl text-lg font-semibold text-white relative overflow-hidden group">
                                    <span class="relative z-10 flex items-center justify-center">
                                        <i class="fas fa-play mr-2"></i>
                                        Enroll Now
                                    </span>
                                </button>
                            </a>

                            <div class="flex items-center justify-center text-sm text-gray-400">
                                <i class="fas fa-shield-alt text-green-400 mr-2"></i>
                                30-day money-back guarantee
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto max-w-7xl px-6 py-20">
        <div class="grid lg:grid-cols-3 gap-12">
            <!-- Content Column -->
            <div class="lg:col-span-2 space-y-12">

                <!-- Instructor Section -->
                <section class="glass-strong rounded-3xl p-8 modern-card" id="instructor-section">
                    <div class="flex items-center mb-8">
                        <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-purple-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-user-graduate text-white"></i>
                        </div>
                        <h2 class="text-3xl font-bold gradient-text">Your Instructor</h2>
                    </div>
                    
                    <div class="flex flex-col md:flex-row gap-8 items-start">
                        <div class="flex-shrink-0">
                            <div class="relative group">
                                <div class="w-32 h-32 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 p-1">
                                    <img src="<?= htmlspecialchars($course['avatar'] ?? 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face') ?>" 
                                         alt="<?= htmlspecialchars($course['instructor_name']) ?>"
                                         class="w-full h-full object-cover rounded-xl">
                                </div>
                                <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-green-500 rounded-full border-4 border-gray-900 flex items-center justify-center">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex-1 space-y-4">
                            <div>
                                <h3 class="text-2xl font-bold text-white"><?= htmlspecialchars($course['instructor_name']) ?></h3>
                                <p class="text-purple-400 font-medium">Expert Instructor at <?= htmlspecialchars($course['university_name'] ?? 'Leading Institution') ?></p>
                            </div>
                            <p class="text-gray-300 leading-relaxed"><?= nl2br(htmlspecialchars($course['instructor_bio'] ?? 'This instructor is a leading expert in the field with years of professional experience and a passion for teaching.')) ?></p>
                        </div>
                    </div>
                </section>
                
                <!-- Learning Outcomes -->
                <section class="glass-strong rounded-3xl p-8 modern-card" id="learning-outcomes-section">
                    <div class="flex items-center mb-8">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-lightbulb text-white"></i>
                        </div>
                        <h2 class="text-3xl font-bold gradient-text">What You'll Master</h2>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="flex items-start space-x-4 group">
                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-1 group-hover:scale-110 transition-transform">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-gray-300 group-hover:text-white transition-colors">Master advanced concepts and methodologies</span>
                        </div>
                        <div class="flex items-start space-x-4 group">
                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-1 group-hover:scale-110 transition-transform">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-gray-300 group-hover:text-white transition-colors">Build portfolio-worthy projects</span>
                        </div>
                        <div class="flex items-start space-x-4 group">
                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-1 group-hover:scale-110 transition-transform">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-gray-300 group-hover:text-white transition-colors">Industry-standard tools and practices</span>
                        </div>
                        <div class="flex items-start space-x-4 group">
                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-1 group-hover:scale-110 transition-transform">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-gray-300 group-hover:text-white transition-colors">Career-ready certification</span>
                        </div>
                    </div>
                </section>

                <!-- Course Description -->
                <section class="glass-strong rounded-3xl p-8 modern-card" id="description-section">
                    <div class="flex items-center mb-8">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-info-circle text-white"></i>
                        </div>
                        <h2 class="text-3xl font-bold gradient-text">About This Course</h2>
                    </div>
                    <div class="prose prose-lg text-gray-300 max-w-none">
                        <p class="leading-relaxed"><?= nl2br(htmlspecialchars($course['description'])) ?></p>
                    </div>
                </section>

                <!-- Course Curriculum -->
                <section class="glass-strong rounded-3xl p-8 modern-card" id="curriculum-section">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mr-4">
                                <i class="fas fa-list text-white"></i>
                            </div>
                            <h2 class="text-3xl font-bold gradient-text">Course Curriculum</h2>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-6 mb-8">
                        <div class="text-center p-4 glass rounded-xl hover-lift">
                            <div class="text-2xl font-bold text-purple-400"><?= htmlspecialchars($total_lectures) ?></div>
                            <div class="text-sm text-gray-400">Lessons</div>
                        </div>
                        <div class="text-center p-4 glass rounded-xl hover-lift">
                            <div class="text-2xl font-bold text-yellow-400"><?= htmlspecialchars($total_quizzes) ?></div>
                            <div class="text-sm text-gray-400">Quizzes</div>
                        </div>
                        <div class="text-center p-4 glass rounded-xl hover-lift">
                            <div class="text-2xl font-bold text-cyan-400"><?= htmlspecialchars($total_assignments) ?></div>
                            <div class="text-sm text-gray-400">Projects</div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($curriculum_items)): ?>
                            <div class="text-center py-12 text-gray-400">
                                <i class="fas fa-book-open text-4xl opacity-50 mb-4"></i>
                                <p>Curriculum content coming soon...</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($curriculum_items as $index => $item): 
                                $icon_class = 'fas fa-play';
                                $gradient_class = 'from-purple-500 to-blue-500';
                                $border_color = 'border-purple-500/30';
                                
                                if ($item['type'] === 'quiz') {
                                    $icon_class = 'fas fa-question';
                                    $gradient_class = 'from-yellow-500 to-orange-500';
                                    $border_color = 'border-yellow-500/30';
                                } elseif ($item['type'] === 'assignment') {
                                    $icon_class = 'fas fa-code';
                                    $gradient_class = 'from-cyan-500 to-blue-500';
                                    $border_color = 'border-cyan-500/30';
                                }
                            ?>
                            <div class="accordion-item interactive-element">
                                <div class="accordion-header p-6 cursor-pointer flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-gradient-to-br <?= $gradient_class ?> rounded-lg flex items-center justify-center">
                                            <i class="<?= htmlspecialchars($icon_class) ?> text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-white"><?= htmlspecialchars($item['title']) ?></h3>
                                            <p class="text-sm text-gray-400 capitalize"><?= htmlspecialchars($item['type']) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <?php if ($item['duration'] > 0): ?>
                                            <span class="text-sm text-gray-400 font-mono"><?= htmlspecialchars($item['duration']) ?>min</span>
                                        <?php endif; ?>
                                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300 accordion-icon"></i>
                                    </div>
                                </div>
                                <div class="accordion-content">
                                    <div class="p-6 pt-0">
                                        <p class="text-gray-400 leading-relaxed">
                                            <?= nl2br(htmlspecialchars($item['description'] ?? 'Comprehensive content designed to enhance your understanding and practical skills.')) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Reviews Section -->
                <section class="glass-strong rounded-3xl p-8 modern-card" id="reviews-section">
                    <div class="flex items-center mb-8">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-star text-white"></i>
                        </div>
                        <h2 class="text-3xl font-bold gradient-text">Student Reviews</h2>
                    </div>
                    <div class="text-center py-12 text-gray-400">
                        <i class="fas fa-comments text-4xl opacity-50 mb-4"></i>
                        <p>No reviews yet. Be the first to leave one!</p>
                    </div>
                </section>
                
            </div>

            <!-- Sidebar / Enrollment -->
            <div class="lg:col-span-1 space-y-12">
                <div class="glass-strong rounded-3xl p-8 sticky-card modern-card" id="enrollment-section">
                    <img src="<?= htmlspecialchars($course['thumbnail'] ?? 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&h=600&fit=crop&crop=top') ?>" 
                         alt="<?= htmlspecialchars($course['title']) ?>"
                         class="w-full h-48 object-cover rounded-2xl mb-6 shadow-2xl">
                    
                    <div class="text-center space-y-4">
                        <div class="text-5xl font-black gradient-text-primary">
                            ৳<?= htmlspecialchars(number_format($course['price'], 2)) ?>
                        </div>
                        <button class="btn-primary w-full py-4 px-6 rounded-xl text-lg font-semibold text-white relative overflow-hidden group">
                            <span class="relative z-10 flex items-center justify-center">
                                <i class="fas fa-play mr-2"></i>
                                Enroll Now
                            </span>
                        </button>
                        <div class="flex items-center justify-center text-sm text-gray-400">
                            <i class="fas fa-shield-alt text-green-400 mr-2"></i>
                            30-day money-back guarantee
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- <script>
        document.addEventListener('DOMContentLoaded', () => {
            const accordionHeaders = document.querySelectorAll('.accordion-header');

            accordionHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const content = header.nextElementSibling;
                    const icon = header.querySelector('.accordion-icon');

                    const isExpanded = content.classList.contains('active');
                    
                    if (isExpanded) {
                        content.classList.remove('active');
                        icon.classList.remove('rotate');
                    } else {
                        document.querySelectorAll('.accordion-content.active').forEach(item => {
                            item.classList.remove('active');
                            item.previousElementSibling.querySelector('.accordion-icon').classList.remove('rotate');
                        });
                        content.classList.add('active');
                        icon.classList.add('rotate');
                    }
                });
            });

            gsap.registerPlugin(ScrollTrigger);

            const sections = document.querySelectorAll('section');
            sections.forEach(section => {
                gsap.fromTo(section, 
                    { opacity: 0, y: 50 },
                    { opacity: 1, y: 0, duration: 1, ease: "power2.out", 
                      scrollTrigger: {
                        trigger: section,
                        start: "top 85%",
                        toggleActions: "play none none none"
                    }
                });
            });

            gsap.from('.hero-title', { opacity: 0, y: -50, duration: 1.5, ease: "power4.out" });
            gsap.from('.hero-subtitle', { opacity: 0, y: -30, duration: 1.5, ease: "power4.out", delay: 0.2 });
            gsap.from('.hero-meta > div', { opacity: 0, y: 20, duration: 1, ease: "power2.out", stagger: 0.1, delay: 0.5 });
            gsap.from('.hero-visual', { opacity: 0, x: 50, duration: 1.5, ease: "power4.out", delay: 0.8 });
        });
    </script> -->
</body>
</html>
