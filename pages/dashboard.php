<?php
// Check if user is logged in, if not, redirect to login page.
// Assuming 'user_id' is set in the session upon successful login.
if (!isset($_SESSION['user_id'])) {
    header('Location: account.php');
    exit;
}

// Retrieve user details from session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'student'; // Default to 'student' if not set
$user_name = $_SESSION['user_name'] ?? 'User'; // Default to 'User' if not set

// Determine which dashboard page to display. Default to 'overview'.
$page = $_GET['page'] ?? 'overview';

// A simple function to create a user-friendly title from the page slug
function get_dashboard_title($role, $page) {
    $page_title = htmlspecialchars(ucfirst(str_replace('-', ' ', $page)));
    $role_title = ucfirst($role);
    return "$page_title | $role_title Dashboard";
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= get_dashboard_title($user_role, $page) ?> | UNIES</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
</head>
<body class="dashboard-body">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h2 class="page-title"><?= htmlspecialchars(ucfirst(str_replace('-', ' ', $page))) ?></h2>
            <div class="user-info">
                <div class="welcome-message">
                    Welcome, <span class="font-semibold"><?= htmlspecialchars($user_name) ?></span>!
                </div>
                <!-- You can add a theme switcher or profile dropdown here if needed -->
            </div>
        </header>

        <div class="content-wrapper">
            <?php
            // This is a simple router to display content.
            // For a real application, you would create separate files for each page
            // and include them here to keep your code organized and manageable.
            // Example: include __DIR__ . "/dashboard_pages/{$user_role}/{$page}.php";
            ?>
            <div class="placeholder-content">
                <i class="fas fa-cogs placeholder-icon"></i>
                <h3 class="placeholder-title">Under Construction</h3>
                <p class="placeholder-text">
                    The '<?= htmlspecialchars(ucfirst(str_replace('-', ' ', $page))) ?>' section is currently being developed.
                    <br>
                    Check back soon for updates!
                </p>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>