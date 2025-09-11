<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. AUTHORIZATION: Redirect to login if user is not logged in.
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: account.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/header.php';

$user_role = $_SESSION['user_role'];
$page = $_GET['page'] ?? 'home'; // Default page is 'home'

// 2. PAGE MAPPING: Define which pages are accessible to each role.
$admin_pages = [
    'home' => 'admin/home.php',
    'enrollment-management' => 'admin/enrollment_management.php',
    'course-management' => 'admin/course_management.php',
    'instructor-settings' => 'admin/instructor_settings.php',
    // Add other admin pages here
];

$instructor_pages = [
    'home' => 'instructor/home.php',
    'my-courses' => 'instructor/my_courses.php',
    'create-course' => 'instructor/create_course.php',
    'payouts' => 'instructor/payouts.php', // <-- THE FIX: This maps 'payouts' to the correct file.
    'profile' => 'instructor/profile.php',
    // Add other instructor pages here
];

$student_pages = [
    'home' => 'student/home.php',
    'my-courses' => 'student/my_courses.php',
    'my-wallet' => 'student/my_wallet.php',
    'profile' => 'student/profile.php',
    // Add other student pages here
];

// 3. ROUTING LOGIC: Determine which sidebar and content file to load.
$sidebar_file = '';
$page_file_to_include = '';
$page_map = [];

switch ($user_role) {
    case 'admin':
        $sidebar_file = 'admin/sidebar.php';
        $page_map = $admin_pages;
        break;
    case 'instructor':
        $sidebar_file = 'instructor/sidebar.php';
        $page_map = $instructor_pages;
        break;
    case 'student':
        $sidebar_file = 'student/sidebar.php';
        $page_map = $student_pages;
        break;
    default:
        die("Invalid user role.");
}

// Find the correct file to include, or default to the role's home page.
$page_file_to_include = $page_map[$page] ?? $page_map['home'];

// --- Handle POST requests for logic files ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user_role === 'instructor' && $page === 'payouts') {
        include __DIR__ . '/instructor/payouts_logic.php';
        exit;
    }
    // Add other POST handlers here if needed
}

?>

<main class="container mx-auto max-w-7xl px-4 py-10">
    <div class="flex flex-col md:flex-row gap-8">
        
        <!-- Sidebar -->
        <aside class="w-full md:w-1/4 lg:w-1/5">
            <?php
            if (file_exists($sidebar_file)) {
                include $sidebar_file;
            } else {
                echo "<div class='p-4 bg-card-bg rounded-lg text-red-500'>Sidebar not found: " . htmlspecialchars($sidebar_file) . "</div>";
            }
            ?>
        </aside>

        <!-- Main Content -->
        <section class="w-full md:w-3/4 lg:w-4/5">
            <?php
            if (file_exists($page_file_to_include)) {
                include $page_file_to_include;
            } else {
                echo "<div class='bg-card-bg p-8 rounded-lg text-center'><h2 class='text-2xl font-bold text-red-500'>Content Not Found</h2><p class='text-card-color mt-2'>The page you requested ('" . htmlspecialchars($page) . "') could not be found.</p></div>";
            }
            ?>
        </section>
    </div>
</main>

<?php
include_once __DIR__ . '/includes/footer.php';
?>