<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    $_SESSION['error_message'] = "Unauthorized access.";
    header('Location: ../../dashboard?page=payouts');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_withdrawal') {
    $instructor_id = $_SESSION['user_id'];
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_details = trim(filter_input(INPUT_POST, 'payment_details', FILTER_SANITIZE_STRING));

    $wallet = db_select("SELECT * FROM instructor_wallets WHERE instructor_id = ?", 'i', [$instructor_id])[0] ?? null;

    if (!$wallet || !$amount || $amount <= 0 || empty($payment_details) || $amount > $wallet['balance']) {
        $_SESSION['error_message'] = "Invalid amount, missing payment details, or insufficient balance.";
    } else {
        global $conn;
        $conn->begin_transaction();
        try {
            $pending = db_select("SELECT id FROM instructor_withdrawal_requests WHERE instructor_id = ? AND status = 'pending'", 'i', [$instructor_id]);
            if (!empty($pending)) {
                throw new Exception("You already have a pending withdrawal request.");
            }

            db_execute("UPDATE instructor_wallets SET balance = balance - ? WHERE id = ?", 'di', [$amount, $wallet['id']]);
            $request_id = db_execute("INSERT INTO instructor_withdrawal_requests (instructor_id, amount, payment_details) VALUES (?, ?, ?)", 'ids', [$instructor_id, $amount, $payment_details]);

            // --- ADMIN NOTIFICATION ---
            require_once __DIR__ . '/../pages/notifications.php';
            $admin_users = db_select("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC");
            $instructor_name = $_SESSION['user_name'];
            if (!empty($admin_users)) {
                foreach ($admin_users as $admin) {
                    create_notification($admin['id'], "Instructor Payout Request", "Instructor '{$instructor_name}' has requested a payout of $" . number_format($amount, 2) . ".", 'instructor_payout', ['link' => 'dashboard?page=instructor-payouts']);
                }
            }
            // --- END NOTIFICATION ---

            $conn->commit();
            $_SESSION['success_message'] = "Withdrawal request for $" . number_format($amount, 2) . " submitted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    }
}

header('Location: ../../dashboard?page=payouts');
exit;
?>