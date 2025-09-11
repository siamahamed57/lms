<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authorization check - only admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo "<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Access Denied!</h2><p class='text-gray-600'>Only Administrators can access this page.</p></div>";
    exit;
}

// --- Message Handling ---
$success_message = $_SESSION['coupon_management_success'] ?? '';
unset($_SESSION['coupon_management_success']);
$error_message = $_SESSION['coupon_management_error'] ?? '';
unset($_SESSION['coupon_management_error']);

// --- Fetch all coupons ---
$coupons = db_select("SELECT * FROM coupons ORDER BY created_at DESC");

// Function to get status badge class
function getCouponStatusBadge($status, $expires_at) {
    if ($status === 'inactive') {
        return 'status-draft'; // gray
    }
    if ($expires_at && strtotime($expires_at) < time()) {
        return 'status-archived'; // red
    }
    if ($status === 'active') {
        return 'status-published'; // green
    }
    return 'status-draft';
}

function getCouponStatusText($status, $expires_at) {
    if ($status === 'inactive') {
        return 'Inactive';
    }
    if ($expires_at && strtotime($expires_at) < time()) {
        return 'Expired';
    }
    return 'Active';
}

?>
<link rel="stylesheet" href="assets/css/course-manage.css"> <!-- Base styles are kept -->
<style> 
/* ---- [ Import Modern Font & Icons ] ---- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
/* Font Awesome for icons (used in action buttons) */
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');


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

    /* Status & Alert Colors */
    --color-success: #28a745;
    --color-danger: #dc3545;
    --color-warning: #ffc107; /* Inactive/Draft */
    --color-archived: #6c757d; /* Gray */
}



.container {

    margin: 0 auto;
    padding: 2rem;
}

/* ---- [ Header & Titles ] ---- */
.header {
    margin-bottom: 2rem;
}
.title {
    font-size: 2.25rem;
    font-weight: 600;
}

/* ---- [ Form Section Styling ] ---- */
.form-section, .table-container {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    padding: 2rem;
    margin-bottom: 2rem;
}
.form-section-title {
    font-size: 1.5rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    align-items: flex-end;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
}
.form-group input, .form-group select {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--input-bg);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    color: var(--text-primary);
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
}
.form-group input:focus, .form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(185, 21, 255, 0.2);
}
.form-group select {
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23a0a0a0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
}

/* ---- [ Button Styles ] ---- */
.btn-create, .btn-update {
    width: 100%;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 8px;
    background-color: var(--primary-color);
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-create:hover, .btn-update:hover {
    background-color: var(--primary-hover-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(185, 21, 255, 0.2);
}

/* ---- [ Table Styling ] ---- */
.table-container { padding: 0; }
.table { width: 100%; border-collapse: collapse; }
.table-header, .table-cell {
    padding: 1rem 1.5rem;
    text-align: left;
}
.table-header {
    background: rgba(255, 255, 255, 0.05);
}
.table-header th {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.table-body {
    display: block;
    max-height: 60vh; /* Adjust as needed */
    overflow-y: auto;
}
.table-row {
    border-bottom: 1px solid var(--glass-border);
}
.table-row:last-child { border-bottom: none; }
.table-row:hover { background-color: rgba(185, 21, 255, 0.08); }
.empty-state { text-align: center; color: var(--text-secondary); padding: 3rem; }
.font-bold { font-weight: 600; }
.text-primary { color: var(--primary-color); }

/* ---- [ Status Badges ] ---- */
.status-badge {
    padding: 0.3em 0.8em;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
}
.status-published { background-color: rgba(40, 167, 69, 0.3); color: #7ee29a; } /* Active */
.status-draft { background-color: rgba(108, 117, 125, 0.3); color: #ced4da; } /* Inactive */
.status-archived { background-color: rgba(220, 53, 69, 0.3); color: #ffacb3; } /* Expired */

/* ---- [ Actions Buttons ] ---- */
.actions { display: flex; gap: 0.5rem; }
.action-btn {
    background: transparent;
    border: 1px solid var(--glass-border);
    color: var(--text-secondary);
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.action-btn:hover { background: var(--input-bg); color: var(--text-primary); }
.action-edit:hover { border-color: var(--primary-color); color: var(--primary-color); }
.action-delete:hover { border-color: var(--color-danger); color: var(--color-danger); }

/* ---- [ Inline Edit Form ] ---- */
.edit-form-row.active { display: table-row; }
.edit-form-cell { padding: 1.5rem !important; background: rgba(0, 0, 0, 0.2); }
.edit-form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; }
.btn-cancel {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    background: transparent;
    border: 1px solid var(--glass-border);
    color: var(--text-secondary);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-cancel:hover { background: var(--input-bg); color: var(--text-primary); }

/* ---- [ Alerts ] ---- */
.alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid transparent; }
.alert-success { background-color: rgba(40, 167, 69, 0.15); border-color: rgba(40, 167, 69, 0.4); color: #a3ffb8; }
.alert-error { background-color: rgba(220, 53, 69, 0.15); border-color: rgba(220, 53, 69, 0.4); color: #ffacb3; }

/* ---- [ Responsive Design ] ---- */
@media (max-width: 768px) {
    .container { padding: 1rem; }
    .form-grid { grid-template-columns: 1fr; }
    .table-header { display: none; } /* Hide header on mobile */
    .table-body, .table-row, .table-cell { display: block; }
    .table-row { border: 1px solid var(--glass-border); border-radius: 8px; margin-bottom: 1rem; padding: 1rem; }
    .table-cell { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--glass-border); }
    .table-cell:last-child { border-bottom: none; }
    .table-cell::before {
        content: attr(data-label);
        font-weight: 500;
        color: var(--text-secondary);
        padding-right: 1rem;
    }
    .actions { justify-content: flex-end; }
}

</style>

<div class="container">
    <div class="header">
        <h1 class="title">Coupon Management</h1>
    </div>

    <?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-error"><?= $error_message ?></div><?php endif; ?>

    <!-- Create Coupon Form -->
    <div class="form-section">
        <h3 class="form-section-title">Create New Coupon</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create_coupon">
            <div class="form-grid">
                <div class="form-group">
                    <label for="code">Coupon Code</label>
                    <input type="text" name="code" id="code" placeholder="e.g., SPRING25" required style="text-transform:uppercase">
                </div>
                <div class="form-group">
                    <label for="type">Discount Type</label>
                    <select name="type" id="type" required>
                        <option value="fixed">Fixed Amount (৳)</option>
                        <option value="percentage">Percentage (%)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="value">Value</label>
                    <input type="number" name="value" id="value" step="0.01" min="0.01" required placeholder="e.g., 500 or 25">
                </div>
                <div class="form-group">
                    <label for="expires_at">Expires At (Optional)</label>
                    <input type="datetime-local" name="expires_at" id="expires_at">
                </div>
                <div class="form-group">
                    <label for="usage_limit">Usage Limit (Optional)</label>
                    <input type="number" name="usage_limit" id="usage_limit" min="1" placeholder="e.g., 100">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-create">Create Coupon</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Coupons Table -->
    <div class="table-container">
        <table class="table">
            <thead class="table-header">
                <tr><th>Code</th><th>Type</th><th>Value</th><th>Usage</th><th>Expires At</th><th>Status</th><th>Actions</th></tr>
            </thead>
        </table>
        <div class="table-body">
            <table class="table">
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr><td colspan="7" class="empty-state">No coupons found. Create one above to get started.</td></tr>
                    <?php else: foreach ($coupons as $index => $coupon): ?>
                        <tr class="table-row" data-coupon-id="<?= $coupon['id'] ?>">
                            <td class="table-cell font-bold text-primary"><?= htmlspecialchars($coupon['code']) ?></td>
                            <td class="table-cell"><?= htmlspecialchars(ucfirst($coupon['type'])) ?></td>
                            <td class="table-cell font-semibold"><?= $coupon['type'] === 'percentage' ? htmlspecialchars($coupon['value']) . '%' : '৳' . htmlspecialchars(number_format($coupon['value'], 2)) ?></td>
                            <td class="table-cell"><?= htmlspecialchars($coupon['times_used']) ?> / <?= $coupon['usage_limit'] ?? '∞' ?></td>
                            <td class="table-cell"><?= $coupon['expires_at'] ? date('M j, Y, g:i a', strtotime($coupon['expires_at'])) : 'Never' ?></td>
                            <td class="table-cell"><span class="status-badge <?= getCouponStatusBadge($coupon['status'], $coupon['expires_at']) ?>"><?= getCouponStatusText($coupon['status'], $coupon['expires_at']) ?></span></td>
                            <td class="table-cell">
                                <div class="actions">
                                    <button class="action-btn action-edit" onclick="toggleEditForm(<?= $coupon['id'] ?>)"><i class="fas fa-edit"></i> <span class="edit-btn-text">Edit</span></button>
                                    <button class="action-btn action-delete" onclick="confirmDelete(<?= $coupon['id'] ?>, '<?= htmlspecialchars(addslashes($coupon['code']), ENT_QUOTES) ?>')"><i class="fas fa-trash-alt"></i> Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr class="edit-form-row" id="edit-form-<?= $coupon['id'] ?>" style="display: none;">
                            <td colspan="7" class="edit-form-cell">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_coupon">
                                    <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                    <h4 class="form-section-title" style="font-size: 1.2rem; margin-top:0;">Editing Coupon: <?= htmlspecialchars($coupon['code']) ?></h4>
                                    <div class="form-grid" style="grid-template-columns: repeat(3, 1fr);">
                                        <div class="form-group"><label>Coupon Code</label><input type="text" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required style="text-transform:uppercase"></div>
                                        <div class="form-group"><label>Discount Type</label><select name="type" required><option value="fixed" <?= $coupon['type'] == 'fixed' ? 'selected' : '' ?>>Fixed Amount (৳)</option><option value="percentage" <?= $coupon['type'] == 'percentage' ? 'selected' : '' ?>>Percentage (%)</option></select></div>
                                        <div class="form-group"><label>Value</label><input type="number" name="value" value="<?= htmlspecialchars($coupon['value']) ?>" step="0.01" min="0.01" required></div>
                                        <div class="form-group"><label>Expires At</label><input type="datetime-local" name="expires_at" value="<?= !empty($coupon['expires_at']) ? date('Y-m-d\TH:i', strtotime($coupon['expires_at'])) : '' ?>"></div>
                                        <div class="form-group"><label>Usage Limit</label><input type="number" name="usage_limit" value="<?= htmlspecialchars($coupon['usage_limit'] ?? '') ?>" min="1"></div>
                                        <div class="form-group"><label>Status</label><select name="status" required><option value="active" <?= $coupon['status'] == 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $coupon['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
                                    </div>
                                    <div class="edit-form-actions">
                                        <button type="button" class="btn-cancel" onclick="toggleEditForm(<?= $coupon['id'] ?>)">Cancel</button>
                                        <button type="submit" class="btn-update">Update Coupon</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Hidden form for deletion -->
<form id="delete-form" method="POST" action="" style="display: none;"><input type="hidden" name="action" value="delete_coupon"><input type="hidden" name="coupon_id_to_delete" id="coupon_id_to_delete"></form>
<script>
    function confirmDelete(couponId, couponCode) {
        if (confirm(`Are you sure you want to delete the coupon "${couponCode}"? This action cannot be undone.`)) {
            document.getElementById('coupon_id_to_delete').value = couponId;
            document.getElementById('delete-form').submit();
        }
    }

    let activeEditFormId = null;

    function toggleEditForm(couponId) {
        const formRow = document.getElementById(`edit-form-${couponId}`);
        const tableRow = document.querySelector(`.table-row[data-coupon-id='${couponId}']`);
        const editButtonText = tableRow.querySelector('.edit-btn-text');

        if (activeEditFormId && activeEditFormId !== couponId) {
            document.getElementById(`edit-form-${activeEditFormId}`).style.display = 'none';
            const lastTableRow = document.querySelector(`.table-row[data-coupon-id='${activeEditFormId}']`);
            if(lastTableRow) lastTableRow.querySelector('.edit-btn-text').textContent = 'Edit';
        }

        const isVisible = formRow.style.display === 'table-row';
        formRow.style.display = isVisible ? 'none' : 'table-row';
        if(editButtonText) editButtonText.textContent = isVisible ? 'Edit' : 'Cancel';
        
        activeEditFormId = isVisible ? null : couponId;
    }
</script>