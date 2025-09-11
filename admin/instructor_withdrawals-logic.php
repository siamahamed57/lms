<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_instructor_withdrawal'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $admin_notes = filter_input(INPUT_POST, 'admin_notes', FILTER_SANITIZE_STRING);

    if ($request_id && in_array($status, ['approved', 'rejected'])) {
        $req_data = db_select("SELECT * FROM instructor_withdrawal_requests WHERE id = ? AND status = 'pending'", 'i', [$request_id]);
        
        if (!empty($req_data)) {
            $request = $req_data[0];
            db_execute("UPDATE instructor_withdrawal_requests SET status = ?, admin_notes = ?, processed_at = NOW() WHERE id = ?", 'ssi', [$status, $admin_notes, $request_id]);

            if ($status == 'rejected') {
                // Refund the amount to instructor's wallet
                db_execute("UPDATE instructor_wallets SET balance = balance + ? WHERE instructor_id = ?", 'di', [$request['amount'], $request['instructor_id']]);
            }
            
            $_SESSION['success_message'] = "Instructor withdrawal request updated.";
        } else {
            $_SESSION['error_message'] = "Request not found or already processed.";
        }
    }
    header('Location: dashboard?page=instructor-payouts');
    exit;
}