<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultra Modern Preloader</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0c0c0e 0%, #1a1a2e 50%, #16213e 100%);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 1.2s cubic-bezier(0.23, 1, 0.320, 1);
            visibility: visible;
            opacity: 1;
            overflow: hidden;
        }

        /* Animated background particles */
        .bg-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(185, 21, 255, 0.6);
            border-radius: 50%;
            animation: float 6s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }

        .preloader-content {
            text-align: center;
            position: relative;
            z-index: 10;
        }

        /* Main logo container */
        .logo-container {
            position: relative;
            margin-bottom: 2rem;
        }

        /* Rotating rings around logo */
        .ring {
            position: absolute;
            border: 2px solid transparent;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .ring-1 {
            width: 120px;
            height: 120px;
            border-top: 2px solid #b915ff;
            border-right: 2px solid rgba(185, 21, 255, 0.3);
            animation: spin 2s linear infinite;
        }

        .ring-2 {
            width: 160px;
            height: 160px;
            border-left: 2px solid #ff6b35;
            border-bottom: 2px solid rgba(255, 107, 53, 0.3);
            animation: spin 3s linear infinite reverse;
        }

        .ring-3 {
            width: 200px;
            height: 200px;
            border-top: 1px solid #00d4ff;
            border-right: 1px solid rgba(0, 212, 255, 0.3);
            animation: spin 4s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Main text styling */
        .preloader-text {
            font-family: 'Orbitron', monospace;
            font-size: 5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #b915ff, #ff6b35, #00d4ff, #b915ff);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 3s ease-in-out infinite, letter-glow 2s ease-in-out infinite alternate;
            text-shadow: 0 0 30px rgba(185, 21, 255, 0.5);
            letter-spacing: 0.5rem;
            position: relative;
            z-index: 5;
        }

        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @keyframes letter-glow {
            0% { 
                filter: brightness(1) contrast(1);
                transform: scale(1);
            }
            100% { 
                filter: brightness(1.2) contrast(1.1);
                transform: scale(1.02);
            }
        }

        /* Loading progress bar */
        .progress-container {
            width: 300px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            margin: 2rem auto;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #b915ff, #ff6b35, #00d4ff);
            border-radius: 2px;
            position: relative;
            transition: width 0.3s ease-out; /* Smooth transition for JS-driven width */
        }

        .progress-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Loading text */
        .loading-text {
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 300;
            letter-spacing: 0.2rem;
            animation: fade-pulse 2s ease-in-out infinite;
            text-transform: uppercase;
        }

        @keyframes fade-pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        /* Geometric shapes */
        .geo-shape {
            position: absolute;
            opacity: 0.1;
            animation: float-shapes 8s ease-in-out infinite;
        }

        .shape-1 {
            top: 20%;
            left: 15%;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #b915ff, transparent);
            transform: rotate(45deg);
            animation-delay: 0s;
        }

        .shape-2 {
            top: 70%;
            right: 20%;
            width: 40px;
            height: 40px;
            border: 2px solid #ff6b35;
            border-radius: 50%;
            animation-delay: 2s;
        }

        .shape-3 {
            bottom: 30%;
            left: 20%;
            width: 0;
            height: 0;
            border-left: 25px solid transparent;
            border-right: 25px solid transparent;
            border-bottom: 43px solid rgba(0, 212, 255, 0.3);
            animation-delay: 4s;
        }

        @keyframes float-shapes {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.1;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 0.3;
            }
        }

        /* Exit animation */
        #preloader.hidden {
            opacity: 0;
            visibility: hidden;
            transform: scale(0.8);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .preloader-text {
                font-size: 3rem;
                letter-spacing: 0.3rem;
            }
            
            .progress-container {
                width: 250px;
            }
            
            .ring-1 { width: 80px; height: 80px; }
            .ring-2 { width: 110px; height: 110px; }
            .ring-3 { width: 140px; height: 140px; }
        }

        /* Additional glow effects */
        .preloader-content::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(185, 21, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse-bg 4s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes pulse-bg {
            0%, 100% {
                transform: translate(-50%, -50%) scale(0.8);
                opacity: 0.3;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.2);
                opacity: 0.1;
            }
        }
    </style>
</head>
<body>
    <div id="preloader">
        <!-- Animated background particles -->
        <div class="bg-particles"></div>
        
        <!-- Geometric shapes -->
        <div class="geo-shape shape-1"></div>
        <div class="geo-shape shape-2"></div>
        <div class="geo-shape shape-3"></div>
        
        <div class="preloader-content">
            <div class="logo-container">
                <!-- Rotating rings -->
                <div class="ring ring-1"></div>
                <div class="ring ring-2"></div>
                <div class="ring ring-3"></div>
                
                <!-- Main logo text -->
                <h1 class="preloader-text">UNIES</h1>
            </div>
            
            <!-- Progress bar -->
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
            
            <!-- Loading text -->
            <p class="loading-text">Initializing Experience</p>
        </div>
    </div>

    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.querySelector('.bg-particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Initialize particles
        createParticles();

        // --- Preloader Logic ---
        const progressBar = document.querySelector('.progress-bar');
        const loadingText = document.querySelector('.loading-text');
        let progress = 0;
        
        const loadingStates = [
            'Initializing Experience',
            'Loading Assets',
            'Preparing Interface',
            'Almost Ready'
        ];
        
        // This interval simulates loading progress and will be cleared by the 'load' event.
        const loadingInterval = setInterval(() => {
            // Don't let the fake progress reach 100% on its own.
            // The 'load' event will be responsible for the final jump to 100%.
            if (progress < 90) {
                progress += Math.random() * 5;
                progressBar.style.width = progress + '%';
            }
            
            if (progress > 75) {
                loadingText.textContent = loadingStates[3];
            } else if (progress > 50) {
                loadingText.textContent = loadingStates[2];
            } else if (progress > 25) {
                loadingText.textContent = loadingStates[1];
            }
        }, 150);

        // Preloader exit logic - synchronized with page load
        window.addEventListener('load', function() {
            // The page is fully loaded. Now we can orchestrate the exit.
            
            // 1. Stop the fake progress and jump to 100%.
            clearInterval(loadingInterval);
            progressBar.style.width = '100%';
            loadingText.textContent = 'Ready';

            // 2. Wait a moment for the user to see the "100%" state, then hide.
            setTimeout(() => {
                const preloader = document.getElementById('preloader');
                if (preloader) {
                    preloader.classList.add('hidden');
                    
                    // 3. Remove from DOM after transition completes (1.2s in CSS)
                    setTimeout(() => {
                        preloader.remove();
                    }, 1200);
                }
            }, 400); // A short delay of 400ms for the final animation to be seen.
        });
    </script>
</body>
</html>