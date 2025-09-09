<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authorization check - only admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['coupon_management_error'] = "You are not authorized to perform this action.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../../dashboard?page=manage-coupons'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- Handle Create ---
    if ($_POST['action'] === 'create_coupon') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = $_POST['type'] ?? 'fixed';
        $value = floatval($_POST['value'] ?? 0);
        $expires_at = !empty($_POST['expires_at']) ? trim($_POST['expires_at']) : null;
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;

        $errors = [];
        if (empty($code)) $errors[] = "Coupon code is required.";
        if (!in_array($type, ['fixed', 'percentage'])) $errors[] = "Invalid coupon type.";
        if ($value <= 0) $errors[] = "Discount value must be greater than zero.";
        if ($type === 'percentage' && $value > 100) $errors[] = "Percentage discount cannot exceed 100.";

        // Check if code already exists
        $existing_code = db_select("SELECT id FROM coupons WHERE code = ?", "s", [$code]);
        if (!empty($existing_code)) {
            $errors[] = "This coupon code already exists.";
        }

        if (empty($errors)) {
            $sql = "INSERT INTO coupons (code, type, value, expires_at, usage_limit) VALUES (?, ?, ?, ?, ?)";
            
            $new_coupon_id = db_execute($sql, "ssdsi", [$code, $type, $value, $expires_at, $usage_limit]);

            if ($new_coupon_id) {
                $_SESSION['coupon_management_success'] = "Coupon '{$code}' created successfully!";
            } else {
                $_SESSION['coupon_management_error'] = "Database error: Could not create the coupon.";
            }
        } else {
            $_SESSION['coupon_management_error'] = implode("<br>", $errors);
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // --- Handle Delete ---
    if ($_POST['action'] === 'delete_coupon') {
        $coupon_id_to_delete = intval($_POST['coupon_id_to_delete'] ?? 0);
        if ($coupon_id_to_delete > 0) {
            db_execute("DELETE FROM coupons WHERE id = ?", "i", [$coupon_id_to_delete]);
            $_SESSION['coupon_management_success'] = "Coupon has been deleted successfully.";
        } else {
            $_SESSION['coupon_management_error'] = "Invalid Coupon ID for deletion.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // --- Handle Update (Example, can be expanded) ---
    if ($_POST['action'] === 'update_coupon') {
        $coupon_id = intval($_POST['coupon_id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = $_POST['type'] ?? 'fixed';
        $value = floatval($_POST['value'] ?? 0);
        $expires_at = !empty($_POST['expires_at']) ? trim($_POST['expires_at']) : null;
        $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        $status = $_POST['status'] ?? 'active';

        $errors = [];
        if ($coupon_id <= 0) $errors[] = "Invalid Coupon ID.";
        if (empty($code)) $errors[] = "Coupon code is required.";
        if (!in_array($type, ['fixed', 'percentage'])) $errors[] = "Invalid coupon type.";
        if ($value <= 0) $errors[] = "Discount value must be greater than zero.";
        if ($type === 'percentage' && $value > 100) $errors[] = "Percentage discount cannot exceed 100.";
        if (!in_array($status, ['active', 'inactive'])) $errors[] = "Invalid status.";

        // Check if code already exists on a *different* coupon
        $existing_code = db_select("SELECT id FROM coupons WHERE code = ? AND id != ?", "si", [$code, $coupon_id]);
        if (!empty($existing_code)) {
            $errors[] = "This coupon code is already in use by another coupon.";
        }

        if (empty($errors)) {
            $sql = "UPDATE coupons SET code = ?, type = ?, value = ?, expires_at = ?, usage_limit = ?, status = ? WHERE id = ?";
            $success = db_execute($sql, "ssdsssi", [$code, $type, $value, $expires_at, $usage_limit, $status, $coupon_id]);

            if ($success) {
                $_SESSION['coupon_management_success'] = "Coupon '{$code}' updated successfully!";
            } else {
                $_SESSION['coupon_management_error'] = "Database error: Could not update the coupon.";
            }
        } else {
            $_SESSION['coupon_management_error'] = implode("<br>", $errors);
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Fallback redirect
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../../dashboard?page=manage-coupons'));
exit;

?>