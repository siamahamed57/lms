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
<style> 
/* ---- [ Import Modern Font ] ---- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

/* ---- [ CSS Variables for Easy Theming ] ---- */
:root {
 --primary-color: #b915ff; /* A vibrant blue for interactive elements */
    --primary-hover-color: #8b00ccff;
    --background-start: #1d2b64;
    --background-end: #0f172a;
    --glass-bg: rgba(255, 255, 255, 0.07);
    --glass-border: rgba(255, 255, 255, 0.2);
    --text-primary: #f0f0f0;
    --text-secondary: #a0a0a0;
    
    /* Status Colors */
    --color-success: #28a745;
    --color-danger: #dc3545;
    --color-warning: #ffc107;
    --color-info: #17a2b8;
}


h3, h4 {
    font-weight: 500;
    margin-bottom: 0.75rem;
    margin-top: 3rem;
}

p {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* ---- [ Layout Grid ] ---- */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px; /* Gutter simulation */
}

.col-md-4, .col-md-6, .col-md-8 {
    width: 100%;
    padding: 0 15px;
    margin-bottom: 1.5rem;
}

/* Desktop layout */
@media (min-width: 768px) {
    .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; }
    .col-md-6 { flex: 0 0 50%; max-width: 50%; }
    .col-md-8 { flex: 0 0 66.667%; max-width: 66.667%; }
}

.mt-4 {
    margin-top: 2rem;
}


/* ---- [ Glass Card Effect ] ---- */
.card {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    overflow: hidden;
    height: 100%; /* Make cards in a row the same height */
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--glass-border);
}

.card-body {
    padding: 1.5rem;
}

/* ---- [ Special Balance Card ] ---- */
.card.bg-success {
    background: rgba(40, 167, 69, 0.15); /* Tinted glass */
    border-color: rgba(40, 167, 69, 0.4);
}
.card.bg-success .card-header {
    border-bottom: 1px solid rgba(40, 167, 69, 0.4);
}
.card.bg-success .card-title {
    color: #fff;
    font-size: 2.5rem;
    font-weight: 600;
}

/* ---- [ Form Styling ] ---- */
.form-group {
    margin-bottom: 1.25rem;
}
label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
}
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    color: var(--text-primary);
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
}
.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 170, 255, 0.2);
}
textarea.form-control {
    resize: vertical;
}
.btn {
    padding: 0.7rem 1.5rem;
    border-radius: 8px;
    border: none;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-primary {
    background-color: var(--primary-color);
    color: #fff;
}
.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-hover-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 170, 255, 0.2);
}
.btn:disabled {
    background-color: rgba(255, 255, 255, 0.1);
    color: var(--text-secondary);
    cursor: not-allowed;
}

/* ---- [ Table Styling ] ---- */
.table-responsive-wrapper {
    overflow-x: auto;
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table-bordered th, .table-bordered td {
    border: 1px solid var(--glass-border);
}
.table th, .table td {
    padding: 1rem;
    text-align: left;
    vertical-align: middle;
}
.table thead th {
    color: var(--text-secondary);
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}
.text-success { color: var(--color-success) !important; font-weight: 500; }
.text-danger { color: var(--color-danger) !important; font-weight: 500; }

/* ---- [ Badge Styling ] ---- */
.badge {
    display: inline-block;
    padding: 0.4em 0.7em;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 1rem;
    color: #fff;
}
.badge-success { background-color: rgba(40, 167, 69, 0.5); }
.badge-danger { background-color: rgba(220, 53, 69, 0.5); }
.badge-warning { background-color: rgba(255, 193, 7, 0.5); color: #111; }
.badge-info { background-color: rgba(23, 162, 184, 0.5); }


/* ---- [ Alert Styling ] ---- */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid transparent;
}
.alert-success {
    background-color: rgba(40, 167, 69, 0.15);
    border-color: rgba(40, 167, 69, 0.4);
    color: #a3ffb8;
}
.alert-danger {
    background-color: rgba(220, 53, 69, 0.15);
    border-color: rgba(220, 53, 69, 0.4);
    color: #ffacb3;
}


</style>
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