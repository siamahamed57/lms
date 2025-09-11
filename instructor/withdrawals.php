<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header('Location: /lms/login');
    exit;
}

$instructor_id = $_SESSION['user_id'];

// Fetch withdrawal history
$sql = "SELECT * FROM instructor_withdrawal_requests WHERE instructor_id = ? ORDER BY requested_at DESC";
$requests = db_select($sql, 'i', [$instructor_id]);
?>
<style>
/* Reusing styles from admin/withdrawals.php */
.card { background: rgba(255, 255, 255, 0.07); backdrop-filter: blur(15px); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.2); }
.card-body { padding: 0; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 1rem 1.25rem; text-align: left; vertical-align: middle; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
.table thead th { color: #a0a0a0; font-weight: 500; text-transform: uppercase; font-size: 0.8rem; }
.table tbody tr:last-child td { border-bottom: none; }
.badge { display: inline-block; padding: 0.4em 0.7em; font-size: 0.8rem; font-weight: 600; border-radius: 1rem; color: #fff; }
.badge-success { background-color: rgba(40, 167, 69, 0.5); }
.badge-danger { background-color: rgba(220, 53, 69, 0.5); }
.badge-warning { background-color: rgba(255, 193, 7, 0.5); color: #111; }
pre { background: rgba(0, 0, 0, 0.3); padding: 0.5rem; border-radius: 6px; white-space: pre-wrap; color: #a0a0a0; }
</style>

<h3>My Withdrawal History</h3>
<p>Track the status of your payout requests.</p>

<div class="card mt-3">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Requested At</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment Details</th>
                    <th>Processed At</th>
                    <th>Admin Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="6" class="text-center">You have not made any withdrawal requests.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><?= date('M d, Y H:i', strtotime($req['requested_at'])) ?></td>
                        <td>$<?= number_format($req['amount'], 2) ?></td>
                        <td><span class="badge badge-<?= $req['status'] == 'approved' ? 'success' : ($req['status'] == 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($req['status']) ?></span></td>
                        <td><pre><?= htmlspecialchars($req['payment_details']) ?></pre></td>
                        <td><?= $req['processed_at'] ? date('M d, Y', strtotime($req['processed_at'])) : 'N/A' ?></td>
                        <td><?= htmlspecialchars($req['admin_notes'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>