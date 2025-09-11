<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /lms/login');
    exit;
}

$filter_role = $_GET['role'] ?? 'all';
$allowed_roles = ['all', 'student', 'instructor', 'admin'];
if (!in_array($filter_role, $allowed_roles)) {
    $filter_role = 'all';
}

$sql = "SELECT id, name, email, role, created_at, avatar FROM users";
$params = [];
$types = '';

if ($filter_role !== 'all') {
    $sql .= " WHERE role = ?";
    $params[] = $filter_role;
    $types .= 's';
}
$sql .= " ORDER BY created_at DESC";

$users = db_select($sql, $types, $params);
?>

<style>
/* Reusing styles from other admin pages for consistency */
.nav-tabs { display: flex; border-bottom: 1px solid rgba(255, 255, 255, 0.2); margin-bottom: 1.5rem; }
.nav-item { margin-right: 0.5rem; }
.nav-link { display: block; padding: 0.75rem 1.25rem; color: #a0a0a0; border: 1px solid transparent; border-bottom: none; border-radius: 8px 8px 0 0; text-decoration: none; }
.nav-link.active { color: #fff; background-color: rgba(255, 255, 255, 0.07); border-color: rgba(255, 255, 255, 0.2); }
.card { background: rgba(255, 255, 255, 0.07); backdrop-filter: blur(15px); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.2); }
.card-body { padding: 0; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 1rem 1.25rem; text-align: left; vertical-align: middle; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
.table thead th { color: #a0a0a0; font-weight: 500; text-transform: uppercase; font-size: 0.8rem; }
.table tbody tr:last-child td { border-bottom: none; }
.user-info { display: flex; align-items: center; gap: 1rem; }
.user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #333; display: flex; align-items: center; justify-content: center; font-weight: bold; overflow: hidden; }
.user-avatar img { width: 100%; height: 100%; object-fit: cover; }
.form-control-sm { width: 100%; padding: 0.4rem 0.8rem; background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; color: #f0f0f0; font-size: 0.875rem; }
.btn-sm { padding: 0.4rem 1rem; border-radius: 6px; border: none; font-weight: 500; cursor: pointer; background-color: #b915ff; color: #fff; font-size: 0.875rem; }
.alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
.alert-success { background-color: rgba(40, 167, 69, 0.15); color: #a3ffb8; }
.alert-danger { background-color: rgba(220, 53, 69, 0.15); color: #ffacb3; }
</style>

<h3>User Management</h3>

<ul class="nav nav-tabs">
    <?php foreach ($allowed_roles as $role): ?>
    <li class="nav-item">
        <a class="nav-link <?= $role == $filter_role ? 'active' : '' ?>" href="dashboard?page=users&role=<?= $role ?>"><?= ucfirst($role) ?>s</a>
    </li>
    <?php endforeach; ?>
</ul>

<?php if (isset($_SESSION['user_mgmt_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['user_mgmt_success']); unset($_SESSION['user_mgmt_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['user_mgmt_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['user_mgmt_error']); unset($_SESSION['user_mgmt_error']); ?></div>
<?php endif; ?>

<div class="card mt-3">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                                <?php else: ?>
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <span><?= htmlspecialchars($user['name']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] !== 'admin'): ?>
                        <form method="POST" action="dashboard?page=users&role=<?= $filter_role ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <div style="display: flex; gap: 8px;">
                                <select name="new_role" class="form-control form-control-sm">
                                    <option value="student" <?= $user['role'] == 'student' ? 'selected' : '' ?>>Student</option>
                                    <option value="instructor" <?= $user['role'] == 'instructor' ? 'selected' : '' ?>>Instructor</option>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" name="change_role" class="btn btn-primary btn-sm">Save</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <span style="color: #a0a0a0; font-size: 0.8rem;">(Locked)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="5" class="text-center">No users found for this filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>