<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    exit; // Silently exit if not a logged-in student
}

$student_id = $_SESSION['user_id'];

function generateReferralCodeForLogic($length = 8) {
    return substr(bin2hex(random_bytes(ceil($length / 2))), 0, $length);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_referral'])) {
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if ($course_id) {
        $settings = db_select("SELECT * FROM referral_settings WHERE course_id = ? AND is_enabled = 1", 'i', [$course_id]);
        if (!empty($settings)) {
            $code = generateReferralCodeForLogic();
            $expires_at = date('Y-m-d H:i:s', strtotime("+15 days"));
            db_execute("INSERT INTO referrals (referrer_id, course_id, referral_code, expires_at) VALUES (?, ?, ?, ?)", 'iiss', [$student_id, $course_id, $code, $expires_at]);
            $_SESSION['success_message'] = "Referral link generated!";
        } else {
            $_SESSION['error_message'] = "Referrals are not enabled for this course.";
        }
    }
    header('Location: dashboard?page=my-referrals');
    exit;
}