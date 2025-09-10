<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Helper function to get a Bootstrap badge class based on status.
 */
function get_status_badge_class($status) {
    switch ($status) {
        case 'approved':
            return 'badge-success';
        case 'rejected':
            return 'badge-danger';
        case 'pending':
            return 'badge-warning';
        default:
            return 'badge-info';
    }
}

require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: /lms/login');
    exit;
}

$student_id = $_SESSION['user_id'];

// Ensure wallet exists
$wallet = db_select("SELECT * FROM student_wallets WHERE student_id = ?", 'i', [$student_id]);
if (empty($wallet)) {
    // Use the correct helper function from db.php
    db_execute("INSERT INTO student_wallets (student_id, balance) VALUES (?, 0.00)", 'i', [$student_id]);
    $wallet = db_select("SELECT * FROM student_wallets WHERE student_id = ?", 'i', [$student_id]);
}
$wallet = $wallet[0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdrawal'])) {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_details = filter_input(INPUT_POST, 'payment_details', FILTER_SANITIZE_STRING);
    
    if (!$amount || $amount <= 0 || !$payment_details || $amount > $wallet['balance']) {
        $_SESSION['error_message'] = "Invalid amount, details provided, or insufficient balance.";
    } else {
        // Use a transaction to ensure all-or-nothing database operations.
        global $conn;
        $conn->begin_transaction();

        try {
            $pending = db_select("SELECT id FROM withdrawal_requests WHERE student_id = ? AND status = 'pending'", 'i', [$student_id]);
            if (!empty($pending)) {
                throw new Exception("You already have a pending withdrawal request.");
            }

            // 1. Deduct from wallet
            db_execute("UPDATE student_wallets SET balance = balance - ? WHERE id = ?", 'di', [$amount, $wallet['id']]);

            // 2. Create withdrawal request
            $request_id = db_execute("INSERT INTO withdrawal_requests (student_id, amount, payment_details) VALUES (?, ?, ?)", 'ids', [$student_id, $amount, $payment_details]);

            // 3. Log the withdrawal request transaction
            $desc = "Withdrawal request #{$request_id} submitted.";
            $negative_amount = -$amount;
            db_execute("INSERT INTO wallet_transactions (wallet_id, amount, type, description, related_id) VALUES (?, ?, 'withdrawal_request', ?, ?)", 'idsi', [$wallet['id'], $negative_amount, $desc, $request_id]);

            // All good, commit the transaction
            $conn->commit();
            $_SESSION['success_message'] = "Withdrawal request submitted successfully.";

        } catch (Exception $e) {
            // Something went wrong, roll back
            $conn->rollback();
            $_SESSION['error_message'] = $e->getMessage();
        }
    }

    header('Location: dashboard?page=my-wallet');
    exit;
}

$wallet = db_select("SELECT * FROM student_wallets WHERE student_id = ?", 'i', [$student_id])[0];
$transactions = db_select("SELECT * FROM wallet_transactions WHERE wallet_id = ? ORDER BY created_at DESC", 'i', [$wallet['id']]);
$withdrawals = db_select("SELECT * FROM withdrawal_requests WHERE student_id = ? ORDER BY requested_at DESC", 'i', [$student_id]);
?>
<h3>My Wallet</h3>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Available Balance</div>
            <div class="card-body"><h4 class="card-title">$<?= number_format($wallet['balance'], 2) ?></h4></div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Request a Withdrawal</div>
            <div class="card-body">
                <form method="POST" action="dashboard?page=my-wallet">
                    <div class="form-group">
                        <label for="amount">Amount ($)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required max="<?= $wallet['balance'] ?>">
                    </div>
                    <div class="form-group">
                        <label for="payment_details">Payment Details (e.g., PayPal Email)</label>
                        <textarea name="payment_details" class="form-control" rows="2" required></textarea>
                    </div>
                    <button type="submit" name="request_withdrawal" class="btn btn-primary" <?= $wallet['balance'] <= 0 ? 'disabled' : '' ?>>Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <h4>Withdrawal History</h4>
        <table class="table table-bordered">
            <thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($withdrawals as $w): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($w['requested_at'])) ?></td>
                    <td>$<?= number_format($w['amount'], 2) ?></td>
                    <td><span class="badge <?= get_status_badge_class($w['status']) ?>"><?= ucfirst($w['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <h4>Transaction History</h4>
        <table class="table table-bordered">
            <thead><tr><th>Date</th><th>Description</th><th>Amount</th></tr></thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr class="<?= $t['amount'] > 0 ? 'text-success' : 'text-danger' ?>">
                    <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                    <td><?= htmlspecialchars($t['description']) ?></td>
                    <td><?= ($t['amount'] > 0 ? '+' : '') . '$' . number_format(abs($t['amount']), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>