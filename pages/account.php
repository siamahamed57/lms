<?php
// --- START SESSION ---
// This must be the very first thing in your script


// --- DATABASE CONNECTION ---
// Make sure this path is correct relative to THIS file's location
require_once __DIR__ . '../../includes/db.php';

// Initialize messages for user feedback
$message = '';
$message_type = '';

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- REGISTRATION LOGIC ---
    if (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'] ?? 'student'; // Default to 'student'

        // Validation Checks
        if (empty($name) || empty($email) || empty($password)) {
            $message = 'All fields are required for registration.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format.';
            $message_type = 'error';
        } else {
            // Check if the email already exists in the database
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = 'An account with this email already exists.';
                $message_type = 'error';
            } else {
                // Email is unique, proceed with registration
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert the new user into the 'users' table
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
    }

    // --- LOGIN LOGIC ---
    elseif (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $message = 'Both email and password are required.';
            $message_type = 'error';
        } else {
            // Fetch user data from the database
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            // Verify user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables to establish the login state
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['is_logged_in'] = true; // Set a general login flag

                // --- REDIRECT USER BASED ON THEIR ROLE ---
                switch ($user['role']) {
                    case 'admin':
                        header('Location: admin_dashboard.php');
                        break;
                    case 'instructor':
                        header('Location: instructor_dashboard.php');
                        break;
                    case 'student':
                        header('Location: student_dashboard.php');
                        break;
                    default:
                        // Fallback redirect for safety
                        header('Location: home.php');
                        break;
                }
                exit; // Crucial to stop script execution after a header redirect
            } else {
                $message = 'Invalid email or password.';
                $message_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNIES | Login & Register</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
    .card { background-color: #fff; border-radius: 1.25rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 2rem; width: 100%; max-width: 420px; margin: 60px auto; transition: 0.3s; }
    .card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
    .tab-active { border-bottom: 3px solid #8B5CF6; color: #111827; }
    .form-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #D1D5DB; border-radius: 0.5rem; transition: border-color 0.2s, box-shadow 0.2s; }
    .form-input:focus { border-color: #8B5CF6; box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.3); outline: none; }
    .btn-primary { width: 100%; background-color: #8B5CF6; color: white; padding: 0.8rem; border-radius: 0.5rem; font-weight: 600; transition: background-color 0.2s; }
    .btn-primary:hover { background-color: #7C3AED; }
</style>
</head>
<body>
<div class="card">

<div class="flex justify-around mb-6 border-b">
    <button id="login-tab" class="py-2 px-6 text-lg font-semibold tab-active">Login</button>
    <button id="register-tab" class="py-2 px-6 text-lg font-semibold text-gray-500">Register</button>
</div>

<?php if ($message): ?>
    <div class="mb-4 p-3 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div id="login-form">
    <h2 class="text-2xl font-bold text-center mb-6">Welcome Back 👋</h2>
    <form method="POST" action="" class="space-y-4">
        <input type="hidden" name="login" value="1">
        <input type="email" name="email" placeholder="Email Address" required class="form-input">
        <input type="password" name="password" placeholder="Password" required class="form-input">
        <button type="submit" class="btn-primary">Log In</button>
    </form>
</div>

<div id="register-form" class="hidden">
    <h2 class="text-2xl font-bold text-center mb-6">Create an Account 🚀</h2>
    <form method="POST" action="" class="space-y-4">
        <input type="hidden" name="register" value="1">
        <input type="text" name="name" placeholder="Full Name" required class="form-input">
        <input type="email" name="email" placeholder="Email Address" required class="form-input">
        <input type="password" name="password" placeholder="Password" required class="form-input">
        <select name="role" class="form-input">
            <option value="student" selected>Register as a Student</option>
            <option value="instructor">Register as an Instructor</option>
            <option value="admin">Register as an Admin</option>
        </select>
        <button type="submit" class="btn-primary">Register</button>
    </form>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const loginTab = document.getElementById('login-tab');
    const registerTab = document.getElementById('register-tab');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    const activeTabClasses = ['tab-active', 'text-gray-900'];
    const inactiveTabClasses = ['text-gray-500'];

    loginTab.addEventListener('click', () => {
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
        
        loginTab.classList.add(...activeTabClasses);
        loginTab.classList.remove(...inactiveTabClasses);
        
        registerTab.classList.remove(...activeTabClasses);
        registerTab.classList.add(...inactiveTabClasses);
    });

    registerTab.addEventListener('click', () => {
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
        
        registerTab.classList.add(...activeTabClasses);
        registerTab.classList.remove(...inactiveTabClasses);
        
        loginTab.classList.remove(...activeTabClasses);
        loginTab.classList.add(...inactiveTabClasses);
    });
});
</script>
</body>
</html>
