<?php
// admin/instructor_settings.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /lms/login');
    exit;
}

// Fetch all instructors
$instructors_sql = "SELECT id, name, email, commission_rate FROM users WHERE role = 'instructor' ORDER BY name";
$instructors = db_select($instructors_sql);
?>

<h3>Instructor Commission Settings</h3>
<p>Set the commission rate for each instructor. This is the percentage they will earn from each course sale.</p>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<style>
/* Using similar styles from referral_settings.php for consistency */
.card { background: rgba(255, 255, 255, 0.07); backdrop-filter: blur(15px); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); overflow: hidden; }
.card-body { padding: 1rem 1.5rem; }
.row { display: flex; flex-wrap: wrap; align-items: center; }
form.border-bottom { border-bottom: 1px solid rgba(255, 255, 255, 0.2); padding: 1.25rem 0; }
.card-body > form:last-of-type { border-bottom: none; padding-bottom: 0.5rem; }
.text-muted { color: #a0a0a0 !important; }
.font-weight-bold { font-weight: 600; }
.pb-2 { padding-bottom: 0.75rem; }
.mb-2 { margin-bottom: 0.75rem; }
.form-control { width: 100%; padding: 0.75rem 1rem; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: #f0f0f0; transition: all 0.3s ease; }
.form-control:focus { outline: none; border-color: #b915ff; box-shadow: 0 0 0 3px rgba(185, 21, 255, 0.2); }
.form-control-sm { padding: 0.4rem 0.8rem; font-size: 0.875rem; border-radius: 6px; }
.btn { padding: 0.7rem 1.5rem; border-radius: 8px; border: none; font-weight: 500; cursor: pointer; transition: all 0.3s ease; white-space: nowrap; }
.btn-primary { background-color: #b915ff; color: #fff; }
.btn-primary:hover { background-color: #8b00cc; }
.btn-sm { padding: 0.4rem 1rem; font-size: 0.875rem; border-radius: 6px; }
.alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid transparent; }
.alert-success { background-color: rgba(40, 167, 69, 0.15); border-color: rgba(40, 167, 69, 0.4); color: #a3ffb8; }
.alert-danger { background-color: rgba(220, 53, 69, 0.15); border-color: rgba(220, 53, 69, 0.4); color: #ffacb3; }
.d-none { display: none !important; }
@media (min-width: 768px) {
    .d-md-flex { display: flex !important; }
    .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 0 8px; }
    .col-md-2 { flex: 0 0 16.667%; max-width: 16.667%; padding: 0 8px; }
    .text-md-right { text-align: right; }
    .mb-md-0 { margin-bottom: 0 !important; }
}
</style>

<div class="card">
    <div class="card-body">
        <div class="d-none d-md-flex row text-muted font-weight-bold border-bottom pb-2 mb-2">
            <div class="col-md-4">Instructor Name</div>
            <div class="col-md-4">Email</div>
            <div class="col-md-2">Commission Rate (%)</div>
            <div class="col-md-2 text-md-right">Action</div>
        </div>

        <?php if (empty($instructors)): ?>
            <p class="text-center">No instructors found.</p>
        <?php else: ?>
            <?php foreach ($instructors as $instructor): ?>
                <form method="POST" action="dashboard?page=instructor-settings" class="border-bottom py-3">
                    <input type="hidden" name="instructor_id" value="<?= $instructor['id'] ?>">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <strong><?= htmlspecialchars($instructor['name']) ?></strong>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <small><?= htmlspecialchars($instructor['email']) ?></small>
                        </div>
                        <div class="col-md-2 mb-2 mb-md-0">
                            <input type="number" step="0.01" name="commission_rate" class="form-control form-control-sm" value="<?= htmlspecialchars($instructor['commission_rate'] ?? '70.00') ?>" required>
                        </div>
                        <div class="col-md-2 text-md-right">
                            <button type="submit" name="save_commission_settings" class="btn btn-primary btn-sm">Save</button>
                        </div>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>