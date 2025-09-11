<?php

require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: pages/account.php");
    exit;
}
// Role চেক
$userRole = $_SESSION['user_role'] ?? 'student';

// যদি role student হয় → permission deny
if ($userRole !== 'admin' && $userRole !== 'instructor') {
    // Access deny message
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>❌ Access Denied!<br>Only Admin or Instructor can access this page.</h2>";
    exit;
}
$errors = [];
$success = '';

// After a successful post, a session variable is set before redirecting.
if (isset($_SESSION['course_creation_success'])) {
    $success = $_SESSION['course_creation_success'];
    unset($_SESSION['course_creation_success']);
}

// Fetch categories for dropdown
$categoriesQuery = "SELECT id, name FROM categories ORDER BY name ASC";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch universities for dropdown
$universitiesQuery = "SELECT id, name FROM universities ORDER BY name ASC";
$universitiesResult = $conn->query($universitiesQuery);
$universities = [];
if ($universitiesResult) {
    while ($row = $universitiesResult->fetch_assoc()) {
        $universities[] = $row;
    }
}

// If admin, fetch instructors for dropdown
$instructors = [];
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $instructorsQuery = "SELECT id, name, email FROM users WHERE role = 'instructor' ORDER BY name ASC";
    $instructorsResult = $conn->query($instructorsQuery);
    if ($instructorsResult) {
        while ($row = $instructorsResult->fetch_assoc()) {
            $instructors[] = $row;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $university_id = intval($_POST['university_id'] ?? 0);
    $status = $_POST['status'] ?? 'draft';

    // NEW FEATURES - Get form data for new fields
    $course_level = $_POST['course_level'] ?? 'beginner';
    $course_language = trim($_POST['course_language'] ?? 'English');
    $seo_title = trim($_POST['seo_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $prerequisites = trim($_POST['prerequisites'] ?? '');
    $certificate_of_completion = isset($_POST['certificate_of_completion']) ? 1 : 0;
    $enrollment_limit = intval($_POST['enrollment_limit'] ?? 0);
    
    // For admin, allow selecting instructor; for instructor, use their ID
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_POST['instructor_id'])) {
        $instructor_id = intval($_POST['instructor_id']);
    } else {
        $instructor_id = $_SESSION['user_id'];
    }
    
    // Validation
    if (empty($title)) {
        $errors[] = "Course title is required.";
    }
    
    if (strlen($title) > 255) {
        $errors[] = "Course title must be less than 255 characters.";
    }
    
    if (!empty($subtitle) && strlen($subtitle) > 255) {
        $errors[] = "Course subtitle must be less than 255 characters.";
    }

    if (!empty($seo_title) && strlen($seo_title) > 255) {
        $errors[] = "SEO title must be less than 255 characters.";
    }
    
    if (empty($description)) {
        $errors[] = "Course description is required.";
    }
    
    if ($price < 0) {
        $errors[] = "Price cannot be negative.";
    }
    
    if ($category_id > 0) {
        // Verify category exists
        $checkCategory = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        $checkCategory->bind_param("i", $category_id);
        $checkCategory->execute();
        if ($checkCategory->get_result()->num_rows === 0) {
            $errors[] = "Invalid category selected.";
        }
        $checkCategory->close();
    }
    
    if ($university_id > 0) {
        // Verify university exists
        $checkUniversity = $conn->prepare("SELECT id FROM universities WHERE id = ?");
        $checkUniversity->bind_param("i", $university_id);
        $checkUniversity->execute();
        if ($checkUniversity->get_result()->num_rows === 0) {
            $errors[] = "Invalid university selected.";
        }
        $checkUniversity->close();
    }

    // Validate enrollment limit
    if (!empty($_POST['enrollment_limit']) && $enrollment_limit <= 0) {
        $errors[] = "Enrollment limit must be a positive number.";
    }
    
    // Validate status
    $validStatuses = ['draft', 'pending', 'published', 'archived'];
    if (!in_array($status, $validStatuses)) {
        $status = 'draft';
    }
    
    // Handle thumbnail upload
    $thumbnail = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/thumbnails/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['thumbnail']['type'];
        $fileSize = $_FILES['thumbnail']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.";
        }
        
        // Max file size: 5MB
        if ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "File size must be less than 5MB.";
        }
        
        if (empty($errors)) {
            $fileExtension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('course_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadPath)) {
                // Store relative path in database
                $thumbnail = 'uploads/thumbnails/' . $fileName;
            } else {
                $errors[] = "Failed to upload thumbnail.";
            }
        }
    } elseif (isset($_POST['thumbnail_url']) && !empty($_POST['thumbnail_url'])) {
        // Allow external URL for thumbnail
        $thumbnail = filter_var($_POST['thumbnail_url'], FILTER_VALIDATE_URL);
        if (!$thumbnail) {
            $errors[] = "Invalid thumbnail URL.";
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Updated INSERT query with new columns
            $insertQuery = "INSERT INTO courses (
                instructor_id, 
                category_id, 
                university_id, 
                title, 
                subtitle, 
                description, 
                price, 
                status, 
                thumbnail,
                course_level,
                course_language,
                seo_title,
                meta_description,
                prerequisites,
                certificate_of_completion,
                enrollment_limit
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insertQuery);
            
            // Handle NULL values for optional fields
            $cat_id = $category_id > 0 ? $category_id : null;
            $uni_id = $university_id > 0 ? $university_id : null;
            $sub = !empty($subtitle) ? $subtitle : null;
            $thumb = !empty($thumbnail) ? $thumbnail : null;
            $seo_t = !empty($seo_title) ? $seo_title : null;
            $meta_d = !empty($meta_description) ? $meta_description : null;
            $prereq = !empty($prerequisites) ? $prerequisites : null;
            $en_limit = $enrollment_limit > 0 ? $enrollment_limit : null;

            // Updated bind_param with new types
            $stmt->bind_param(
                "iiisssdssisssssi",
                $instructor_id,
                $cat_id,
                $uni_id,
                $title,
                $sub,
                $description,
                $price,
                $status,
                $thumb,
                $course_level,
                $course_language,
                $seo_t,
                $meta_d,
                $prereq,
                $certificate_of_completion,
                $en_limit
            );
            
            if ($stmt->execute()) {
                $course_id = $conn->insert_id;
                
                // Handle tags if provided
                if (isset($_POST['tags']) && !empty($_POST['tags'])) {
                    $tags = explode(',', $_POST['tags']);
                    
                    foreach ($tags as $tagName) {
                        $tagName = trim($tagName);
                        if (!empty($tagName)) {
                            // Insert or get tag ID
                            $tagQuery = "INSERT IGNORE INTO tags (name) VALUES (?)";
                            $tagStmt = $conn->prepare($tagQuery);
                            $tagStmt->bind_param("s", $tagName);
                            $tagStmt->execute();
                            
                            // Get tag ID
                            $getTagQuery = "SELECT id FROM tags WHERE name = ?";
                            $getTagStmt = $conn->prepare($getTagQuery);
                            $getTagStmt->bind_param("s", $tagName);
                            $getTagStmt->execute();
                            $tagResult = $getTagStmt->get_result();
                            
                            if ($tagRow = $tagResult->fetch_assoc()) {
                                // Link tag to course
                                $linkQuery = "INSERT IGNORE INTO course_tag (course_id, tag_id) VALUES (?, ?)";
                                $linkStmt = $conn->prepare($linkQuery);
                                $linkStmt->bind_param("ii", $course_id, $tagRow['id']);
                                $linkStmt->execute();
                            }
                        }
                    }
                }
                
                $conn->commit();
                $_SESSION['course_creation_success'] = "Course created successfully! Course ID: " . $course_id;

                // Redirect to the same page using JavaScript to clear the POST data and reset the form.
                echo '<script>window.location.href = window.location.href;</script>';
                exit;

            } else {
                throw new Exception("Failed to create course.");
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course - LMS</title>
    <style>
    /* ---- [ Import Modern Font & Icons ] ---- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

/* ---- [ CSS Variables for Easy Theming ] ---- */
:root {
    --primary-color: #b915ff;
    --primary-hover-color: #8b00cc;
    --secondary-color: rgba(255, 255, 255, 0.1);
    --secondary-hover-color: rgba(255, 255, 255, 0.2);
    --background-start: #231134;
    --background-end: #0f172a;
    --glass-bg: rgba(255, 255, 255, 0.07);
    --glass-border: rgba(255, 255, 255, 0.2);
    --text-primary: #f0f0f0;
    --text-secondary: #a0a0a0;
    --input-bg: rgba(0, 0, 0, 0.3);

    /* Status & Alert Colors */
    --color-success: #28a745;
    --color-danger: #dc3545;
}


.app-container {
    display: grid;
    grid-template-columns: 300px 1fr;
    max-width: 1200px;
    width: 100%;
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

/* ---- [ Left Panel Styling ] ---- */
.left-panel {
    background: rgba(0, 0, 0, 0.2);
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.left-panel h1 {
    font-size: 2.25rem;
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 1rem;
}
.left-panel p {
    font-size: 1rem;
    color: var(--text-secondary);
    line-height: 1.6;
}

/* ---- [ Form Container Styling ] ---- */
.form-container {
    padding: 2.5rem;
    max-height: 90vh;
    overflow-y: auto;
}
.form-section-title {
    font-size: 1.5rem;
    font-weight: 500;
    margin-top: 2rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--glass-border);
}
.form-section-title:first-child {
    margin-top: 0;
}

/* ---- [ Form Elements Styling ] ---- */
.form-grid { display: grid; gap: 1.5rem; grid-template-columns: 1fr 1fr; margin-bottom: 1.5rem; }
.form-group { display: flex; flex-direction: column; margin-bottom: 1.5rem; }
.form-group:last-child { margin-bottom: 0; }
.form-group label { margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary); }
.form-group label.required::after { content: ' *'; color: var(--primary-color); }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 0.75rem 1rem; background: var(--input-bg);
    border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-primary);
    font-family: 'Poppins', sans-serif; transition: all 0.3s ease;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none; border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(185, 21, 255, 0.2);
}
.form-group select {
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23a0a0a0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 16px 12px;
}
textarea { resize: vertical; min-height: 100px; }
.help-text { font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem; }

/* ---- [ Custom File Input & Checkbox ] ---- */
.file-input {
    background: var(--input-bg); border: 2px dashed var(--glass-border); padding: 1rem;
    text-align: center; color: var(--text-secondary); transition: border-color 0.3s;
}
.file-input:hover { border-color: var(--primary-color); }
.file-input::-webkit-file-upload-button {
    background: var(--secondary-color); color: var(--text-primary);
    border: 1px solid var(--glass-border); border-radius: 6px; padding: 0.5rem 1rem;
    cursor: pointer; transition: background-color 0.3s ease;
}
.file-input::-webkit-file-upload-button:hover { background: var(--secondary-hover-color); }

.checkbox-group { display: flex; align-items: center; gap: 0.75rem; }
.checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary-color); }
.checkbox-group label { margin: 0; }

/* ---- [ Buttons ] ---- */
.btn-group { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; }
.btn { text-decoration: none; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
.btn-primary { background-color: var(--primary-color); color: #fff; }
.btn-primary:hover { background-color: var(--primary-hover-color); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(185, 21, 255, 0.2); }
.btn-secondary { background: var(--secondary-color); color: var(--text-primary); border: 1px solid var(--glass-border); }
.btn-secondary:hover { background: var(--secondary-hover-color); }

/* ---- [ Alerts ] ---- */
.alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid transparent; }
.alert-success { background-color: rgba(40, 167, 69, 0.15); border-color: rgba(40, 167, 69, 0.4); color: #a3ffb8; }
.alert-error { background-color: rgba(220, 53, 69, 0.15); border-color: rgba(220, 53, 69, 0.4); color: #ffacb3; }
.alert-error .error-list { list-style: none; padding-left: 0; }

/* ---- [ Responsive Design ] ---- */
@media (max-width: 992px) {
    .app-container { grid-template-columns: 1fr; }
    .left-panel { display: none; } /* Hide the left panel on smaller screens for more form space */
    .form-container { max-height: none; }
}

@media (max-width: 768px) {
    body { padding: 1rem; align-items: flex-start; }
    .app-container { width: 100%; }
    .form-container { padding: 1.5rem; }
    .form-grid { grid-template-columns: 1fr; }
    .btn-group { flex-direction: column; }
    .btn { width: 100%; text-align: center; }
}
    </style>
</head>
<body>
    <div class="app-container">
        <div class="left-panel">
            <h1>Create a New Course</h1>
            <p>Fill out the form to publish a new course on the platform.</p>
        </div>
        
        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li>• <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <h3 class="form-section-title">General Information</h3>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && !empty($instructors)): ?>
                <div class="form-group">
                    <label for="instructor_id" class="required">
                        Instructor
                    </label>
                    <select name="instructor_id" id="instructor_id" required>
                        <option value="">Select an Instructor</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?php echo $instructor['id']; ?>">
                                <?php echo htmlspecialchars($instructor['name'] . ' (' . $instructor['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title" class="required">Course Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required maxlength="255" placeholder="e.g., The Ultimate JavaScript Guide">
                    </div>
                    
                    <div class="form-group">
                        <label for="subtitle">Course Subtitle</label>
                        <input type="text" id="subtitle" name="subtitle" value="<?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?>" maxlength="255" placeholder="A brief tagline for your course">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" required placeholder="Describe what students will learn and what makes your course unique."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="course_level">Course Level</label>
                        <select name="course_level" id="course_level">
                            <option value="beginner" <?php echo (isset($_POST['course_level']) && $_POST['course_level'] === 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                            <option value="intermediate" <?php echo (isset($_POST['course_level']) && $_POST['course_level'] === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="advanced" <?php echo (isset($_POST['course_level']) && $_POST['course_level'] === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="course_language">Course Language</label>
                        <select name="course_language" id="course_language">
                            <option value="English" <?php echo (isset($_POST['course_language']) && $_POST['course_language'] === 'English') ? 'selected' : ''; ?>>English</option>
                            <option value="Spanish" <?php echo (isset($_POST['course_language']) && $_POST['course_language'] === 'Spanish') ? 'selected' : ''; ?>>Spanish</option>
                            <option value="French" <?php echo (isset($_POST['course_language']) && $_POST['course_language'] === 'French') ? 'selected' : ''; ?>>French</option>
                            <option value="German" <?php echo (isset($_POST['course_language']) && $_POST['course_language'] === 'German') ? 'selected' : ''; ?>>German</option>
                            <option value="Bengali" <?php echo (isset($_POST['course_language']) && $_POST['course_language'] === 'Bengali') ? 'selected' : ''; ?>>Bengali</option>
                        </select>
                    </div>
                </div>
                
                <h3 class="form-section-title">Categorization</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" id="category_id">
                            <option value="0">Select a Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="university_id">University</label>
                        <select name="university_id" id="university_id">
                            <option value="0">Select a University</option>
                            <?php foreach ($universities as $university): ?>
                                <option value="<?php echo $university['id']; ?>" <?php echo (isset($_POST['university_id']) && $_POST['university_id'] == $university['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($university['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h3 class="form-section-title">Pricing & Enrollment</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="price" class="required">Price ($)</label>
                        <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? '0'); ?>" min="0" step="0.01" required placeholder="Set to 0 for free courses">
                    </div>

                    <div class="form-group">
                        <label for="enrollment_limit">Enrollment Limit</label>
                        <input type="number" id="enrollment_limit" name="enrollment_limit" value="<?php echo htmlspecialchars($_POST['enrollment_limit'] ?? ''); ?>" min="0" placeholder="Set to 0 for unlimited">
                    </div>
                </div>

                <h3 class="form-section-title">Course Media</h3>

                <div class="form-group">
                    <label for="thumbnail">Course Thumbnail</label>
                    <input type="file" id="thumbnail" name="thumbnail" accept="image/*" class="file-input">
                    <p class="help-text">Upload an image (JPEG, PNG, GIF, WebP) - Max 5MB</p>
                </div>
                
                <div class="form-group">
                    <label for="thumbnail_url">OR Thumbnail URL</label>
                    <input type="url" id="thumbnail_url" name="thumbnail_url" value="<?php echo htmlspecialchars($_POST['thumbnail_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                    <p class="help-text">Alternatively, provide a URL to an external image.</p>
                </div>

                <h3 class="form-section-title">SEO & Prerequisites</h3>

                <div class="form-group">
                    <label for="seo_title">SEO Title</label>
                    <input type="text" id="seo_title" name="seo_title" value="<?php echo htmlspecialchars($_POST['seo_title'] ?? ''); ?>" maxlength="255" placeholder="A title optimized for search engines">
                </div>

                <div class="form-group">
                    <label for="meta_description">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" placeholder="A brief summary for search engine results"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="prerequisites">Prerequisites</label>
                    <textarea id="prerequisites" name="prerequisites" placeholder="List any required knowledge, skills, or courses."><?php echo htmlspecialchars($_POST['prerequisites'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="tags">Tags</label>
                    <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" placeholder="web development, javascript, frontend">
                    <p class="help-text">Separate tags with commas.</p>
                </div>

                <h3 class="form-section-title">Publishing Settings</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="status" class="required">Status</label>
                        <select name="status" id="status" required>
                            <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="pending" <?php echo (isset($_POST['status']) && $_POST['status'] === 'pending') ? 'selected' : ''; ?>>Pending Review</option>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo (isset($_POST['status']) && $_POST['status'] === 'archived') ? 'selected' : ''; ?>>Archived</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Certificate of Completion</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="certificate_of_completion" name="certificate_of_completion" value="1" <?php echo (isset($_POST['certificate_of_completion'])) ? 'checked' : ''; ?>>
                            <label for="certificate_of_completion">Enable Certificate</label>
                        </div>
                        <p class="help-text">Students will receive a certificate upon completion.</p>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                    <button type="submit" class="btn btn-primary">Create Course</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>