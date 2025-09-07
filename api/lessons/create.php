<?php
// --- Includes & Session ---
require_once __DIR__ . '/../../includes/db.php'; // Database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Authorization Check ---
// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../account");
    exit;
}

// Check for appropriate role (admin or instructor)
$userRole = $_SESSION['user_role'] ?? 'student';
if ($userRole !== 'admin' && $userRole !== 'instructor') {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>❌ Access Denied!<br>Only Admins or Instructors can create lessons.</h2>";
    exit;
}

// --- Initializations ---
$errors = [];
$success = '';

// --- Success/Error Message Handling from Session ---
if (isset($_SESSION['lesson_creation_success'])) {
    $success = $_SESSION['lesson_creation_success'];
    unset($_SESSION['lesson_creation_success']);
}
if (isset($_SESSION['lesson_creation_error'])) {
    $errors[] = $_SESSION['lesson_creation_error'];
    unset($_SESSION['lesson_creation_error']);
}

// --- Fetch Data for Form Dropdowns ---

// Fetch courses (Admins see all, Instructors see their own)
$coursesQuery = "SELECT id, title FROM courses";
$queryParams = [];
$queryTypes = '';
if ($userRole === 'instructor') {
    $coursesQuery .= " WHERE instructor_id = ?";
    $queryParams[] = $_SESSION['user_id'];
    $queryTypes .= 'i';
}
$coursesQuery .= " ORDER BY title ASC";
$courses = db_select($coursesQuery, $queryTypes, $queryParams);

// Fetch quizzes and assignments (placeholders, assuming these tables exist)
$quizzes = db_select("SELECT id, title FROM quizzes ORDER BY title ASC");
$assignments = db_select("SELECT id, title FROM assignments ORDER BY title ASC");


// --- Form Submission Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Sanitize and Retrieve Form Data ---
    $course_id = intval($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $order_no = intval($_POST['order_no'] ?? 0);
    $duration = trim($_POST['duration'] ?? '');
    
    // Content types
    $content_type = $_POST['content_type'] ?? 'video_url';
    $video_url = trim($_POST['video_url'] ?? '');
    $article_content = trim($_POST['article_content'] ?? '');
    $external_link = trim($_POST['external_link'] ?? '');

    // Settings
    $status = in_array($_POST['status'] ?? 'draft', ['draft', 'published']) ? $_POST['status'] : 'draft';
    $is_preview = isset($_POST['is_preview']) ? 1 : 0;
    $is_locked = isset($_POST['is_locked']) ? 1 : 0;
    $release_date = !empty($_POST['release_date']) ? trim($_POST['release_date']) : null;

    // Add-ons
    $quiz_id = intval($_POST['quiz_id'] ?? 0);
    $assignment_id = intval($_POST['assignment_id'] ?? 0);

    // --- Validation ---
    if (empty($title)) $errors[] = "Lesson title is required.";
    if ($course_id === 0) $errors[] = "You must select a course.";
    
    // Validate content based on type
    $video_file_path = null;
    $document_file_path = null;

    if ($content_type === 'video_url' && !empty($video_url) && !filter_var($video_url, FILTER_VALIDATE_URL)) {
        $errors[] = "The provided video link is not a valid URL.";
    }
    if ($content_type === 'external_link' && !empty($external_link) && !filter_var($external_link, FILTER_VALIDATE_URL)) {
        $errors[] = "The provided external link is not a valid URL.";
    }

    // --- File Upload Handling ---
    $uploadDir = __DIR__ . '/../uploads/lessons/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle Video Upload
    if ($content_type === 'video_upload' && isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = uniqid('lesson_vid_') . basename($_FILES['video_file']['name']);
        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadPath)) {
            $video_file_path = 'uploads/lessons/' . $fileName;
        } else {
            $errors[] = "Failed to upload video file.";
        }
    }

    // Handle Document Upload
    if ($content_type === 'document_upload' && isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = uniqid('lesson_doc_') . basename($_FILES['document_file']['name']);
        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $uploadPath)) {
            $document_file_path = 'uploads/lessons/' . $fileName;
        } else {
            $errors[] = "Failed to upload document file.";
        }
    }

    // Handle multiple resource attachments
    $attachment_paths = [];
    if (isset($_FILES['attachments'])) {
        foreach ($_FILES['attachments']['name'] as $key => $name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = uniqid('lesson_res_') . basename($name);
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $uploadPath)) {
                    $attachment_paths[] = [
                        'name' => $name,
                        'path' => 'uploads/lessons/' . $fileName
                    ];
                } else {
                    $errors[] = "Failed to upload attachment: " . htmlspecialchars($name);
                }
            }
        }
    }

    // --- Database Insertion ---
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $sql = "INSERT INTO lessons (course_id, title, description, order_no, duration, video_url, video_file_path, document_file_path, article_content, external_link, status, is_preview, release_date, is_locked, quiz_id, assignment_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);

            // Add this check for better debugging
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Use null for empty optional values
            $q_id = $quiz_id > 0 ? $quiz_id : null;
            $a_id = $assignment_id > 0 ? $assignment_id : null;

            $stmt->bind_param(
                "ississsssssisiii",
                $course_id,
                $title,
                $description,
                $order_no,
                $duration,
                $video_url,
                $video_file_path,
                $document_file_path,
                $article_content,
                $external_link,
                $status,
                $is_preview,
                $release_date,
                $is_locked,
                $q_id,
                $a_id
            );

            if ($stmt->execute()) {
                $lesson_id = $conn->insert_id;

                // Insert attachments if any
                if (!empty($attachment_paths)) {
                    $attachSql = "INSERT INTO lesson_resources (lesson_id, file_name, file_path) VALUES (?, ?, ?)";
                    $attachStmt = $conn->prepare($attachSql);

                    if ($attachStmt === false) {
                        throw new Exception("Prepare for attachments failed: " . $conn->error);
                    }

                    foreach ($attachment_paths as $attachment) {
                        $attachStmt->bind_param("iss", $lesson_id, $attachment['name'], $attachment['path']);
                        $attachStmt->execute();
                    }
                    $attachStmt->close();
                }

                $conn->commit();
                $_SESSION['lesson_creation_success'] = "Lesson '{$title}' created successfully!";
                // Use a proper redirect to avoid issues with headers already sent.
                // The JS redirect is fine here as it's after a successful transaction.
                echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
                exit;
            } else {
                throw new Exception("Database error on execute: " . $stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Lesson - LMS</title>
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        :root {
            --primary-color: #8b5cf6;
            --primary-light: #a78bfa;
            --secondary-color: #f3f4f6;
            --text-color: #374151;
            --placeholder-color: #9ca3af;
            --bg-color: #e5e7eb;
            --card-bg: #ffffff;
            --border-color: #d1d5db;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-dark: rgba(0, 0, 0, 0.12);
        }

        .app-container {
            width: 1280px;
            height: 820px;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 20px var(--shadow-light), 0 6px 6px var(--shadow-dark);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.5s ease-in-out;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: 4px solid var(--primary-color);
        }

        .form-header h1 { font-size: 1.75rem; font-weight: 700; }
        .form-header p { font-size: 0.9rem; opacity: 0.9; }

        .form-container {
            flex: 1;
            padding: 1.5rem 2rem;
            overflow-y: auto;
        }
        
        .form-section-title {
            font-size: 1.2rem; font-weight: 600; color: var(--primary-color);
            margin: 1.5rem 0 1rem; border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }
        .form-section-title:first-of-type { margin-top: 0; }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .form-group { margin-bottom: 1.2rem; }
        
        label {
            display: block; margin-bottom: 0.5rem; font-weight: 600;
            font-size: 0.85rem; color: #4b5563;
        }
        
        input:not([type="checkbox"]), textarea, select {
            width: 100%; padding: 10px; border: 1px solid var(--border-color);
            border-radius: 6px; font-size: 0.9rem; color: var(--text-color);
            background-color: var(--secondary-color); transition: all 0.3s ease-in-out;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none; border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2); background-color: #fff;
        }
        
        .help-text { font-size: 0.75rem; color: var(--placeholder-color); margin-top: 0.5rem; }
        .required::after { content: '*'; color: #ef4444; margin-left: 4px; }
        
        .btn-group { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; }
        .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease-in-out; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-secondary { background: var(--secondary-color); color: var(--text-color); border: 1px solid var(--border-color); }
        
        .alert { padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        
        .checkbox-group { display: flex; align-items: center; gap: 0.75rem; }
        .checkbox-group input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--primary-color); }

        .content-type-selector label {
            display: inline-block; padding: 8px 16px; margin-right: 10px;
            border: 1px solid var(--border-color); border-radius: 20px;
            cursor: pointer; transition: all 0.2s ease;
        }
        .content-type-selector input[type="radio"] { display: none; }
        .content-type-selector input[type="radio"]:checked + label {
            background-color: var(--primary-color); color: white; border-color: var(--primary-color);
        }
        .content-panel { display: none; border-left: 3px solid var(--primary-color); padding-left: 1rem; margin-top: 1rem; }
        .content-panel.active { display: block; }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="form-header">
            <h1>Create a New Lesson</h1>
            <p>Build out your course by adding lessons with various content types and settings.</p>
        </div>
        
        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $error): ?><li>• <?= htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <h3 class="form-section-title">Basic Information</h3>
                <div class="form-group">
                    <label for="course_id" class="required">Course</label>
                    <select name="course_id" id="course_id" required>
                        <option value="">-- Select a Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id']; ?>"><?= htmlspecialchars($course['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title" class="required">Lesson Title</label>
                        <input type="text" id="title" name="title" required placeholder="e.g., Introduction to Variables">
                    </div>
                    <div class="form-group">
                        <label for="duration">Lesson Duration</label>
                        <input type="text" id="duration" name="duration" placeholder="e.g., 15 min, 1 hr 20 min">
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Lesson Description</label>
                    <textarea id="description" name="description" class="rich-text-editor"></textarea>
                </div>

                <h3 class="form-section-title">Lesson Content</h3>
                <div class="form-group content-type-selector">
                    <input type="radio" id="type_video_url" name="content_type" value="video_url" checked><label for="type_video_url">Video URL</label>
                    <input type="radio" id="type_video_upload" name="content_type" value="video_upload"><label for="type_video_upload">Upload Video</label>
                    <input type="radio" id="type_article" name="content_type" value="article"><label for="type_article">Article/Text</label>
                    <input type="radio" id="type_document" name="content_type" value="document_upload"><label for="type_document">Upload Document</label>
                    <input type="radio" id="type_external_link" name="content_type" value="external_link"><label for="type_external_link">External Link</label>
                </div>
                
                <div id="panel_video_url" class="content-panel active">
                    <div class="form-group"><label for="video_url">YouTube/Vimeo URL</label><input type="url" name="video_url" id="video_url" placeholder="https://www.youtube.com/watch?v=..."></div>
                </div>
                <div id="panel_video_upload" class="content-panel">
                    <div class="form-group"><label for="video_file">Video File</label><input type="file" name="video_file" id="video_file" accept="video/*"></div>
                </div>
                <div id="panel_article" class="content-panel">
                    <div class="form-group"><label for="article_content">Article Content</label><textarea name="article_content" id="article_content" class="rich-text-editor"></textarea></div>
                </div>
                <div id="panel_document_upload" class="content-panel">
                    <div class="form-group"><label for="document_file">Document (PDF, PPT, DOC)</label><input type="file" name="document_file" id="document_file" accept=".pdf,.doc,.docx,.ppt,.pptx"></div>
                </div>
                <div id="panel_external_link" class="content-panel">
                    <div class="form-group"><label for="external_link">Resource or Live Class URL</label><input type="url" name="external_link" id="external_link" placeholder="https://zoom.us/j/..."></div>
                </div>

                <h3 class="form-section-title">Lesson Settings</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="status" class="required">Status</label>
                        <select name="status" id="status" required>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="order_no">Lesson Order</label>
                        <input type="number" id="order_no" name="order_no" min="0" value="0">
                        <p class="help-text">Controls the lesson sequence. 0 is first.</p>
                    </div>
                    <div class="form-group">
                        <label for="release_date">Drip Schedule (Optional)</label>
                        <input type="datetime-local" id="release_date" name="release_date">
                        <p class="help-text">Set a future date to release this lesson.</p>
                    </div>
                    <div class="form-group">
                        <label>Options</label>
                        <div class="checkbox-group"><input type="checkbox" id="is_preview" name="is_preview" value="1"><label for="is_preview">Free Preview Lesson</label></div>
                        <div class="checkbox-group" style="margin-top: 10px;"><input type="checkbox" id="is_locked" name="is_locked" value="1"><label for="is_locked">Lock Lesson (requires previous completion)</label></div>
                    </div>
                </div>

                <h3 class="form-section-title">Optional Add-ons</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="quiz_id">Attach Quiz</label>
                        <select name="quiz_id" id="quiz_id">
                            <option value="0">-- No Quiz --</option>
                            <?php foreach ($quizzes as $quiz): ?><option value="<?= $quiz['id']; ?>"><?= htmlspecialchars($quiz['title']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="assignment_id">Attach Assignment</label>
                        <select name="assignment_id" id="assignment_id">
                            <option value="0">-- No Assignment --</option>
                            <?php foreach ($assignments as $assignment): ?><option value="<?= $assignment['id']; ?>"><?= htmlspecialchars($assignment['title']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="attachments">Attachments / Resources</label>
                    <input type="file" name="attachments[]" id="attachments" multiple>
                    <p class="help-text">Upload extra files like slides, worksheets, etc. (Hold Ctrl/Cmd to select multiple).</p>
                </div>

                <div class="btn-group">
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                    <button type="submit" class="btn btn-primary">Create Lesson</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Rich Text Editor Initialization
            tinymce.init({
                selector: '.rich-text-editor',
                plugins: 'lists link image table code help wordcount',
                toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image | code'
            });

            // Content Type Panel Switcher
            const contentRadios = document.querySelectorAll('.content-type-selector input[type="radio"]');
            const contentPanels = document.querySelectorAll('.content-panel');

            function switchPanel() {
                const selectedType = document.querySelector('.content-type-selector input[type="radio"]:checked').value;
                contentPanels.forEach(panel => {
                    if (panel.id === `panel_${selectedType}`) {
                        panel.classList.add('active');
                    } else {
                        panel.classList.remove('active');
                    }
                });
            }

            contentRadios.forEach(radio => radio.addEventListener('change', switchPanel));
        });
    </script>
</body>
</html>