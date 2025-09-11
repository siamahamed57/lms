<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    $_SESSION['withdrawal_error'] = "You are not authorized to perform this action.";
    header('Location: dashboard?page=my-wallet');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_withdrawal') {
    $student_id = $_SESSION['user_id'];
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_details = trim(filter_input(INPUT_POST, 'payment_details', FILTER_SANITIZE_STRING));

    $wallet = db_select("SELECT * FROM student_wallets WHERE student_id = ?", 'i', [$student_id])[0] ?? null;

    if (!$wallet || !$amount || $amount <= 0 || empty($payment_details) || $amount > $wallet['balance']) {
        $_SESSION['withdrawal_error'] = "Invalid amount, missing payment details, or insufficient balance.";
    } else {
        global $conn;
        $conn->begin_transaction();

        try {
            $pending = db_select("SELECT id FROM withdrawal_requests WHERE student_id = ? AND status = 'pending'", 'i', [$student_id]);
            if (!empty($pending)) {
                throw new Exception("You already have a pending withdrawal request.");
            }

            // 1. Create withdrawal request
            $request_id = db_execute(
                "INSERT INTO withdrawal_requests (student_id, amount, payment_details) VALUES (?, ?, ?)",
                'ids',
                [$student_id, $amount, $payment_details]
            );

            // 2. Deduct from wallet balance
            db_execute("UPDATE student_wallets SET balance = balance - ? WHERE id = ?", 'di', [$amount, $wallet['id']]);

            // 3. Log transaction
            $desc = "Withdrawal request #{$request_id} submitted.";
            db_execute("INSERT INTO wallet_transactions (wallet_id, amount, type, description, related_id) VALUES (?, ?, 'withdrawal_request', ?, ?)", 'idsi', [$wallet['id'], -$amount, $desc, $request_id]);

            $conn->commit();
            $_SESSION['withdrawal_success'] = "Withdrawal request for $" . number_format($amount, 2) . " submitted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['withdrawal_error'] = "Error: " . $e->getMessage();
        }
    }
}

header('Location: dashboard?page=my-wallet');
exit;