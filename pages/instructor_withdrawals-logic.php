<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../pages/notifications.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /lms/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_instructor_withdrawal'])) {
    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes']);

    if ($request_id > 0 && in_array($status, ['approved', 'rejected'])) {
        global $conn;
        $conn->begin_transaction();
        try {
            // Get request details for notification and potential refund
            $request_details_raw = db_select("SELECT * FROM instructor_withdrawal_requests WHERE id = ?", 's', [$request_id]);
            if (empty($request_details_raw)) {
                throw new Exception("Withdrawal request not found.");
            }
            $request_details = $request_details_raw[0];
            $instructor_id = $request_details['instructor_id'];
            $amount = $request_details['amount'];

            // Update the request
            db_execute(
                "UPDATE instructor_withdrawal_requests SET status = ?, admin_notes = ?, processed_at = NOW() WHERE id = ?",
                'sss',
                [$status, $admin_notes, $request_id]
            );

            // If rejected, refund the amount to the instructor's wallet
            if ($status === 'rejected') {
                db_execute("UPDATE instructor_wallets SET balance = balance + ? WHERE instructor_id = ?", 'ds', [$amount, (string)$instructor_id]);
                
                // Notify instructor of rejection
                create_notification($instructor_id, "Payout Request Rejected", "Your payout request for $" . number_format($amount, 2) . " was rejected. The amount has been returned to your wallet. Note: " . $admin_notes, 'payout_rejected', ['link' => 'dashboard?page=payouts']);

            } else { // Approved
                // Notify instructor of approval
                create_notification($instructor_id, "Payout Processed", "Your payout request for $" . number_format($amount, 2) . " has been approved and is being processed.", 'payout_approved', ['link' => 'dashboard?page=payout-history']);
            }

            $conn->commit();
            $_SESSION['success_message'] = "Withdrawal request updated successfully.";

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Invalid data provided.";
    }
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'dashboard?page=instructor-payouts'));
exit;
?>