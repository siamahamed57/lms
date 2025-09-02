<?php
// Path to the database connection file.
require_once __DIR__ . '/../../includes/db.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['user_role'] : null;
$is_instructor = ($user_role == 3); // Assuming role 3 is for instructors

// Search query
$search_query = $_GET['search'] ?? '';
$search_param = '%' . $search_query . '%';

// Fetch courses from the database
$sql = "SELECT c.*, u.name AS instructor_name, cat.name AS category_name, uni.name AS university_name
        FROM courses c
        JOIN users u ON c.instructor_id = u.id
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN universities uni ON c.university_id = uni.id
        WHERE c.title LIKE ? OR u.name LIKE ?
        ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$search_param, $search_param]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Courses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 font-poppins">

    <div class="container mx-auto max-w-7xl px-4 py-8">
        <h1 class="text-4xl font-extrabold text-gray-900 text-center mb-4">Explore Our Courses</h1>
        <p class="text-lg text-gray-600 text-center mb-8">Find the perfect course to kickstart your learning journey.</p>
        
        <div class="mb-8 flex justify-center">
            <form action="list.php" method="GET" class="w-full max-w-xl">
                <div class="relative">
                    <input type="text" name="search" placeholder="Search for courses..."
                        value="<?= htmlspecialchars($search_query) ?>"
                        class="w-full px-6 py-3 pr-12 border border-gray-300 rounded-full shadow-md focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all duration-200">
                    <button type="submit" class="absolute right-0 top-0 mt-3 mr-4 text-gray-500">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <?php if (empty($courses)): ?>
            <p class="text-center text-gray-500 text-xl">No courses found.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden">
                        <img src="<?= htmlspecialchars($course['thumbnail'] ?? 'https://placehold.co/400x250/e5e7eb/4b5563?text=Thumbnail') ?>" 
                             alt="<?= htmlspecialchars($course['title']) ?>" 
                             onerror="this.onerror=null;this.src='https://placehold.co/400x250/e5e7eb/4b5563?text=No+Image';"
                             class="w-full h-40 object-cover">
                        <div class="p-5">
                            <h3 class="text-xl font-semibold text-gray-800 truncate mb-1"><?= htmlspecialchars($course['title']) ?></h3>
                            <p class="text-sm text-gray-500 mb-3 truncate"><?= htmlspecialchars($course['subtitle'] ?? 'No subtitle') ?></p>
                            <div class="flex items-center text-gray-700 text-sm mb-2">
                                <i class="fas fa-user-circle text-purple-600 mr-2"></i>
                                <span><?= htmlspecialchars($course['instructor_name']) ?></span>
                            </div>
                            <div class="flex items-center text-gray-700 text-sm mb-4">
                                <i class="fas fa-university text-purple-600 mr-2"></i>
                                <span><?= htmlspecialchars($course['university_name'] ?? 'General') ?></span>
                            </div>
                            <div class="flex items-center justify-between mt-4">
                                <span class="text-lg font-bold text-purple-600">$<?= htmlspecialchars(number_format($course['price'], 2)) ?></span>
                                <a href="detail.php?id=<?= htmlspecialchars($course['id']) ?>" class="text-sm font-medium text-purple-600 hover:underline transition-colors duration-200">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
