<?php
// Path to the database connection file.
require_once __DIR__ . '/../../includes/db.php';

// Get the course ID from the URL.
$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    echo "<div class='text-center text-red-600 mt-10'>Course ID is missing.</div>";
    exit;
}

// Fetch course details from the database.
$stmt = $pdo->prepare("SELECT c.*, u.name AS instructor_name, u.avatar, cat.name AS category_name, uni.name AS university_name
                       FROM courses c
                       JOIN users u ON c.instructor_id = u.id
                       LEFT JOIN categories cat ON c.category_id = cat.id
                       LEFT JOIN universities uni ON c.university_id = uni.id
                       WHERE c.id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    echo "<div class='text-center text-red-600 mt-10'>Course not found.</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 font-poppins">

    <div class="container mx-auto max-w-6xl px-4 py-12">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden md:flex">
            <div class="md:w-1/2">
                <img src="<?= htmlspecialchars($course['thumbnail'] ?? 'https://placehold.co/800x600/e5e7eb/4b5563?text=Course+Thumbnail') ?>" 
                     alt="<?= htmlspecialchars($course['title']) ?>" 
                     onerror="this.onerror=null;this.src='https://placehold.co/800x600/e5e7eb/4b5563?text=No+Image';"
                     class="w-full h-96 object-cover">
            </div>
            <div class="md:w-1/2 p-8 flex flex-col justify-between">
                <div>
                    <span class="text-sm font-semibold text-purple-600 uppercase tracking-wide">
                        <?= htmlspecialchars($course['category_name'] ?? 'Uncategorized') ?>
                    </span>
                    <h1 class="mt-2 text-3xl font-extrabold text-gray-900"><?= htmlspecialchars($course['title']) ?></h1>
                    <p class="mt-2 text-xl text-gray-600"><?= htmlspecialchars($course['subtitle'] ?? 'No subtitle') ?></p>
                    <div class="mt-4 flex items-center space-x-4 text-gray-700">
                        <div class="flex items-center">
                            <i class="fas fa-user-circle text-purple-600 mr-2 text-lg"></i>
                            <span><?= htmlspecialchars($course['instructor_name']) ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-university text-purple-600 mr-2 text-lg"></i>
                            <span><?= htmlspecialchars($course['university_name'] ?? 'General') ?></span>
                        </div>
                    </div>
                    <div class="mt-6 text-gray-800">
                        <h2 class="text-lg font-bold">Description</h2>
                        <p class="mt-2 text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($course['description'])) ?></p>
                    </div>
                </div>
                <div class="mt-8">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-4xl font-extrabold text-purple-600">$<?= htmlspecialchars(number_format($course['price'], 2)) ?></span>
                        <span class="text-sm font-semibold text-gray-500 uppercase">Status: <span class="text-green-600 font-bold"><?= htmlspecialchars(ucfirst($course['status'])) ?></span></span>
                    </div>
                    <button class="w-full py-4 rounded-lg bg-purple-600 text-white font-semibold text-xl shadow-lg transition-transform duration-200 hover:bg-purple-700 hover:scale-105">
                        Enroll Now
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
