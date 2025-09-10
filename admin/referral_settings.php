<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /lms/login');
    exit;
}

// This assumes your db.php file makes the mysqli connection object available as a global variable $conn.
global $conn;

// Fetch all courses and their current referral settings
$courses_sql = "SELECT c.id, c.title, rs.is_enabled, rs.reward_type, rs.reward_value 
                FROM courses c 
                LEFT JOIN referral_settings rs ON c.id = rs.course_id 
                ORDER BY c.title";
$courses = db_select($courses_sql);
?>

<h3>Course Referral Settings</h3>
<p>Enable and configure referral rewards for each course individually.</p>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <!-- Header for the list - visible on medium screens and up -->
        <div class="d-none d-md-flex row text-muted font-weight-bold border-bottom pb-2 mb-2">
            <div class="col-md-4">Course Title</div>
            <div class="col-md-2">Status</div>
            <div class="col-md-2">Reward Type</div>
            <div class="col-md-2">Reward Value</div>
            <div class="col-md-2 text-md-right">Action</div>
        </div>

        <?php if (empty($courses)): ?>
            <p class="text-center">No courses found.</p>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <form method="POST" action="dashboard?page=referral-settings" class="border-bottom py-3" id="form-<?= $course['id'] ?>">
                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <strong><?= htmlspecialchars($course['title']) ?></strong>
                        </div>
                        <div class="col-md-2 mb-2 mb-md-0">
                            <div class="custom-control custom-switch" title="Click to toggle status">
                                <input type="checkbox" class="custom-control-input" id="isEnabled-<?= $course['id'] ?>" name="is_enabled" value="1" <?= !empty($course['is_enabled']) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="isEnabled-<?= $course['id'] ?>"><?= !empty($course['is_enabled']) ? 'Enabled' : 'Disabled' ?></label>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2 mb-md-0">
                            <select name="reward_type" class="form-control form-control-sm">
                                <option value="fixed" <?= ($course['reward_type'] ?? 'fixed') == 'fixed' ? 'selected' : '' ?>>Fixed ($)</option>
                                <option value="percentage" <?= ($course['reward_type'] ?? '') == 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2 mb-md-0">
                            <input type="number" step="0.01" name="reward_value" class="form-control form-control-sm" value="<?= htmlspecialchars($course['reward_value'] ?? '10.00') ?>" required>
                        </div>
                        <div class="col-md-2 text-md-right">
                            <button type="submit" name="save_referral_settings" class="btn btn-primary btn-sm">Save</button>
                        </div>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Provides instant visual feedback when an admin toggles a referral status switch.
    const switches = document.querySelectorAll('.custom-control-input');
    switches.forEach(function(switchEl) {
        switchEl.addEventListener('change', function() {
            const label = this.nextElementSibling;
            label.textContent = this.checked ? 'Enabled' : 'Disabled';
        });
    });
});
</script>