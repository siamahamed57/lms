<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_withdrawal'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $admin_notes = filter_input(INPUT_POST, 'admin_notes', FILTER_SANITIZE_STRING);

    if ($request_id && in_array($status, ['approved', 'rejected'])) {
        $req = db_select("SELECT * FROM withdrawal_requests WHERE id = ? AND status = 'pending'", 'i', [$request_id]);
        if (!empty($req)) {
            $request = $req[0];
            db_execute("UPDATE withdrawal_requests SET status = ?, admin_notes = ?, processed_at = NOW() WHERE id = ?", 'ssi', [$status, $admin_notes, $request_id]);

            if ($status == 'rejected') {
                db_execute("UPDATE student_wallets SET balance = balance + ? WHERE student_id = ?", 'di', [$request['amount'], $request['student_id']]);
                $wallet = db_select("SELECT id FROM student_wallets WHERE student_id = ?", 'i', [$request['student_id']])[0];
                $desc = "Withdrawal request #$request_id rejected. Amount refunded.";
                db_execute("INSERT INTO wallet_transactions (wallet_id, amount, type, description) VALUES (?, ?, 'withdrawal_rejected', ?)", 'ids', [$wallet['id'], $request['amount'], $desc]);
            }
            $_SESSION['success_message'] = "Withdrawal request updated.";
        }
    }
}

echo json_encode(['success' => true]);
exit;