<?php
// This file is included by web.php, so session is already started.

// Get course_id from URL
$course_id = $_GET['course_id'] ?? null;
 
if (!$course_id) {
    die("Course ID is missing.");
}


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User not logged in → show a styled page with a loader and redirect
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Required</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg-color: #0f172a;
                --text-color: #e2e8f0;
                --text-strong: #ffffff;
                --text-muted: #94a3b8;
                --glass-bg: rgba(17, 25, 40, 0.75);
                --glass-border: rgba(255, 255, 255, 0.125);
                --accent-color: #A435F0;
                --background-gradient-dots: #4f46e5;
                --background-container: #0f172a;
            }

           

            .dot-grid-bg {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: -1;
                background-color: var(--background-container);
                background-image: radial-gradient(var(--background-gradient-dots) 1px, transparent 1px);
                background-size: 20px 20px;
                animation: bg-pan 15s linear infinite;
            }

            @keyframes bg-pan {
                0% { background-position: 0% 0%; }
                100% { background-position: 100% 100%; }
            }

            .popup {
                background: var(--glass-bg);
                border: 1px solid var(--glass-border);
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
                color: var(--text-strong);
                padding: 2.5rem 3.5rem;
                border-radius: 1.25rem;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.4);
                z-index: 1000;
                max-width: 400px;
            }

            .loader {
                border: 4px solid rgba(255, 255, 255, 0.2);
                border-left-color: var(--accent-color);
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 1.5rem auto;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <div class="dot-grid-bg"></div>
        <div class="popup">
            <h2 class="text-2xl font-bold">Login Required</h2>
            <p class="mt-2 text-gray-300">Please login to enroll in this course.</p>
            <div class="loader"></div>
            <p class="text-sm text-gray-400">Redirecting to the login page...</p>
        </div>

        <script>
            // Auto redirect after 3 seconds
            setTimeout(() => {
                window.location.href = 'index.php?page=account';
            }, 3000);
        </script>
    </body>
    </html>
    <?php
    exit;
}

// User is logged in → redirect to payment page
// Assuming payment.php is in the root directory, as this file is included by index.php
header("Location: payment.php?course_id=$course_id");
exit;
