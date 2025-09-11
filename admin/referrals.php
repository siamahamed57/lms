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

<style> 
    /* ---- [ Import Modern Font ] ---- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap');

/* ---- [ CSS Variables for Easy Theming ] ---- */
:root {
    --primary-color: #b915ff; /* UPDATED to vibrant purple */
    --primary-hover-color: #8b00cc; /* UPDATED to darker purple */
    --background-start: #231134; /* Adjusted to complement purple theme */
    --background-end: #0f172a;
    --glass-bg: rgba(255, 255, 255, 0.07);
    --glass-border: rgba(255, 255, 255, 0.2);
    --text-primary: #f0f0f0;
    --text-secondary: #a0a0a0;
    --input-bg: rgba(0, 0, 0, 0.2);
}


h3, h4, h5 {
    font-weight: 500;
    margin-bottom: 0.75rem;
}

/* ---- [ Layout Grid ] ---- */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}

.col-md-4 {
    width: 100%;
    padding: 0 15px;
    margin-bottom: 1.5rem;
}

.mb-4 {
    margin-bottom: 2rem !important;
}

@media (min-width: 768px) {
    .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; }
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
    height: 100%;
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--glass-border);
    background: rgba(255, 255, 255, 0.05); /* Subtle header highlight */
}

.card-body {
    padding: 1.5rem;
}

/* ---- [ Summary Stat Cards ] ---- */
.text-center {
    text-align: center;
}

.card .card-title {
    color: var(--text-secondary);
    text-transform: uppercase;
    font-size: 0.9rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.card .display-4 {
    font-size: 3.5rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

/* ---- [ Table Styling ] ---- */
.table-responsive-wrapper {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, .table td {
    padding: 1rem 1.25rem;
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

/* Bordered Table Style */
.table-bordered {
    border: 1px solid var(--glass-border);
    border-radius: 8px; /* Soften the table corners */
    overflow: hidden; /* Ensure border-radius is respected */
}

.table-bordered th, .table-bordered td {
    border: 1px solid var(--glass-border);
}
.table-bordered thead th {
    border-bottom-width: 2px;
}

/* Striped Table Style */
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(185, 21, 255, 0.05); /* Faint purple stripe */
}

/* Style for secondary text in table cells */
.table td small {
    display: block;
    margin-top: 0.25rem;
    color: var(--text-secondary);
    font-size: 0.85em;
}


/* ---- [ Responsive Design ] ---- */
@media (max-width: 992px) {
    .card .display-4 {
        font-size: 2.75rem;
    }
}

@media (max-width: 768px) {
    body {
        padding: 1rem;
    }
    .card .display-4 {
        font-size: 2.5rem;
    }
    .table th, .table td {
        padding: 0.75rem;
    }
}
</style>
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