<?php
// admin/overview-logic.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    return; // Silently exit if not admin
}

// --- 1. KPI Widgets Data ---
$kpi = [];

// Courses
$course_stats_raw = db_select("SELECT status, COUNT(id) as count FROM courses GROUP BY status");
$course_stats = array_column($course_stats_raw, 'count', 'status');
$kpi['courses_published'] = $course_stats['published'] ?? 0;
$kpi['courses_draft'] = $course_stats['draft'] ?? 0;
$kpi['courses_pending'] = $course_stats['pending'] ?? 0;
$kpi['coupons_used'] = db_select("SELECT SUM(times_used) as total FROM coupons")[0]['total'] ?? 0;

// Users
$kpi['total_students'] = db_select("SELECT COUNT(id) as count FROM users WHERE role = 'student'")[0]['count'] ?? 0;
$kpi['total_instructors'] = db_select("SELECT COUNT(id) as count FROM users WHERE role = 'instructor'")[0]['count'] ?? 0;

// Revenue
$kpi['revenue_today'] = db_select("SELECT SUM(sale_amount) as total FROM instructor_earnings WHERE DATE(earned_at) = CURDATE()")[0]['total'] ?? 0;
$kpi['revenue_month'] = db_select("SELECT SUM(sale_amount) as total FROM instructor_earnings WHERE MONTH(earned_at) = MONTH(CURDATE()) AND YEAR(earned_at) = YEAR(CURDATE())")[0]['total'] ?? 0;
$kpi['revenue_all_time'] = db_select("SELECT SUM(sale_amount) as total FROM instructor_earnings")[0]['total'] ?? 0;

// Enrollments & Completion
$kpi['total_enrollments'] = db_select("SELECT COUNT(id) as count FROM enrollments")[0]['count'] ?? 0;
$kpi['avg_completion_rate'] = db_select("SELECT AVG(progress) as avg_prog FROM enrollments")[0]['avg_prog'] ?? 0;


// --- 2. User Insights ---
$user_insights = [];
$user_insights['new_today'] = db_select("SELECT COUNT(id) as count FROM users WHERE role = 'student' AND DATE(created_at) = CURDATE()")[0]['count'] ?? 0;
$user_insights['new_week'] = db_select("SELECT COUNT(id) as count FROM users WHERE role = 'student' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")[0]['count'] ?? 0;
$user_insights['new_month'] = db_select("SELECT COUNT(id) as count FROM users WHERE role = 'student' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")[0]['count'] ?? 0;
$user_insights['recent_students'] = db_select("SELECT name, email, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 3");


// --- 3. Course Insights ---
$course_insights = [];
$course_insights['popular_courses'] = db_select("SELECT c.title, COUNT(e.id) as enrollments FROM courses c LEFT JOIN enrollments e ON c.id = e.course_id WHERE c.status = 'published' GROUP BY c.id ORDER BY enrollments DESC LIMIT 5");
$course_insights['pending_courses'] = db_select("SELECT c.id, c.title, u.name as instructor_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.status = 'pending' ORDER BY c.created_at DESC LIMIT 5");


// --- 4. Financial Snapshot & Charts ---
// Payouts
$payout_summary_raw = db_select("SELECT status, COUNT(id) as count, SUM(amount) as total FROM instructor_withdrawal_requests GROUP BY status");
$financial_snapshot['top_earning_courses'] = db_select("SELECT c.title, SUM(ie.sale_amount) as total_revenue FROM courses c JOIN instructor_earnings ie ON c.id = ie.course_id GROUP BY c.id ORDER BY total_revenue DESC LIMIT 3");
$payout_summary = [];
foreach($payout_summary_raw as $row) {
    $payout_summary[$row['status']] = $row;
}

// Revenue Chart (Last 30 days)
$sales_report_data = db_select("
    SELECT DATE(earned_at) as sale_date, SUM(sale_amount) as daily_total 
    FROM instructor_earnings 
    WHERE earned_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(earned_at) ORDER BY sale_date ASC
");
$sales_by_date = array_column($sales_report_data, 'daily_total', 'sale_date');
$sales_chart_labels = [];
$sales_chart_values = [];
$period = new DatePeriod(new DateTime('-29 days'), new DateInterval('P1D'), new DateTime('+1 day'));
foreach ($period as $date) {
    $formatted_date = $date->format('Y-m-d');
    $sales_chart_labels[] = $date->format('M d');
    $sales_chart_values[] = $sales_by_date[$formatted_date] ?? 0;
}

// User Distribution Pie Chart
$user_dist_data = db_select("SELECT role, COUNT(id) as count FROM users WHERE role IN ('student', 'instructor') GROUP BY role");
$user_dist_labels = array_map('ucfirst', array_column($user_dist_data, 'role'));
$user_dist_values = array_column($user_dist_data, 'count');

// Enrollments per Course Chart Data
$enrollment_chart_labels = array_column($course_insights['popular_courses'], 'title');
$enrollment_chart_values = array_column($course_insights['popular_courses'], 'enrollments');


// --- 5. Activity Feed ---
$activity_feed = [];
$activity_feed['latest_enrollments'] = db_select("
    SELECT s.name as student_name, c.title as course_title, e.enrolled_at 
    FROM enrollments e 
    JOIN users s ON e.student_id = s.id 
    JOIN courses c ON e.course_id = c.id 
    ORDER BY e.enrolled_at DESC LIMIT 5
");
$activity_feed['new_submissions'] = db_select("SELECT c.title, u.name as instructor_name, c.created_at FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.status = 'pending' ORDER BY c.created_at DESC LIMIT 3");
?>