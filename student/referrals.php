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
<style>
/* ---- [ Import Modern Font ] ---- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

/* ---- [ CSS Variables for Easy Theming ] ---- */
:root {
    --primary-color: #b915ff; /* A vibrant blue for interactive elements */
    --primary-hover-color: #8b00ccff;
    --background-start: #1d2b64; /* Dark blue/purple gradient */
    --background-end: #0f172a;
    --glass-bg: rgba(255, 255, 255, 0.07); /* The semi-transparent white for the glass effect */
    --glass-border: rgba(255, 255, 255, 0.2);
    --text-primary: #f0f0f0;
    --text-secondary: #a0a0a0;
    --success-bg: rgba(22, 163, 74, 0.2);
    --success-border: #16a34a;
}


h3, h4 {
    font-weight: 500;
    margin-bottom: 0.75rem;
}

p {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* ---- [ Glass Card Effect ] ---- */
.card {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px); /* For Safari */
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    overflow: hidden; /* Ensures content respects the border-radius */
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--glass-border);
}

.card-body {
    padding: 1.5rem;
}

/* Add margin between cards */
.card.mt-4 {
    margin-top: 2rem;
}

/* ---- [ Table Styling ] ---- */
.table-responsive-wrapper {
    overflow-x: auto; /* Makes table scrollable on small screens */
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

.table th, .table td {
    padding: 1rem;
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

/* ---- [ Form & Interactive Elements ] ---- */
.btn {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    border: none;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-primary {
    background-color: var(--primary-color);
    color: #fff;
}

.btn-primary:hover, .btn-primary:focus {
    background-color: var(--primary-hover-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 170, 255, 0.2);
}

.btn:disabled {
    background-color: #4caf50; /* Green for "Copied!" feedback */
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.form-control {
    width: 100%;
    padding: 0.6rem 1rem;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    color: var(--text-primary);
    font-family: 'Poppins', sans-serif;
}

/* ---- [ Specific Layouts & Alerts ] ---- */
.alert-success {
    background-color: var(--success-bg);
    border-left: 4px solid var(--success-border);
    color: var(--text-primary);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

/* Container for the copy input and button */
.copy-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ---- [ Responsive Design ] ---- */

/* For tablets and smaller devices */
@media (max-width: 768px) {
    body {
        padding: 1rem;
    }

    .card-header, .card-body {
        padding: 1rem;
    }

    .table th, .table td {
        padding: 0.75rem;
    }
    
    .copy-container {
        flex-direction: column;
        align-items: stretch;
    }

    .copy-container .btn {
        width: 100%;
        margin-top: 0.5rem;
    }
}

/* For very small mobile phones */
@media (max-width: 480px) {
    h3 {
        font-size: 1.5rem;
    }
    
    .btn {
        width: 100%;
    }
    
    /* Ensure the form doesn't cause overflow */
    form {
        display: flex;
    }
}

</style>
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
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="text" readonly class="form-control" id="referral-link-<?= $link['referral_code'] ?>" value="<?= htmlspecialchars($referral_url) ?>">
                                <button class="btn btn-primary copy-btn" data-target="#referral-link-<?= $link['referral_code'] ?>" style="white-space: nowrap;">Copy</button>
                            </div>
                        </td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButtons = document.querySelectorAll('.copy-btn');

    copyButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent any default button action
            const targetSelector = this.dataset.target;
            const inputField = document.querySelector(targetSelector);

            if (inputField && navigator.clipboard) {
                navigator.clipboard.writeText(inputField.value).then(() => {
                    const originalText = this.innerHTML;
                    this.innerHTML = 'Copied!';
                    this.disabled = true;

                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }
        });
    });
});
</script>