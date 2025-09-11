<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /lms/login');
    exit;
}

$filter = $_GET['filter'] ?? 'pending';
$allowed_filters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $allowed_filters)) $filter = 'pending';

$sql = "SELECT wr.*, u.name as instructor_name, u.email as instructor_email
        FROM instructor_withdrawal_requests wr 
        JOIN users u ON wr.instructor_id = u.id";
if ($filter !== 'all') {
    $sql .= " WHERE wr.status = ? ORDER BY wr.requested_at DESC";
    $requests = db_select($sql, 's', [$filter]);
} else {
    $sql .= " ORDER BY wr.requested_at DESC";
    $requests = db_select($sql);
}
?>

<style> 
/* Reusing styles from admin/withdrawals.php */
.nav-tabs { display: flex; border-bottom: 1px solid rgba(255, 255, 255, 0.2); margin-bottom: 1.5rem; }
.nav-item { margin-right: 0.5rem; }
.nav-link { display: block; padding: 0.75rem 1.25rem; color: #a0a0a0; border: 1px solid transparent; border-bottom: none; border-radius: 8px 8px 0 0; }
.nav-link.active { color: #fff; background-color: rgba(255, 255, 255, 0.07); border-color: rgba(255, 255, 255, 0.2); }
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
.form-control-sm { width: 100%; padding: 0.4rem 0.8rem; background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; color: #f0f0f0; font-size: 0.875rem; }
.btn-sm { width: 100%; padding: 0.4rem 1rem; border-radius: 6px; border: none; font-weight: 500; cursor: pointer; background-color: #b915ff; color: #fff; font-size: 0.875rem; }
.alert-success { padding: 1rem; border-radius: 8px; border: 1px solid rgba(40, 167, 69, 0.4); background-color: rgba(40, 167, 69, 0.15); color: #a3ffb8; margin-top: 1.5rem; }
pre { background: rgba(0, 0, 0, 0.3); padding: 0.5rem; border-radius: 6px; white-space: pre-wrap; color: #a0a0a0; }
</style>

<h3>Manage Instructor Payouts</h3>

<ul class="nav nav-tabs">
    <?php foreach ($allowed_filters as $f): ?>
    <li class="nav-item">
        <a class="nav-link <?= $f == $filter ? 'active' : '' ?>" href="dashboard?page=instructor-payouts&filter=<?= $f ?>"><?= ucfirst($f) ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>

<div class="card mt-3">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Instructor</th>
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
                    <td><?= htmlspecialchars($req['instructor_name']) ?><br><small><?= htmlspecialchars($req['instructor_email']) ?></small></td>
                    <td>$<?= number_format($req['amount'], 2) ?></td>
                    <td><?= date('M d, Y', strtotime($req['requested_at'])) ?></td>
                    <td><pre><?= htmlspecialchars($req['payment_details']) ?></pre></td>
                    <td><span class="badge badge-<?= $req['status'] == 'approved' ? 'success' : ($req['status'] == 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($req['status']) ?></span></td>
                    <td>
                        <?php if ($req['status'] == 'pending'): ?>
                        <form method="POST" action="dashboard?page=instructor-payouts">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <div class="form-group mb-2">
                                <select name="status" class="form-control form-control-sm">
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <textarea name="admin_notes" class="form-control form-control-sm" placeholder="Admin Notes..."></textarea>
                            </div>
                            <button type="submit" name="update_instructor_withdrawal" class="btn btn-primary btn-sm">Update</button>
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