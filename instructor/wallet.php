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

// Fetch wallet balance
$wallet_sql = "SELECT balance FROM instructor_wallets WHERE instructor_id = ?";
$wallet_data = db_select($wallet_sql, 'i', [$instructor_id]);
$balance = $wallet_data[0]['balance'] ?? 0.00;

// Fetch earnings history
$earnings_sql = "SELECT ie.earned_at, ie.earned_amount, c.title as course_title 
                 FROM instructor_earnings ie
                 JOIN courses c ON ie.course_id = c.id
                 WHERE ie.instructor_id = ? 
                 ORDER BY ie.earned_at DESC LIMIT 10";
$earnings = db_select($earnings_sql, 'i', [$instructor_id]);

?>
<style>
/* Basic styling for wallet page */
.card { background: rgba(255, 255, 255, 0.07); backdrop-filter: blur(15px); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); }
.card-body { padding: 1.5rem; }
.wallet-balance { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
.form-control { width: 100%; padding: 0.75rem 1rem; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: #f0f0f0; }
.btn-primary { background-color: #b915ff; color: #fff; padding: 0.7rem 1.5rem; border-radius: 8px; border: none; cursor: pointer; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
.alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
.alert-success { background-color: rgba(40, 167, 69, 0.15); color: #a3ffb8; }
.alert-danger { background-color: rgba(220, 53, 69, 0.15); color: #ffacb3; }
</style>

<h3>My Wallet</h3>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-1">
        <div class="card wallet-balance text-center">
            <div class="card-body">
                <h5 class="text-lg opacity-80">Current Balance</h5>
                <p class="text-5xl font-bold mt-2">$<?= number_format($balance, 2) ?></p>
            </div>
        </div>
        <div class="card mt-6">
            <div class="card-body">
                <h4>Request Withdrawal</h4>
                <form method="POST" action="dashboard?page=payouts">
                    <input type="hidden" name="action" value="request_withdrawal">
                    <div class="form-group mb-4">
                        <label for="amount">Amount ($)</label>
                        <input type="number" name="amount" id="amount" class="form-control" step="0.01" max="<?= $balance ?>" required>
                    </div>
                    <div class="form-group mb-4">
                        <label for="payment_details">Payment Details</label>
                        <textarea name="payment_details" id="payment_details" class="form-control" rows="3" placeholder="e.g., Bank Name, Account Number, bKash/Nagad Number" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
    <div class="md:col-span-2">
        <div class="card">
            <div class="card-body">
                <h4>Recent Earnings</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($earnings)): ?>
                            <tr><td colspan="3" class="text-center">No earnings yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($earnings as $earning): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($earning['earned_at'])) ?></td>
                                    <td><?= htmlspecialchars($earning['course_title']) ?></td>
                                    <td>$<?= number_format($earning['earned_amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                 <div class="text-right mt-4">
                    <a href="dashboard?page=payout-history">View Withdrawal History &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>