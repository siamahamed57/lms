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