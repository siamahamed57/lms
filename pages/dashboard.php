<?php


// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/account.php");
    exit;
}

// Role
$userRole = $_SESSION['user_role'] ?? 'student';

// Current section (from URL)
$section = $_GET['page'] ?? 'overview';

// Menus
$adminMenu = [
    'overview' => ['icon' => 'fas fa-tachometer-alt', 'text' => 'Overview'],
    'users' => ['icon' => 'fas fa-users-cog', 'text' => 'User Management'],
    'courses' => ['icon' => 'fas fa-book', 'text' => 'Course Management'],
    'create-course' => ['icon' => 'fas fa-plus', 'text' => 'Create Course'],
    'instructors' => ['icon' => 'fas fa-chalkboard-teacher', 'text' => 'Instructor Management'],
    'students' => ['icon' => 'fas fa-user-graduate', 'text' => 'Student Management'],
    'content' => ['icon' => 'fas fa-file-alt', 'text' => 'Content & Assessments'],
    'reports' => ['icon' => 'fas fa-chart-bar', 'text' => 'Reporting & Analytics'],
    'communication' => ['icon' => 'fas fa-bullhorn', 'text' => 'Communication'],
    'settings' => ['icon' => 'fas fa-cogs', 'text' => 'System Settings'],
    'misc' => ['icon' => 'fas fa-ellipsis-h', 'text' => 'Miscellaneous']
];

$instructorMenu = [
    'overview' => ['icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
    'my-courses' => ['icon' => 'fas fa-book-open', 'text' => 'My Courses'],
    'students' => ['icon' => 'fas fa-users', 'text' => 'Student Management'],
    'assessments' => ['icon' => 'fas fa-tasks', 'text' => 'Assessments'],
    'analytics' => ['icon' => 'fas fa-chart-line', 'text' => 'Analytics'],
    'communication' => ['icon' => 'fas fa-comments', 'text' => 'Communication'],
    'payouts' => ['icon' => 'fas fa-dollar-sign', 'text' => 'Monetization'],
    'profile' => ['icon' => 'fas fa-user-edit', 'text' => 'Profile & Settings']
];

$studentMenu = [
    'overview' => ['icon' => 'fas fa-home', 'text' => 'Dashboard'],
    'my-courses' => ['icon' => 'fas fa-book-reader', 'text' => 'My Courses'],
    'browse-courses' => ['icon' => 'fas fa-search', 'text' => 'Browse Courses'],
    'grades' => ['icon' => 'fas fa-graduation-cap', 'text' => 'Grades & Feedback'],
    'certificates' => ['icon' => 'fas fa-certificate', 'text' => 'My Certificates'],
    'profile' => ['icon' => 'fas fa-user-cog', 'text' => 'Account & Profile'],
    'support' => ['icon' => 'fas fa-question-circle', 'text' => 'Support']
];

// Active menu based on role
$activeMenu = ($userRole === 'admin') ? $adminMenu : (($userRole === 'instructor') ? $instructorMenu : $studentMenu);

// Function to generate sidebar links
function generateNavLinks($menu, $section) {
    $html = '';
    foreach ($menu as $page => $details) {
        $isActive = ($page === $section);
        $html .= '<li class="nav-list-item">
                    <a href="?page=' . $page . '" 
                       data-section="' . $page . '" 
                       class="nav-link ' . ($isActive ? 'active' : '') . '">
                        <i class="' . $details['icon'] . ' nav-icon"></i>
                        <span class="nav-text">' . $details['text'] . '</span>
                    </a>
                  </li>';
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <style>
    .dashboard-section { display: none; }
    .dashboard-section.active { display: block; }
  </style>
</head>
<body>
<div class="dashboard-wrapper">

  <!-- Sidebar -->
  <aside class="sidebar-desktop">
    <nav>
      <ul class="nav-list">
        <?= generateNavLinks($activeMenu, $section) ?>
      </ul>
    </nav>
  </aside>

  <!-- Content Sections -->
  <main class="dashboard-content">
    <section id="overview" class="dashboard-section <?= ($section==='overview')?'active':'' ?>">
      <?php include __DIR__ . "/templates/overview.php"; ?>
    </section>

    <section id="create-course" class="dashboard-section <?= ($section==='create-course')?'active':'' ?>">
      <?php include __DIR__ . "/../api/courses/create-course.php"; ?>
    </section>

    <section id="users" class="dashboard-section <?= ($section==='users')?'active':'' ?>">
      <?php include __DIR__ . "/../api/users/user-management.php"; ?>
    </section>

    <!-- আরও section add করবেন এখানে একইভাবে -->
  </main>
</div>

<script>
  // Handle clicks (AJAX-like switching without reload)
  document.querySelectorAll(".nav-link").forEach(link => {
    link.addEventListener("click", function(e){
      e.preventDefault();
      let target = this.getAttribute("data-section");

      // hide all
      document.querySelectorAll(".dashboard-section").forEach(sec => sec.classList.remove("active"));

      // show selected
      document.getElementById(target).classList.add("active");

      // update sidebar
      document.querySelectorAll(".nav-link").forEach(l => l.classList.remove("active"));
      this.classList.add("active");

      // update URL (so refresh works)
      history.pushState({section: target}, "", "?page=" + target);
    });
  });

  // Handle back/forward buttons
  window.addEventListener("popstate", function(e){
    let section = e.state?.section || "overview";

    // hide all
    document.querySelectorAll(".dashboard-section").forEach(sec => sec.classList.remove("active"));

    // show correct
    document.getElementById(section).classList.add("active");

    // update sidebar
    document.querySelectorAll(".nav-link").forEach(l => l.classList.remove("active"));
    document.querySelector('[data-section="'+section+'"]').classList.add("active");
  });
</script>

</body>
</html>
