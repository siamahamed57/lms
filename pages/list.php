<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<h2>Please log in to view your notifications.</h2>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark all notifications as read when the user visits this page.
db_execute("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", 's', [$user_id]);
$all_notifications = db_select("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC", 's', [$user_id]);

// A simple time_ago function for display
if (!function_exists('time_ago')) {
    function time_ago($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = ['y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}
?>
<style>
/* Styles for the full notification page */
.notifications-page { padding: 2rem; }
.notifications-page h1 { font-size: 1.8rem; font-weight: 600; margin-bottom: 2rem; }
.notification-list-full .notification-item {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    margin-bottom: 1rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
}
.notification-list-full .notification-item.unread {
    border-left: 4px solid var(--primary-color, #8b5cf6);
}
.notification-icon {
    font-size: 1.5rem;
    color: var(--primary-color, #8b5cf6);
}
</style>
<div class="notifications-page">
    <h1>All Notifications</h1>
    <div class="notification-list-full">
        <?php if (empty($all_notifications)): ?>
            <p>You have no notifications.</p>
        <?php else: foreach ($all_notifications as $notif): ?>
            <a href="<?= htmlspecialchars(json_decode($notif['data'])->link ?? '#') ?>" class="notification-item <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                <div class="notification-icon"><i class="fas fa-info-circle"></i></div>
                <div class="notification-content">
                    <p class="notification-title"><?= htmlspecialchars($notif['title']) ?></p>
                    <p class="notification-message"><?= htmlspecialchars($notif['message']) ?></p>
                    <p class="notification-time"><?= time_ago($notif['created_at']) ?></p>
                </div>
            </a>
        <?php endforeach; endif; ?>
    </div>
</div>