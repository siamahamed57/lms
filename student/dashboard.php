<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in, if not, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'student';
$user_name = $_SESSION['user_name'] ?? 'User';

// Determine which page to show, default to 'overview'
$page = $_GET['page'] ?? 'overview';

function get_dashboard_title($role) {
    switch ($role) {
        case 'admin': return 'Admin Dashboard';
        case 'instructor': return 'Instructor Dashboard';
        default: return 'Student Dashboard';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= get_dashboard_title($user_role) ?> | UNIES</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-body">

    <?php include __DIR__ . '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h2><?= htmlspecialchars(ucfirst(str_replace('-', ' ', $page))) ?></h2>
            <div class="user-info">
                <span>Welcome, <?= htmlspecialchars($user_name) ?>!</span>
                <!-- You can add a theme switcher or profile dropdown here -->
            </div>
        </header>

        <div class="content-wrapper">
            <?php
            // This is a simple router. For a real application, you would create separate files
            // for each page and include them here to keep your code organized.
            echo "<h1>" . htmlspecialchars(ucfirst(str_replace('-', ' ', $page))) . "</h1>";
            echo "<p>This is a placeholder for the " . htmlspecialchars($page) . " page content. You can build out the specific functionality for each section here.</p>";
            ?>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>