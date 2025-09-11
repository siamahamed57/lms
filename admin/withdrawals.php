<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /lms/login');
    exit;
}

// This assumes your db.php file makes the mysqli connection object available as a global variable $conn.
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_withdrawal'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $admin_notes = filter_input(INPUT_POST, 'admin_notes', FILTER_SANITIZE_STRING);

    if ($request_id && in_array($status, ['approved', 'rejected'])) {
        $req = db_select("SELECT * FROM withdrawal_requests WHERE id = ? AND status = 'pending'", 'i', [$request_id]);
        if (!empty($req)) {
            // FATAL ERROR FIX: Replace undefined db_query() with prepared statements.
            $request = $req[0];
            db_execute("UPDATE withdrawal_requests SET status = ?, admin_notes = ?, processed_at = NOW() WHERE id = ?", 'ssi', [$status, $admin_notes, $request_id]);

            if ($status == 'rejected') {
                // Refund the amount to student's wallet
                db_execute("UPDATE student_wallets SET balance = balance + ? WHERE student_id = ?", 'di', [$request['amount'], $request['student_id']]);

                // Log transaction
                $wallet = db_select("SELECT id FROM student_wallets WHERE student_id = ?", 'i', [$request['student_id']])[0];
                $desc = "Withdrawal request #$request_id rejected. Amount refunded.";
                db_execute("INSERT INTO wallet_transactions (wallet_id, amount, type, description) VALUES (?, ?, 'withdrawal_rejected', ?)", 'ids', [$wallet['id'], $request['amount'], $desc]);
            } elseif ($status == 'approved') {
                // Update the original transaction log to reflect approval for the student's history.
                db_execute(
                    "UPDATE wallet_transactions SET description = ? WHERE related_id = ? AND type = 'withdrawal_request'",
                    'si', ["Withdrawal request #{$request_id} approved and processed.", $request_id]
                );
            }
            $_SESSION['success_message'] = "Withdrawal request updated.";
        }
    }
    header('Location: dashboard?page=withdrawals');
    exit;
}

$filter = $_GET['filter'] ?? 'pending';
$allowed_filters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $allowed_filters)) $filter = 'pending';

$sql = "SELECT wr.*, u.name as student_name, u.email as student_email
        FROM withdrawal_requests wr JOIN users u ON wr.student_id = u.id";
if ($filter !== 'all') {
    $sql .= " WHERE wr.status = ? ORDER BY wr.requested_at DESC";
    $requests = db_select($sql, 's', [$filter]);
} else {
    $sql .= " ORDER BY wr.requested_at DESC";
    $requests = db_select($sql);
}
?>

<style> 
/* ---- [ Import Modern Font ] ---- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

/* ---- [ CSS Variables for Easy Theming ] ---- */
:root {
    --primary-color: #b915ff;
    --primary-hover-color: #8b00cc;
    --background-start: #231134;
    --background-end: #0f172a;
    --glass-bg: rgba(255, 255, 255, 0.07);
    --glass-border: rgba(255, 255, 255, 0.2);
    --text-primary: #f0f0f0;
    --text-secondary: #a0a0a0;
    --input-bg: rgba(0, 0, 0, 0.3);

    /* Status Colors */
    --color-success: #28a745;
    --color-danger: #dc3545;
    --color-warning: #ffc107;
}



h3 {
    font-weight: 500;
    margin-bottom: 1.5rem;
}

/* ---- [ Navigation Tabs ] ---- */
.nav-tabs {
    display: flex;
   
}


/* ---- [ Glass Card Effect ] ---- */
.card {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
}

.card-body {
    padding: 0; /* Remove padding to allow table to fill it */
}

.mt-3 {
    margin-top: 1.5rem !important;
}

/* ---- [ Table Styling ] ---- */
.table-responsive-wrapper {
    overflow-x: auto;
    padding: 0.5rem; /* Add padding here instead of card-body */
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table th, .table td {
    padding: 1rem 1.25rem;
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid var(--glass-border);
}
.table thead th {
    color: var(--text-secondary);
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}
.table tbody tr:last-child td {
    border-bottom: none;
}
.table-hover tbody tr:hover {
    background-color: rgba(185, 21, 255, 0.1);
}
.table td small {
    display: block;
    color: var(--text-secondary);
    font-size: 0.85em;
    margin-top: 0.25rem;
}
pre {
    background: var(--input-bg);
    padding: 0.5rem;
    border-radius: 6px;
    font-family: monospace;
    white-space: pre-wrap; /* Allow long details to wrap */
    color: var(--text-secondary);
}

/* ---- [ Badges ] ---- */
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

/* ---- [ Inline Action Form ] ---- */
.form-group { margin-bottom: 0.5rem; }
.form-control-sm {
    width: 100%;
    padding: 0.4rem 0.8rem;
    background: var(--input-bg);
    border: 1px solid var(--glass-border);
    border-radius: 6px;
    color: var(--text-primary);
    font-family: 'Poppins', sans-serif;
    font-size: 0.875rem;
}
select.form-control-sm {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23a0a0a0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 16px 12px;
    padding-right: 2rem;
}
.btn-sm {
    width: 100%;
    padding: 0.4rem 1rem;
    border-radius: 6px;
    border: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: var(--primary-color);
    color: #fff;
    font-size: 0.875rem;
}
.btn-sm:hover {
    background-color: var(--primary-hover-color);
    transform: translateY(-1px);
}

/* ---- [ Alerts ] ---- */
.alert-success {
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid rgba(40, 167, 69, 0.4);
    background-color: rgba(40, 167, 69, 0.15);
    color: #a3ffb8;
}

/* ---- [ Responsive Design ] ---- */
@media (max-width: 768px) {
    body { padding: 1rem; }
}

</style>
<h3>Manage Withdrawal Requests</h3>

<ul class="nav nav-tabs">
    <?php foreach ($allowed_filters as $f): ?>
    <li class="nav-item">
        <a class="nav-link <?= $f == $filter ? 'active' : '' ?>" href="dashboard?page=withdrawals&filter=<?= $f ?>"><?= ucfirst($f) ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success mt-3"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>

<div class="card mt-3">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Amount</th>
                    <th>Requested</th>
                    <th>Payment Details</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['student_name']) ?><br><small><?= htmlspecialchars($req['student_email']) ?></small></td>
                    <td>$<?= number_format($req['amount'], 2) ?></td>
                    <td><?= date('M d, Y', strtotime($req['requested_at'])) ?></td>
                    <td><pre><?= htmlspecialchars($req['payment_details']) ?></pre></td>
                    <td><span class="badge badge-<?= $req['status'] == 'approved' ? 'success' : ($req['status'] == 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($req['status']) ?></span></td>
                    <td>
                        <?php if ($req['status'] == 'pending'): ?>
                        <form method="POST" action="dashboard?page=withdrawals">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <div class="form-group">
                                <select name="status" class="form-control form-control-sm">
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <textarea name="admin_notes" class="form-control form-control-sm" placeholder="Admin Notes..."></textarea>
                            </div>
                            <button type="submit" name="update_withdrawal" class="btn btn-primary btn-sm">Update</button>
                        </form>
                        <?php else: ?>
                            Processed: <?= date('M d, Y', strtotime($req['processed_at'])) ?><br>
                            <small>Notes: <?= htmlspecialchars($req['admin_notes'] ?? 'N/A') ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                <tr><td colspan="6" class="text-center">No requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>