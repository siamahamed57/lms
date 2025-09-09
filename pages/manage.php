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
<link rel="stylesheet" href="assets/css/course-manage.css">
<style>
    .form-section {
        background: var(--surface-light);
        padding: 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        border: 1px solid var(--border);
    }
    .form-section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1.5rem;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        align-items: end;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 0.75rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface);
    }
    .btn-create {
        width: 100%;
        padding: 0.75rem;
        border-radius: 8px;
        background: var(--gradient);
        color: white;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(185, 21, 255, 0.3);
    }
    .action-edit { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .edit-form-cell { padding: 24px; background-color: #f7f8fc; border-bottom: 2px solid var(--primary); }
    .edit-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .edit-form-group label { display: block; font-weight: 600; font-size: 13px; color: var(--text-light); margin-bottom: 8px; }
    .edit-form-group input, .edit-form-group select, .edit-form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; background: white; }
    .edit-form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
    .btn-update, .btn-cancel { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-update { background: var(--primary); color: white; }
    .btn-cancel { background: #e2e8f0; color: #475569; }
    .status-archived { background: rgba(239, 68, 68, 0.1); color: var(--error); }
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