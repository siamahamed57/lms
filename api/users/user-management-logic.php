<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id_to_change = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $new_role = filter_input(INPUT_POST, 'new_role', FILTER_SANITIZE_STRING);
    $current_admin_id = $_SESSION['user_id'];

    $allowed_roles = ['student', 'instructor', 'admin'];

    // --- Validation and Security Checks ---
    if (!$user_id_to_change || !in_array($new_role, $allowed_roles)) {
        $_SESSION['user_mgmt_error'] = "Invalid data provided.";
    } elseif ($user_id_to_change == $current_admin_id) {
        $_SESSION['user_mgmt_error'] = "You cannot change your own role.";
    } else {
        // Check if the target user is another admin. Admins cannot change other admins' roles.
        $target_user_data = db_select("SELECT role FROM users WHERE id = ?", 'i', [$user_id_to_change]);
        if (!empty($target_user_data) && $target_user_data[0]['role'] === 'admin') {
            $_SESSION['user_mgmt_error'] = "You cannot change the role of another administrator.";
        } else {
            // --- Proceed with Role Change ---
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $success = db_execute($sql, 'si', [$new_role, $user_id_to_change]);

            if ($success) {
                // If changing role to instructor, ensure they have a wallet
                if ($new_role === 'instructor') {
                    $wallet_exists = db_select("SELECT id FROM instructor_wallets WHERE instructor_id = ?", 'i', [$user_id_to_change]);
                    if (empty($wallet_exists)) {
                        db_execute("INSERT INTO instructor_wallets (instructor_id, balance) VALUES (?, 0.00)", 'i', [$user_id_to_change]);
                    }
                }
                $_SESSION['user_mgmt_success'] = "User role updated successfully.";
            } else {
                $_SESSION['user_mgmt_error'] = "Failed to update user role.";
            }
        }
    }

    // Redirect back to the user management page
    $redirect_url = 'dashboard?page=users' . (isset($_GET['role']) ? '&role=' . urlencode($_GET['role']) : '');
    header('Location: ' . $redirect_url);
    exit;
}