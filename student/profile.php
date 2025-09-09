<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login'); // Redirect to login if not a logged-in student
    exit;
}

$student_id = $_SESSION['user_id'];

// Fetch current student data
$student_data_raw = db_select("SELECT name, email, phone, university, department, roll_no, bio, avatar FROM users WHERE id = ?", "i", [$student_id]);
if (empty($student_data_raw)) {
    die("Error: Could not find student data.");
}
$student = $student_data_raw[0];

// Message Handling
$success_message = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_success']);
$error_message = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_error']);

$show_congrats_popup = $_SESSION['show_profile_completion_popup'] ?? false;
unset($_SESSION['show_profile_completion_popup']);
?>

<style>
    .profile-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        background-color: var(--surface);
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }
    .profile-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 1rem;
        border: 4px solid var(--primary);
    }
    .profile-header h1 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text);
    }
    .profile-form .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    .profile-form .form-group {
        display: flex;
        flex-direction: column;
    }
    .profile-form .full-width {
        grid-column: 1 / -1;
    }
    .profile-form label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    .profile-form input,
    .profile-form textarea {
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        background-color: var(--surface-light);
    }
    .profile-form input:read-only {
        background-color: var(--bg-secondary);
        cursor: not-allowed;
    }
    .btn-submit-profile {
        display: block;
        width: 100%;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
        background: var(--gradient);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        margin-top: 2rem;
    }

    /* --- Congratulations Popup Styles --- */
    .congrats-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        display: none; /* Initially hidden */
        align-items: center;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }
    .congrats-modal {
        background: var(--surface);
        padding: 2.5rem;
        border-radius: 20px;
        text-align: center;
        max-width: 450px;
        width: 90%;
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease-in-out;
    }
    .congrats-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
        animation: icon-pop 0.5s 0.2s ease-out backwards;
    }
    @keyframes icon-pop {
        from { transform: scale(0); }
        to { transform: scale(1); }
    }
    .congrats-modal h2 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }
    .congrats-modal p {
        color: var(--text-light);
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }
    .congrats-modal button {
        background: var(--gradient);
        color: white;
        border: none;
        padding: 0.8rem 2.5rem;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .congrats-modal button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(185, 21, 255, 0.3);
    }
</style>

<?php if ($show_congrats_popup): ?>
<div id="congrats-popup-overlay" class="congrats-overlay">
    <div class="congrats-modal">
        <div class="congrats-icon"><i class="fas fa-check"></i></div>
        <h2>Congratulations!</h2>
        <p>Your profile is now complete. You have unlocked full access to your dashboard!</p>
        <button id="close-congrats-popup">Awesome!</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const overlay = document.getElementById('congrats-popup-overlay');
        const closeBtn = document.getElementById('close-congrats-popup');
        if (!overlay || !closeBtn) return;

        const closePopup = () => {
            overlay.style.opacity = '0';
            overlay.querySelector('.congrats-modal').style.transform = 'scale(0.9)';
            setTimeout(() => overlay.style.display = 'none', 300);
        };

        // Show the popup with animation
        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.style.opacity = '1';
            overlay.querySelector('.congrats-modal').style.transform = 'scale(1)';
        }, 10);

        closeBtn.addEventListener('click', closePopup);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closePopup();
        });
    });
</script>
<?php endif; ?>

<div class="profile-container">
    <div class="profile-header">
        <img src="<?= htmlspecialchars($student['avatar'] ?? 'assets/images/default-avatar.png') ?>" alt="Avatar" class="profile-avatar">
        <h1>My Profile</h1>
        <p class="text-gray-500">Keep your information up to date.</p>
    </div>

    <?php if ($success_message): ?><div class="alert alert-success mb-4"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-error mb-4"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

    <form class="profile-form" action="dashboard?page=profile" method="POST">
        <div class="form-grid">
            <div class="form-group"><label for="name">Full Name</label><input type="text" id="name" name="name" value="<?= htmlspecialchars($student['name']) ?>" required></div>
            <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" value="<?= htmlspecialchars($student['email']) ?>" readonly></div>
            <div class="form-group"><label for="phone">Phone Number</label><input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>" placeholder="e.g., 01712345678" required></div>
            <div class="form-group"><label for="university">University</label><input type="text" id="university" name="university" value="<?= htmlspecialchars($student['university'] ?? '') ?>" placeholder="e.g., University of Dhaka" required></div>
            <div class="form-group"><label for="department">Department</label><input type="text" id="department" name="department" value="<?= htmlspecialchars($student['department'] ?? '') ?>" placeholder="e.g., Computer Science" required></div>
            <div class="form-group"><label for="roll_no">Roll / ID</label><input type="text" id="roll_no" name="roll_no" value="<?= htmlspecialchars($student['roll_no'] ?? '') ?>" placeholder="e.g., 180204123"></div>
            <div class="form-group full-width"><label for="bio">Bio</label><textarea id="bio" name="bio" rows="4" placeholder="Tell us a little about yourself..."><?= htmlspecialchars($student['bio'] ?? '') ?></textarea></div>
        </div>
        <button type="submit" class="btn-submit-profile">Update Profile</button>
    </form>
</div>