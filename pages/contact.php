<?php
// Define variables and set to empty values
$nameErr = $emailErr = $subjectErr = $messageErr = "";
$name = $email = $subject = $message = "";
$form_submitted_successfully = false;

// --- Form Submission Logic ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $has_error = false;

    // Validate Name
    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
        $has_error = true;
    } else {
        $name = test_input($_POST["name"]);
        if (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
            $nameErr = "Only letters and white space allowed";
            $has_error = true;
        }
    }

    // Validate Email
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
        $has_error = true;
    } else {
        $email = test_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
            $has_error = true;
        }
    }
    
    // Validate Subject
    if (empty($_POST["subject"])) {
        $subjectErr = "Subject is required";
        $has_error = true;
    } else {
        $subject = test_input($_POST["subject"]);
    }

    // Validate Message
    if (empty($_POST["message"])) {
        $messageErr = "Message is required";
        $has_error = true;
    } else {
        $message = test_input($_POST["message"]);
    }

    // --- If no errors, process the form ---
    if (!$has_error) {
        // In a real application, you would send an email here.
        // For example:
        // $to = "your-email@example.com";
        // $headers = "From: " . $email;
        // $email_subject = "Contact Form Submission: " . $subject;
        // mail($to, $email_subject, $message, $headers);
        
        $form_submitted_successfully = true;
        $name = $email = $subject = $message = "";
    }
}

// Function to sanitize input data
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Premium Design</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root, [data-theme="dark"] {
            --bg-color: #0f172a;
            --text-color: #e2e8f0;
            --text-strong: #ffffff;
            --text-muted: #94a3b8;
            --text-error: #fca5a5;
            --glass-bg: rgba(17, 25, 40, 0.75);
            --glass-border: rgba(255, 255, 255, 0.125);
            --info-panel-bg: rgba(0, 0, 0, 0.2);
            --input-bg: rgba(0, 0, 0, 0.3);
            --input-border: rgba(255, 255, 255, 0.2);
            --input-focus-bg: rgba(0, 0, 0, 0.4);
            --input-placeholder: #64748b;
            --accent-color: #A435F0;
            --accent-hover: #8e2ddb;
            --accent-ring: rgba(164, 53, 240, 0.6);
            --success-bg: rgba(56, 189, 248, 0.1);
            --success-border: rgba(56, 189, 248, 0.3);
            --background-gradient-dots: #4f46e5;
            --background-container: #0f172a;
        }

        [data-theme="light"] {
            --bg-color: #f1f5f9;
            --text-color: #1e293b;
            --text-strong: #000000;
            --text-muted: #475569;
            --text-error: #ef4444;
            --glass-bg: rgba(255, 255, 255, 0.6);
            --glass-border: rgba(0, 0, 0, 0.1);
            --info-panel-bg: rgba(255, 255, 255, 0.5);
            --input-bg: rgba(226, 232, 240, 0.5);
            --input-border: #cbd5e1;
            --input-focus-bg: #ffffff;
            --input-placeholder: #94a3b8;
            --success-bg: rgba(14, 165, 233, 0.1);
            --success-border: rgba(14, 165, 233, 0.3);
            --background-gradient-dots: #818cf8;
            --background-container: #e2e8f0;
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
        .form-input {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        .form-input::placeholder {
            color: var(--input-placeholder);
        }
        .form-input:focus {
            background-color: var(--input-focus-bg);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px var(--accent-ring);
        }
        .error-message {
            color: var(--text-error);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .success-message-bg {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
    </style>
</head>
<body class="transition-colors duration-300">

    <div class="absolute inset-0 -z-10 h-full w-full bg-[var(--background-container)] bg-[radial-gradient(var(--background-gradient-dots)_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="absolute top-0 left-0 w-full h-full bg-cover bg-center opacity-30" style="background-image: url('https://placehold.co/1920x1080/0f172a/333333?text=.')"></div>

    <button id="theme-toggle" type="button" class="absolute top-4 right-4 p-2 rounded-lg glass-effect text-[var(--text-color)] hover:bg-black/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[var(--bg-color)] focus:ring-[var(--accent-color)]">
        <svg id="theme-icon-dark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
        <svg id="theme-icon-light" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
    </button>

    <div class="relative min-h-screen flex items-center justify-center p-4">
        
        <div class="w-full max-w-4xl mx-auto animate-fade-in">
            
            <div class="glass-effect rounded-2xl shadow-2xl overflow-hidden">
                <div class="grid md:grid-cols-2">
                    <!-- Left Side: Contact Info -->
                    <div class="p-8 bg-[var(--info-panel-bg)]">
                        <h2 class="text-3xl font-bold text-[var(--text-strong)] mb-4">Contact Information</h2>
                        <p class="text-[var(--text-muted)] mb-8">Fill up the form and our Team will get back to you within 24 hours.</p>

                        <div class="space-y-6">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-purple-400 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                <span class="text-[var(--text-color)]">+0123 4567 8910</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-purple-400 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span class="text-[var(--text-color)]">hello@example.com</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-purple-400 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span class="text-[var(--text-color)]">123 Street Name, City, Country</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Form -->
                    <div class="p-8">
                        <?php if ($form_submitted_successfully): ?>
                            <div class="success-message-bg text-center p-4 rounded-lg mb-6">
                                <p class="font-semibold text-[var(--text-strong)]">✅ Thank you for your message!</p>
                                <p class="text-[var(--text-muted)] text-sm">We'll get back to you shortly.</p>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" novalidate>
                            <div class="space-y-6">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="w-5 h-5 text-[var(--text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name);?>" class="form-input w-full pl-10 pr-4 py-3 rounded-lg outline-none" placeholder="Full Name">
                                    <?php if ($nameErr): ?><p class="error-message"><?php echo $nameErr; ?></p><?php endif; ?>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="w-5 h-5 text-[var(--text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12A4 4 0 108 12a4 4 0 008 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </div>
                                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email);?>" class="form-input w-full pl-10 pr-4 py-3 rounded-lg outline-none" placeholder="Email Address">
                                    <?php if ($emailErr): ?><p class="error-message"><?php echo $emailErr; ?></p><?php endif; ?>
                                </div>
                                <div class="relative">
                                     <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="w-5 h-5 text-[var(--text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                    </div>
                                    <input type="text" name="subject" id="subject" value="<?php echo htmlspecialchars($subject);?>" class="form-input w-full pl-10 pr-4 py-3 rounded-lg outline-none" placeholder="Subject">
                                    <?php if ($subjectErr): ?><p class="error-message"><?php echo $subjectErr; ?></p><?php endif; ?>
                                </div>
                                <div>
                                    <textarea name="message" id="message" rows="5" class="form-input w-full px-4 py-3 rounded-lg outline-none resize-none" placeholder="Your message..."><?php echo htmlspecialchars($message);?></textarea>
                                    <?php if ($messageErr): ?><p class="error-message"><?php echo $messageErr; ?></p><?php endif; ?>
                                </div>
                                <div>
                                    <button type="submit" class="w-full flex items-center justify-center bg-[var(--accent-color)] hover:bg-[var(--accent-hover)] text-white font-bold py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[var(--bg-color)] focus:ring-[var(--accent-color)]">
                                        Send Message
                                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggle = document.getElementById('theme-toggle');
            const lightIcon = document.getElementById('theme-icon-light');
            const darkIcon = document.getElementById('theme-icon-dark');
            const htmlEl = document.documentElement;

            const setTheme = (theme) => {
                htmlEl.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                if (theme === 'dark') {
                    darkIcon.classList.remove('hidden');
                    lightIcon.classList.add('hidden');
                } else {
                    darkIcon.classList.add('hidden');
                    lightIcon.classList.remove('hidden');
                }
            };

            themeToggle.addEventListener('click', () => {
                const currentTheme = htmlEl.getAttribute('data-theme');
                setTheme(currentTheme === 'dark' ? 'light' : 'dark');
            });

            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme) {
                setTheme(savedTheme);
            } else {
                setTheme(prefersDark ? 'dark' : 'light');
            }
        });
    </script>

</body>
</html>

