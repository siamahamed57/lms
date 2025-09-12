<?php
// admin/payments-logic.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    return; // Silently exit if not admin
}

// --- 1. Dashboard Overview ---
$payment_overview = [];

// Revenue
$payment_overview['revenue_today'] = db_select("SELECT SUM(sale_amount) as total FROM instructor_earnings WHERE DATE(earned_at) = CURDATE()")[0]['total'] ?? 0;
$payment_overview['revenue_week'] = db_select("SELECT SUM(sale_amount) as total FROM instructor_earnings WHERE earned_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")[0]['total'] ?? 0;
$payment_overview['revenue_month'] = db_select("SELECT SUM(sale_amount) as total FROM instructor_earnings WHERE MONTH(earned_at) = MONTH(CURDATE()) AND YEAR(earned_at) = YEAR(CURDATE())")[0]['total'] ?? 0;
$payment_overview['revenue_year'] = db_select("SELECT SUM(sale_amount) as total FROM instructor_earnings WHERE YEAR(earned_at) = YEAR(CURDATE())")[0]['total'] ?? 0;

// Payouts
$payment_overview['payouts_month'] = db_select("SELECT SUM(amount) as total FROM instructor_withdrawal_requests WHERE status = 'approved' AND MONTH(processed_at) = MONTH(CURDATE()) AND YEAR(processed_at) = YEAR(CURDATE())")[0]['total'] ?? 0;
$payment_overview['payouts_pending'] = db_select("SELECT SUM(amount) as total FROM instructor_withdrawal_requests WHERE status = 'pending'")[0]['total'] ?? 0;

// Commissions
$total_sales = db_select("SELECT SUM(sale_amount) as total FROM instructor_earnings")[0]['total'] ?? 0;
$total_instructor_earnings = db_select("SELECT SUM(earned_amount) as total FROM instructor_earnings")[0]['total'] ?? 0;
$payment_overview['admin_commission'] = $total_sales - $total_instructor_earnings;

// Top Earning Courses
$payment_overview['top_courses'] = db_select("SELECT c.title, SUM(ie.sale_amount) as total_revenue FROM courses c JOIN instructor_earnings ie ON c.id = ie.course_id GROUP BY c.id ORDER BY total_revenue DESC LIMIT 5");

// Top Earning Instructors
$payment_overview['top_instructors'] = db_select("SELECT u.name, SUM(ie.earned_amount) as total_earnings FROM users u JOIN instructor_earnings ie ON u.id = ie.instructor_id GROUP BY u.id ORDER BY total_earnings DESC LIMIT 5");

// Revenue vs Payouts Chart (Last 12 months)
$revenue_by_month = db_select("SELECT DATE_FORMAT(earned_at, '%Y-%m') as month, SUM(sale_amount) as total FROM instructor_earnings WHERE earned_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");
$payouts_by_month = db_select("SELECT DATE_FORMAT(processed_at, '%Y-%m') as month, SUM(amount) as total FROM instructor_withdrawal_requests WHERE status = 'approved' AND processed_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");

$chart_data = [];
$revenue_map = array_column($revenue_by_month, 'total', 'month');
$payouts_map = array_column($payouts_by_month, 'total', 'month');

for ($i = 11; $i >= 0; $i--) {
    $date = new DateTime("first day of -$i month");
    $month_key = $date->format('Y-m');
    $chart_data['labels'][] = $date->format('M Y');
    $chart_data['revenue'][] = $revenue_map[$month_key] ?? 0;
    $chart_data['payouts'][] = $payouts_map[$month_key] ?? 0;
}

// --- 2. Instructor Payouts ---
$payout_requests = db_select("SELECT wr.*, u.name as instructor_name, u.email as instructor_email FROM instructor_withdrawal_requests wr JOIN users u ON wr.instructor_id = u.id ORDER BY CASE WHEN wr.status = 'pending' THEN 1 ELSE 2 END, wr.requested_at DESC");

// --- 3. Student Transactions ---
$search_term = trim($_GET['search_trans'] ?? '');
$trans_sql = "SELECT ie.id, ie.sale_amount, ie.earned_at, c.title as course_title, s.name as student_name, s.email as student_email FROM instructor_earnings ie JOIN courses c ON ie.course_id = c.id JOIN enrollments en ON ie.enrollment_id = en.id JOIN users s ON en.student_id = s.id";
$trans_params = [];
$trans_types = '';
if (!empty($search_term)) {
    $trans_sql .= " WHERE s.name LIKE ? OR s.email LIKE ? OR c.title LIKE ?";
    $search_like = "%$search_term%";
    $trans_params = [$search_like, $search_like, $search_like];
    $trans_types = 'sss';
}
$trans_sql .= " ORDER BY ie.earned_at DESC LIMIT 100";
$student_transactions = db_select($trans_sql, $trans_types, $trans_params);
?>