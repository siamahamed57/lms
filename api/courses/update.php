<?php
// Path to the database connection file.
require_once __DIR__ . '/../../includes/db.php';


// Check if user is logged in and is an instructor.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header('Location: ../../index.php?page=login_register');
    exit;
}

// Get the course ID from the URL.
$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    echo "<div class='text-center text-red-600 mt-10'>Course ID is missing.</div>";
    exit;
}

$message = '';
$message_type = '';

// Fetch the existing course data
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    echo "<div class='text-center text-red-600 mt-10'>Course not found or you do not have permission to edit it.</div>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'] ?? null;
    $university_id = $_POST['university_id'] ?? null;
    $price = $_POST['price'] ?? 0.00;
    $status = $_POST['status'] ?? 'draft';

    if (empty($title) || empty($description)) {
        $message = 'Title and description are required.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE courses SET title = ?, subtitle = ?, description = ?, category_id = ?, university_id = ?, price = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $subtitle, $description, $category_id, $university_id, $price, $status, $course_id]);
            $message = 'Course updated successfully!';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Re-fetch course data after update to show latest info
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch categories and universities for the dropdowns
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$universities = $pdo->query("SELECT id, name FROM universities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 font-poppins min-h-screen flex items-center justify-center py-12">

    <div class="w-full max-w-2xl bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-center text-purple-600 mb-8">Update Course</h1>
        
        <?php if ($message): ?>
            <div class="px-4 py-3 mb-4 rounded-lg text-sm text-center font-medium
                <?= $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="update.php?id=<?= htmlspecialchars($course['id']) ?>" class="space-y-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Course Title</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($course['title']) ?>" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label for="subtitle" class="block text-sm font-medium text-gray-700">Subtitle</label>
                <input type="text" id="subtitle" name="subtitle" value="<?= htmlspecialchars($course['subtitle']) ?>"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="description" name="description" rows="4" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500"><?= htmlspecialchars($course['description']) ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="category_id" name="category_id"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category['id']) ?>" <?= ($course['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="university_id" class="block text-sm font-medium text-gray-700">University</label>
                    <select id="university_id" name="university_id"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Select University</option>
                        <?php foreach ($universities as $university): ?>
                            <option value="<?= htmlspecialchars($university['id']) ?>" <?= ($course['university_id'] == $university['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($university['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Price ($)</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?= htmlspecialchars($course['price']) ?>"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="draft" <?= ($course['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                        <option value="pending" <?= ($course['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="published" <?= ($course['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                        <option value="archived" <?= ($course['status'] === 'archived') ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
            </div>
            
            <button type="submit"
                class="w-full py-3 rounded-lg bg-purple-600 text-white font-semibold shadow-lg transition-transform duration-200 hover:bg-purple-700 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                Update Course
            </button>
        </form>
    </div>

</body>
</html>
