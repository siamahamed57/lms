<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdrawal'])) {
    $student_id = $_SESSION['user_id'];
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_details = filter_input(INPUT_POST, 'payment_details', FILTER_SANITIZE_STRING);

    $wallet = db_select("SELECT * FROM student_wallets WHERE student_id = ?", 'i', [$student_id])[0];

    if (!$amount || $amount <= 0 || !$payment_details || $amount > $wallet['balance']) {
        $_SESSION['error_message'] = "Invalid amount, details provided, or insufficient balance.";
    } else {
        global $conn;
        $conn->begin_transaction();
        try {
            $pending = db_select("SELECT id FROM withdrawal_requests WHERE student_id = ? AND status = 'pending'", 'i', [$student_id]);
            if (!empty($pending)) {
                throw new Exception("You already have a pending withdrawal request.");
            }

            db_execute("UPDATE student_wallets SET balance = balance - ? WHERE id = ?", 'di', [$amount, $wallet['id']]);
            $request_id = db_execute("INSERT INTO withdrawal_requests (student_id, amount, payment_details) VALUES (?, ?, ?)", 'ids', [$student_id, $amount, $payment_details]);
            $desc = "Withdrawal request #{$request_id} submitted.";
            $negative_amount = -$amount;
            db_execute("INSERT INTO wallet_transactions (wallet_id, amount, type, description, related_id) VALUES (?, ?, 'withdrawal_request', ?, ?)", 'idsi', [$wallet['id'], $negative_amount, $desc, $request_id]);

            $conn->commit();
            $_SESSION['success_message'] = "Withdrawal request submitted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
}

echo json_encode(['success' => true]);
exit;