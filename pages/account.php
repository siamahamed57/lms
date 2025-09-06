<?php
ob_start(); // Start output buffering

require_once __DIR__ . '../../includes/db.php';
require_once __DIR__ . '../../includes/header.php';

// Redirect to dashboard if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard');
    exit;
}

// Initialize messages
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Registration
    if (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'] ?? 'student';

        if (empty($name) || empty($email) || empty($password)) {
            $message = 'All fields are required for registration.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = 'An account with this email already exists.';
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("ssss", $name, $email, $hashed_password, $role);

                if ($stmt_insert->execute()) {
                    $message = 'Registration successful! You can now log in.';
                    $message_type = 'success';
                } else {
                    $message = 'Registration failed. Please try again.';
                    $message_type = 'error';
                }
                $stmt_insert->close();
            }
            $stmt->close();
        }

    // Login
    } elseif (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $message = 'Both email and password are required.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['is_logged_in'] = true;

                header('Location: dashboard');
                exit;
            } else {
                $message = 'Invalid email or password.';
                $message_type = 'error';
            }
        }
    }
}
ob_end_flush(); // Flush output at the very end
?>


<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNIES | Login & Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Firebase SDK - Corrected to use ES modules -->
    <script type="module" src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js"></script>
    <script type="module" src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js"></script>
     <link rel="stylesheet" href="./assets/css/webkit.css"> <!-- Main styles -->

    <style>
        :root,
        [data-theme="dark"] {
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
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .card {
            background-color: var(--glass-bg);
            border-radius: 1.25rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            width: 100%;
            max-width: 420px;
            margin: 60px auto;
            transition: 0.3s;
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
        }

        .tab-active {
            border-bottom: 3px solid var(--accent-color);
            color: var(--text-color);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--input-border);
            border-radius: 0.5rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: var(--input-bg);
            color: var(--text-color);
        }

        .form-input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px var(--accent-ring);
            outline: none;
        }

        .btn-primary {
            width: 100%;
            background-color: var(--accent-color);
            color: white;
            padding: 0.8rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: var(--accent-hover);
        }
    </style>
</head>

<body>
    <div class="absolute inset-0 -z-10 h-full w-full bg-[var(--background-container)] bg-[radial-gradient(var(--background-gradient-dots)_1px,transparent_1px)] [background-size:16px_16px]"></div>
    <div class="card">
        <div class="flex justify-around mb-6 border-b border-gray-700">
            <button id="login-tab" class="py-2 px-6 text-lg font-semibold tab-active">Login</button>
            <button id="register-tab" class="py-2 px-6 text-lg font-semibold text-gray-500">Register</button>
        </div>

        <?php if ($message): ?>
            <div class="mb-4 p-3 rounded-lg <?= $message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <div id="login-form">
            <h2 class="text-2xl font-bold text-center mb-6">Welcome Back</h2>
            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="login" value="1">
                <input type="email" name="email" placeholder="Email Address" required class="form-input">
                <input type="password" name="password" placeholder="Password" required class="form-input">
                <button type="submit" class="btn-primary">Log In</button>
            </form>
            <button type="button" id="google-login" class="w-full mt-4 flex items-center justify-center gap-2 py-2 px-4 border rounded-lg bg-white shadow hover:bg-gray-100 transition">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google Logo" class="w-5 h-5">
                <span class="text-gray-700 font-medium">Sign in with Google</span>
            </button>
        </div>

        <!-- REGISTER FORM -->
        <div id="register-form" class="hidden">
            <h2 class="text-2xl font-bold text-center mb-6">Create an Account</h2>
            <form method="POST" action="" class="space-y-4">
                
                <input type="hidden" name="register" value="1">
                <input type="text" name="name" placeholder="Full Name" required class="form-input">
                <input type="email" name="email" placeholder="Email Address" required class="form-input">
                <input type="password" name="password" placeholder="Password" required class="form-input">
                <button type="submit" class="btn-primary">Register</button>
            </form>

            <button type="button" id="google-register" class="w-full mt-4 flex items-center justify-center gap-2 py-2 px-4 border rounded-lg bg-white shadow hover:bg-gray-100 transition">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google Logo" class="w-5 h-5">
                <span class="text-gray-700 font-medium">Sign up with Google</span>
            </button>
        </div>
    </div>

    <script type="module">
        import {
            initializeApp
        } from 'https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js';
        import {
            getAuth,
            GoogleAuthProvider,
            signInWithPopup,
            getIdToken
        } from 'https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js';

        // --- TAB SWITCH LOGIC ---
        const loginTab = document.getElementById('login-tab');
        const registerTab = document.getElementById('register-tab');
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');

        const tabs = {
            login: { tab: loginTab, form: loginForm },
            register: { tab: registerTab, form: registerForm }
        };

        function switchTab(activeTabKey) {
            Object.keys(tabs).forEach(key => {
                const isActive = key === activeTabKey;
                tabs[key].form.classList.toggle('hidden', !isActive);
                tabs[key].tab.classList.toggle('tab-active', isActive);
                tabs[key].tab.classList.toggle('text-gray-500', !isActive);
            });
        }
        Object.keys(tabs).forEach(key => {
            tabs[key].tab.addEventListener('click', () => switchTab(key));
        });

        // --- FIREBASE CONFIG ---
        const firebaseConfig = {
            apiKey: "AIzaSyCr82dopqG-r6al_fLs4C6w1e3xGZVsCMU",
            authDomain: "unies-auth-54c3d.firebaseapp.com",
            projectId: "unies-auth-54c3d",
            storageBucket: "unies-auth-54c3d.firebasestorage.app",
            messagingSenderId: "412821593502",
            appId: "1:412821593502:web:8f6607bffdac2fb2850a8f",
            measurementId: "G-XKDVNG4BWY"
        };

        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();

        // Handle Google Sign-In (both login + register)
        function handleGoogleSignIn() {
            signInWithPopup(auth, provider)
                .then(result => {
                    const user = result.user;
                    console.log("Google user:", user);

                    getIdToken(user).then(token => {
                        fetch("pages/google_auth.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    token
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.redirect) window.location.href = data.redirect;
                                console.log("Server response:", data);
                            });
                    });
                })
                .catch(error => console.error("Google login error:", error));
        }

        document.getElementById("google-login").addEventListener("click", handleGoogleSignIn);
        document.getElementById("google-register").addEventListener("click", handleGoogleSignIn);

        document.addEventListener("DOMContentLoaded", () => {
            const hash = window.location.hash;
            if (hash === "#register-tab") {
                switchTab('register');
            } else if (hash === "#login-tab") {
                switchTab('login');
            }
        });
    </script>
</body>

</html>



<?php require_once __DIR__ . '../../includes/footer.php'; ?>