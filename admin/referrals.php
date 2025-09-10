<?php
// admin/referrals.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /lms/login');
    exit;
}

// Fetch referral usage data
$sql = "SELECT 
            ru.used_at,
            ru.reward_earned,
            c.title AS course_title,
            referrer.name AS referrer_name,
            referrer.email AS referrer_email,
            invitee.name AS invitee_name,
            invitee.email AS invitee_email,
            r.referral_code
        FROM referral_usages ru
        JOIN referrals r ON ru.referral_id = r.id
        JOIN courses c ON r.course_id = c.id
        JOIN users referrer ON r.referrer_id = referrer.id
        JOIN users invitee ON ru.invitee_id = invitee.id
        ORDER BY ru.used_at DESC";

$referral_usages = db_select($sql);

// Fetch summary stats
$stats_sql = "SELECT 
                COUNT(DISTINCT r.referrer_id) as total_referrers,
                COUNT(ru.id) as total_referrals_used,
                SUM(ru.reward_earned) as total_rewards_paid
              FROM referral_usages ru
              JOIN referrals r ON ru.referral_id = r.id";
$stats = db_select($stats_sql)[0];

?>
<h3>Referral Program Report</h3>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center"><div class="card-body">
            <h5 class="card-title">Total Referrals Used</h5>
            <p class="card-text display-4"><?= $stats['total_referrals_used'] ?? 0 ?></p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card text-center"><div class="card-body">
            <h5 class="card-title">Total Rewards Paid Out</h5>
            <p class="card-text display-4">$<?= number_format($stats['total_rewards_paid'] ?? 0, 2) ?></p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card text-center"><div class="card-body">
            <h5 class="card-title">Active Referrers</h5>
            <p class="card-text display-4"><?= $stats['total_referrers'] ?? 0 ?></p>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4>Detailed Referral History</h4></div>
    <div class="card-body">
        <table class="table table-striped table-bordered" id="referrals-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Referrer</th>
                    <th>Invitee (New Student)</th>
                    <th>Course</th>
                    <th>Reward Earned</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($referral_usages as $usage): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($usage['used_at'])) ?></td>
                    <td><?= htmlspecialchars($usage['referrer_name']) ?><br><small><?= htmlspecialchars($usage['referrer_email']) ?></small></td>
                    <td><?= htmlspecialchars($usage['invitee_name']) ?><br><small><?= htmlspecialchars($usage['invitee_email']) ?></small></td>
                    <td><?= htmlspecialchars($usage['course_title']) ?></td>
                    <td>$<?= number_format($usage['reward_earned'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>/* Using a library like DataTables.js on #referrals-table is recommended */</script>