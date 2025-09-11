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
    --input-bg: rgba(0, 0, 0, 0.2);

    /* Status Colors */
    --color-success: #28a745;
    --color-danger: #dc3545;
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
    -webkit-backdrop-filter: blur(15px);
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.card-body {
    padding: 1rem 1.5rem;
}

/* ---- [ Settings List Layout ] ---- */
.row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}

form.border-bottom {
    border-bottom: 1px solid var(--glass-border);
    padding: 1.25rem 0;
}
/* Remove border from the last form/item in the list */
.card-body > form:last-of-type {
    border-bottom: none;
    padding-bottom: 0.5rem;
}

/* Styling for the desktop header row */
.text-muted { color: var(--text-secondary) !important; }
.font-weight-bold { font-weight: 600; }
.border-bottom { border-bottom: 1px solid var(--glass-border); }
.pb-2 { padding-bottom: 0.75rem; }
.mb-2 { margin-bottom: 0.75rem; }

/* ---- [ Form Controls ] ---- */
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--input-bg);
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

select.form-control {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23a0a0a0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.5rem; /* Make space for the arrow */
}

/* Sizing modifiers */
.form-control-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
    border-radius: 6px;
}
select.form-control-sm {
    padding-right: 2rem;
}

/* ---- [ Custom Toggle Switch ] ---- */
.custom-switch {
    position: relative;
    display: inline-block;
    cursor: pointer;
}
.custom-control-input {
    opacity: 0;
    width: 0;
    height: 0;
}
.custom-control-label {
    display: block;
    position: relative;
    padding-left: 55px; /* Space for the switch */
    line-height: 24px; /* Vertical alignment */
    font-weight: 500;
}
.custom-control-label::before { /* The track of the switch */
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    width: 44px;
    height: 24px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    transition: background-color 0.3s ease;
}
.custom-control-label::after { /* The handle of the switch */
    content: "";
    position: absolute;
    left: 3px;
    top: 3px;
    width: 18px;
    height: 18px;
    background-color: var(--text-secondary);
    border-radius: 50%;
    transition: transform 0.3s ease, background-color 0.3s ease;
}
.custom-control-input:checked + .custom-control-label::before {
    background-color: var(--color-success);
}
.custom-control-input:checked + .custom-control-label::after {
    transform: translateX(20px);
    background-color: #fff;
}


/* ---- [ Button Styling ] ---- */
.btn {
    padding: 0.7rem 1.5rem;
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
.btn-sm {
    padding: 0.4rem 1rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

/* ---- [ Alerts ] ---- */
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

/* ---- [ Responsive Design ] ---- */
/* Hide desktop headers on mobile */
.d-none { display: none !important; }

@media (min-width: 768px) {
    .d-md-flex { display: flex !important; }
    .col-md-2 { flex: 0 0 16.667%; max-width: 16.667%; padding: 0 8px; }
    .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 0 8px; }
    .text-md-right { text-align: right; }
    .mb-md-0 { margin-bottom: 0 !important; }
}

/* Mobile view enhancements */
@media (max-width: 767.98px) {
    .card-body { padding: 0.5rem 1rem; }
    .row > [class^="col-"] {
        width: 100%;
        padding: 0;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .row > [class^="col-"]:last-child {
        margin-bottom: 0;
    }
    .text-md-right { text-align: left; }
    /* Add labels for context on mobile */
    .row > [class^="col-"]::before {
        content: attr(data-label);
        font-weight: 500;
        color: var(--text-secondary);
        padding-right: 1rem;
    }
    .col-md-4 strong { /* Course title doesn't need a label */
        width: 100%;
        font-size: 1.1rem;
        text-align: center;
        padding-bottom: 0.5rem;
    }
    .col-md-4::before {
        display: none;
    }
}

</style>
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