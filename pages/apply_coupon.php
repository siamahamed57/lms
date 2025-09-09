<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Use a relative path to find the db.php file from the 'pages' directory.
require_once __DIR__ . '/../includes/db.php';

// --- Authorization & Validation ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to apply a coupon.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$coupon_code = strtoupper(trim($data['coupon_code'] ?? ''));
$course_id = intval($data['course_id'] ?? 0);

if (empty($coupon_code) || $course_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

// Fetch course price
$course_data = db_select("SELECT price FROM courses WHERE id = ?", 'i', [$course_id]);
if (empty($course_data)) {
    echo json_encode(['success' => false, 'message' => 'Course not found.']);
    exit;
}
$course_price = (float) $course_data[0]['price'];

// Fetch coupon
$coupon_data = db_select("SELECT * FROM coupons WHERE code = ?", 's', [$coupon_code]);
if (empty($coupon_data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid coupon code.']);
    exit;
}
$coupon = $coupon_data[0];

// --- Validate Coupon ---
if ($coupon['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'This coupon is not active.']);
    exit;
}
if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    db_execute("UPDATE coupons SET status = 'expired' WHERE id = ?", 'i', [$coupon['id']]);
    echo json_encode(['success' => false, 'message' => 'This coupon has expired.']);
    exit;
}
if ($coupon['usage_limit'] !== null && (int)$coupon['times_used'] >= (int)$coupon['usage_limit']) {
    echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit.']);
    exit;
}

// --- Calculate Discount ---
$discount_amount = 0.0;
$coupon_value = (float) $coupon['value'];
$coupon_type = trim($coupon['type']);

if ($coupon_type === 'fixed') {
    $discount_amount = $coupon_value;
} elseif ($coupon_type === 'percentage') {
    $discount_amount = ($coupon_value / 100.0) * $course_price;
}

// Ensure discount is not more than the price
$discount_amount = min($course_price, $discount_amount);
$new_price = $course_price - $discount_amount;

// --- Store in session for final processing on form submission ---
$_SESSION['applied_coupon'] = [
    'id' => $coupon['id'],
    'code' => $coupon['code'],
    'course_id' => $course_id,
];

// --- Success Response ---
echo json_encode([
    'success' => true,
    'message' => 'Coupon applied successfully!',
    'discount_amount' => round($discount_amount, 2),
    'new_price' => round($new_price, 2)
]);
?>