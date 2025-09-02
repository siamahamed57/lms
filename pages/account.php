<?php 
require_once __DIR__ . '/../api/auth/login.php';



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
    .card { background-color: #fff; border-radius: 1.25rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); padding: 2rem; width: 100%; max-width: 420px; margin: 100px auto; transition: 0.3s; }
    .card:hover { transform: translateY(-5px); }
    .tab-active { border-bottom: 3px solid #7f00ff; color: #000; }
</style>
</head>
<body>
<div class="card">

<!-- Tabs -->
<div class="flex justify-around mb-6 border-b">
    <button id="login-tab" class="py-2 px-6 text-lg font-semibold tab-active">Login</button>
    <button id="register-tab" class="py-2 px-6 text-lg font-semibold text-gray-500">Register</button>
</div>

<!-- Display messages -->
<?php if ($message): ?>
    <div class="mb-4 p-3 rounded <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Login Form -->
<div id="login-form">
<h2 class="text-2xl font-bold text-center mb-6">Welcome Back 👋</h2>
<form method="POST" class="space-y-4">
    <input type="hidden" name="login" value="1">
    <input type="email" name="email" placeholder="Email" required class="w-full px-4 py-2 border rounded-lg">
    <input type="password" name="password" placeholder="Password" required class="w-full px-4 py-2 border rounded-lg">
    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold">Log In</button>
</form>
</div>

<!-- Register Form -->
<div id="register-form" class="hidden">
<h2 class="text-2xl font-bold text-center mb-6">Create Account 🚀</h2>
<form method="POST" class="space-y-4">
    <input type="hidden" name="register" value="1">
    <input type="text" name="name" placeholder="Full Name" required class="w-full px-4 py-2 border rounded-lg">
    <input type="email" name="email" placeholder="Email" required class="w-full px-4 py-2 border rounded-lg">
    <input type="password" name="password" placeholder="Password" required class="w-full px-4 py-2 border rounded-lg">
    <select name="role" class="w-full px-4 py-2 border rounded-lg">
        <option value="student">User</option>
        <option value="admin">Admin</option>
    </select>
    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold">Register</button>
</form>
</div>
</div>

<script>
const loginTab = document.getElementById('login-tab');
const registerTab = document.getElementById('register-tab');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');

loginTab.addEventListener('click', () => {
    loginForm.classList.remove('hidden');
    registerForm.classList.add('hidden');
    loginTab.classList.add('tab-active');
    registerTab.classList.remove('tab-active'); 
    registerTab.classList.add('text-gray-500');
});

registerTab.addEventListener('click', () => {
    registerForm.classList.remove('hidden');
    loginForm.classList.add('hidden');
    registerTab.classList.add('tab-active');
    loginTab.classList.remove('tab-active');
    loginTab.classList.add('text-gray-500');
});
</script>
</body>
</html>
