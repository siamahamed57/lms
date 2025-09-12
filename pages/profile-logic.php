<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization check
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    $errors = [];

    // --- Avatar Upload Handling ---
    $avatar_path_to_db = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $errors[] = "Failed to create avatar upload directory.";
            }
        }

        if (empty($errors)) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $_FILES['avatar']['tmp_name']);
            finfo_close($fileInfo);

            if (in_array($mimeType, $allowedTypes) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) { // Max 2MB
                // Delete old avatar if it exists and is not a default one
                $old_avatar_data = db_select("SELECT avatar FROM users WHERE id = ?", 'i', [$user_id]);
                if (!empty($old_avatar_data[0]['avatar'])) {
                    $old_avatar_file = __DIR__ . '/../' . $old_avatar_data[0]['avatar'];
                    if (file_exists($old_avatar_file) && strpos($old_avatar_data[0]['avatar'], 'default') === false) {
                        @unlink($old_avatar_file);
                    }
                }

                $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $fileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
                    $avatar_path_to_db = 'uploads/avatars/' . $fileName;
                } else {
                    $errors[] = "Failed to move uploaded avatar file.";
                }
            } else {
                $errors[] = "Invalid file type or size (max 2MB). Allowed: JPG, PNG, GIF, WebP.";
            }
        }
    }

    // Common fields
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (empty($name)) {
        $errors[] = "Your name cannot be empty.";
    }

    // --- Phone Number Uniqueness Validation ---
    if (!empty($phone)) {
        $existing_user_with_phone = db_select("SELECT id FROM users WHERE phone = ? AND id != ?", 'si', [$phone, $user_id]);
        if (!empty($existing_user_with_phone)) {
            $errors[] = "This phone number is already registered to another account.";
        }
    }

    // --- Dynamically build the SQL query ---
    $sql_parts = ['name = ?'];
    $sql_types = 's';
    $sql_params = [$name];

    if ($avatar_path_to_db) {
        $sql_parts[] = 'avatar = ?';
        $sql_types .= 's';
        $sql_params[] = $avatar_path_to_db;
    }

    if ($user_role === 'student') {
        $university = trim($_POST['university'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $roll_no = trim($_POST['roll_no'] ?? '');

        if (empty($phone)) $errors[] = "Phone number is required.";
        if (empty($university)) $errors[] = "University is required.";
        if (empty($department)) $errors[] = "Department is required.";
        
        array_push($sql_parts, 'phone = ?', 'bio = ?', 'university = ?', 'department = ?', 'roll_no = ?');
        $sql_types .= 'sssss';
        array_push($sql_params, $phone, $bio, $university, $department, $roll_no);

    } elseif ($user_role === 'instructor') {
        $payout_details = trim($_POST['payout_details'] ?? '');

        if (empty($phone)) $errors[] = "Phone number is required.";
        if (empty($bio)) $errors[] = "A short bio is required.";

        array_push($sql_parts, 'phone = ?', 'bio = ?', 'payout_details = ?');
        $sql_types .= 'sss';
        array_push($sql_params, $phone, $bio, $payout_details);
    } else { // For admin or any other roles
        array_push($sql_parts, 'phone = ?', 'bio = ?');
        $sql_types .= 'ss';
        array_push($sql_params, $phone, $bio);
    }

    if (empty($errors)) {
        $sql_types .= 'i';
        $sql_params[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?";
        $updated = db_execute($sql, $sql_types, $sql_params);

        if ($updated) {
            $_SESSION['profile_success'] = "Your profile has been updated successfully!";
            $_SESSION['user_name'] = $name; // Update session name
            if ($avatar_path_to_db) {
                $_SESSION['user_avatar'] = $avatar_path_to_db; // Update session avatar
            }
            // Redirect back to the profile page to see changes.
            header('Location: dashboard?page=profile');
            exit;
        } else {
            $_SESSION['profile_error'] = "A database error occurred. Your profile was not updated.";
        }
    } else {
        $_SESSION['profile_error'] = implode("<br>", $errors);
    }

    // If there was a validation error or the update failed, redirect back to the profile page.
    header('Location: dashboard?page=profile');
    exit;
}