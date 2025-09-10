<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: /lms/login');
    exit;
}

$student_id = $_SESSION['user_id'];

$sql = "SELECT c.id, c.title, c.price, rs.reward_type, rs.reward_value 
        FROM courses c
        JOIN referral_settings rs ON c.id = rs.course_id
        WHERE rs.is_enabled = 1";
$referral_courses = db_select($sql);

$links_sql = "SELECT r.referral_code, r.expires_at, r.course_id, c.title as course_title
              FROM referrals r JOIN courses c ON r.course_id = c.id
              WHERE r.referrer_id = ? AND r.expires_at > NOW()";
$my_referrals = db_select($links_sql, 'i', [$student_id]);
?>
<h3>My Referrals</h3>
<p>Share links with friends. When they enroll, they get a discount and you earn a reward!</p>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h4>Generate New Referral Link</h4></div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>Course</th><th>Your Reward / Friend's Discount</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($referral_courses as $course): ?>
                    <tr>
                        <td><?= htmlspecialchars($course['title']) ?></td>
                        <td>
                            <?php
                                if ($course['reward_type'] == 'fixed') {
                                    echo '$' . number_format($course['reward_value'], 2);
                                } else {
                                    echo $course['reward_value'] . '%';
                                }
                            ?>
                        </td>
                        <td>
                            <form method="POST" action="dashboard?page=my-referrals">
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                <button type="submit" name="generate_referral" class="btn btn-primary">Generate Link</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($referral_courses)): ?>
                    <tr><td colspan="3">There are currently no courses with active referral programs. Please check back later!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h4>Your Active Referral Links</h4></div>
    <div class="card-body">
        <table class="table table-striped">
            <thead><tr><th>Course</th><th>Referral Link</th><th>Expires On</th></tr></thead>
            <tbody>
                <?php foreach ($my_referrals as $link): ?>
                    <?php
                        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                        $referral_url = $base_url . '/lms/api/payments/pay.php?course_id=' . $link['course_id'] . '&ref=' . $link['referral_code'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($link['course_title']) ?></td>
                        <td><input type="text" readonly class="form-control" value="<?= htmlspecialchars($referral_url) ?>"></td>
                        <td><?= date('M d, Y', strtotime($link['expires_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($my_referrals)): ?>
                    <tr><td colspan="3">You have no active referral links.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>