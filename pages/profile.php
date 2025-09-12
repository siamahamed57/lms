<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization check
if (!isset($_SESSION['user_id'])) {
    echo "Please log in.";
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Fetch current user data
$user_data = db_select("SELECT * FROM users WHERE id = ?", 'i', [$user_id]);
$user = $user_data[0] ?? null;

if (!$user) {
    echo "User not found.";
    exit;
}

// --- Message Handling ---
$success_message = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_success']);
$error_message = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_error']);

$notice_message = '';
if (isset($_GET['notice']) && $_GET['notice'] === 'complete_profile' && empty($success_message)) {
    $notice_message = "Welcome! Please complete your profile to access all dashboard features.";
}
?>

<style>
    :root {
        --primary-color: #b915ff; --primary-hover-color: #8b00cc;
        --glass-bg: rgba(255, 255, 255, 0.07); --glass-border: rgba(255, 255, 255, 0.2);
        --text-primary: #f0f0f0; --text-secondary: #a0a0a0; --input-bg: rgba(0, 0, 0, 0.3);
        --color-success: #28a745; --color-danger: #dc3545; --color-info: #17a2b8;
    }
    .profile-container { padding: 2rem;
         max-width: 1280px; 
         height: 620px;
         margin: auto; }
    .profile-form-container {
        background: var(--glass-bg); backdrop-filter: blur(15px); border-radius: 16px;
        border: 1px solid var(--glass-border); padding: 0; overflow: hidden;
    }
    .profile-form-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 2rem;
        border-bottom: 1px solid var(--glass-border);
        background: rgba(0,0,0,0.1);
    }
    .avatar-upload {
        position: relative;
        flex-shrink: 0;
    }
    .avatar-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid var(--glass-border);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .avatar-edit-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 32px;
        height: 32px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 2px solid var(--glass-bg);
        transition: all 0.3s ease;
    }
    .avatar-edit-btn:hover {
        transform: scale(1.1);
        background: var(--primary-hover-color);
    }
    .user-identity .user-name {
        font-size: 1.75rem;
        font-weight: 600;
        color: var(--text-primary);
    }
    .user-identity .user-role {
        font-size: 1rem;
        color: var(--text-secondary);
        text-transform: capitalize;
        background: var(--input-bg);
        padding: 0.2rem 0.6rem;
        border-radius: 6px;
        display: inline-block;
    }
    .profile-form-body { padding: 2rem; }
    .form-section-title { font-size: 1.25rem; font-weight: 500; margin-top: 2rem; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--glass-border); }
    .form-section-title:first-child { margin-top: 0; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary); font-weight: 500; }
    .form-group input, .form-group textarea {
        width: 100%; padding: 0.75rem 1rem; background: var(--input-bg);
        border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-primary);
        transition: all 0.3s ease; font-family: 'Poppins', sans-serif;
    }
    .form-group input:focus, .form-group textarea:focus {
        outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(185, 21, 255, 0.2);
    }
    .form-group input[readonly] { background: rgba(0,0,0,0.1); cursor: not-allowed; }
    .form-actions { margin-top: 2.5rem; text-align: right; }
    .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
    .btn-primary { background-color: var(--primary-color); color: #fff; }
    .btn-primary:hover { background-color: var(--primary-hover-color); }
    .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid transparent; }
    .alert-success { background-color: rgba(40, 167, 69, 0.15); border-color: rgba(40, 167, 69, 0.4); color: #a3ffb8; }
    .alert-danger { background-color: rgba(220, 53, 69, 0.15); border-color: rgba(220, 53, 69, 0.4); color: #ffacb3; }
    .alert-info { background-color: rgba(23, 162, 184, 0.15); border-color: rgba(23, 162, 184, 0.4); color: #99e6f4; }
    @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    @media (max-width: 500px) { .profile-form-header { flex-direction: column; text-align: center; } }
</style>

<div class="profile-container">
    <?php if ($notice_message): ?><div class="alert alert-info"><?= htmlspecialchars($notice_message) ?></div><?php endif; ?>
    <?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-danger"><?= $error_message ?></div><?php endif; ?>

    <div class="profile-form-container">
        <form method="POST" action="dashboard?page=profile" enctype="multipart/form-data">
            <div class="profile-form-header">
                <div class="avatar-upload">
                    <div class="avatar-preview">
                        <img id="avatar-preview-img" 
                             src="<?= htmlspecialchars($user['avatar'] ?? 'assets/images/default_avatar.png') ?>" 
                             alt="User Avatar"
                             onerror="this.onerror=null;this.src='assets/images/default_avatar.png';">
                    </div>
                    <label for="avatar-input" class="avatar-edit-btn" title="Change avatar">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" id="avatar-input" name="avatar" accept="image/jpeg, image/png, image/gif, image/webp" style="display: none;">
                </div>
                <div class="user-identity">
                    <h2 class="user-name"><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="user-role"><?= htmlspecialchars(ucfirst($user['role'])) ?></p>
                </div>
            </div>

            <div class="profile-form-body">
                <h3 class="form-section-title">Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g., +8801...">
                </div>
                <div class="form-group">
                    <label for="bio">Short Bio</label>
                    <textarea id="bio" name="bio" rows="3" placeholder="Tell us a little about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <?php if ($user_role === 'student'): ?>
                    <h3 class="form-section-title">Academic Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="university">University</label>
                            <input type="text" id="university" name="university" value="<?= htmlspecialchars($user['university'] ?? '') ?>" placeholder="e.g., AIUB">
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" id="department" name="department" value="<?= htmlspecialchars($user['department'] ?? '') ?>" placeholder="e.g., CSE">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="roll_no">Student ID / Roll No.</label>
                        <input type="text" id="roll_no" name="roll_no" value="<?= htmlspecialchars($user['roll_no'] ?? '') ?>">
                    </div>
                <?php endif; ?>

                <?php if ($user_role === 'instructor'): ?>
                    <h3 class="form-section-title">Payout Information</h3>
                    <div class="form-group">
                        <label for="payout_details">Payment Details</label>
                        <textarea id="payout_details" name="payout_details" rows="3" placeholder="e.g., Bank Name, Account Number, bKash/Nagad Number"><?= htmlspecialchars($user['payout_details'] ?? '') ?></textarea>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview-img');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                }
                reader.readAsDataURL(event.target.files[0]);
            }
        });
    }
});
</script>