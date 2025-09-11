<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_withdrawal') {
    $instructor_id = $_SESSION['user_id'];
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_details = trim(filter_input(INPUT_POST, 'payment_details', FILTER_SANITIZE_STRING));

    // Fetch current balance
    $wallet_data = db_select("SELECT id, balance FROM instructor_wallets WHERE instructor_id = ?", 'i', [$instructor_id]);
    $current_balance = $wallet_data[0]['balance'] ?? 0;
    $wallet_id = $wallet_data[0]['id'] ?? null;

    if ($amount > 0 && $amount <= $current_balance && !empty($payment_details) && $wallet_id) {
        global $conn;
        $conn->begin_transaction();
        try {
            // Deduct from wallet
            db_execute("UPDATE instructor_wallets SET balance = balance - ? WHERE id = ?", 'di', [$amount, $wallet_id]);

            // Create withdrawal request
            db_execute(
                "INSERT INTO instructor_withdrawal_requests (instructor_id, amount, payment_details, status) VALUES (?, ?, ?, 'pending')",
                'ids',
                [$instructor_id, $amount, $payment_details]
            );

            $conn->commit();
            $_SESSION['success_message'] = "Withdrawal request for $" . number_format($amount, 2) . " submitted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Invalid amount or payment details. Please check your balance and try again.";
    }
    header('Location: dashboard?page=payouts');
    exit;
}