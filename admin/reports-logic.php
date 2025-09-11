<?php
// admin/reports-logic.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // This file is included, so we just stop execution. The view file will handle the error message.
    return;
}

// --- 1. KPI Widgets Data ---
$total_revenue_data = db_select("SELECT SUM(sale_amount) as total FROM instructor_earnings");
$kpi_total_revenue = $total_revenue_data[0]['total'] ?? 0;

$total_students_data = db_select("SELECT COUNT(id) as total FROM users WHERE role = 'student'");
$kpi_total_students = $total_students_data[0]['total'] ?? 0;

$total_instructors_data = db_select("SELECT COUNT(id) as total FROM users WHERE role = 'instructor'");
$kpi_total_instructors = $total_instructors_data[0]['total'] ?? 0;

$total_courses_data = db_select("SELECT COUNT(id) as total FROM courses WHERE status = 'published'");
$kpi_total_courses = $total_courses_data[0]['total'] ?? 0;


// --- 2. Sales Report Data (Last 30 days) ---
$sales_report_data = db_select("
    SELECT 
        DATE(earned_at) as sale_date, 
        SUM(sale_amount) as daily_total 
    FROM instructor_earnings 
    WHERE earned_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(earned_at) 
    ORDER BY sale_date ASC
");

$sales_chart_labels = [];
$sales_chart_values = [];
// Create a date range for the last 30 days to ensure all days are present
$period = new DatePeriod(
     new DateTime('-30 days'),
     new DateInterval('P1D'),
     new DateTime('+1 day')
);
$sales_by_date = [];
foreach ($sales_report_data as $row) {
    $sales_by_date[$row['sale_date']] = $row['daily_total'];
}
foreach ($period as $date) {
    $formatted_date = $date->format('Y-m-d');
    $sales_chart_labels[] = $date->format('M d');
    $sales_chart_values[] = $sales_by_date[$formatted_date] ?? 0;
}


// --- 3. User Registrations Data (Last 30 days) ---
$registrations_report_data = db_select("
    SELECT 
        DATE(created_at) as reg_date, 
        COUNT(id) as daily_count 
    FROM users 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY reg_date ASC
");
$registrations_by_date = [];
foreach ($registrations_report_data as $row) {
    $registrations_by_date[$row['reg_date']] = $row['daily_count'];
}
$registrations_chart_labels = [];
$registrations_chart_values = [];
foreach ($period as $date) { // Re-using the same period
    $formatted_date = $date->format('Y-m-d');
    $registrations_chart_labels[] = $date->format('M d');
    $registrations_chart_values[] = $registrations_by_date[$formatted_date] ?? 0;
}


// --- 4. Most Popular Courses (by enrollment) ---
$popular_courses = db_select("SELECT c.title, u.name as instructor_name, COUNT(e.id) as enrollment_count FROM courses c LEFT JOIN enrollments e ON c.id = e.course_id JOIN users u ON c.instructor_id = u.id GROUP BY c.id ORDER BY enrollment_count DESC LIMIT 5");


// --- 5. Top Performing Instructors (by earnings) ---
$top_instructors = db_select("SELECT u.name, COUNT(DISTINCT c.id) as course_count, COALESCE(SUM(ie.earned_amount), 0) as total_earnings FROM users u LEFT JOIN courses c ON u.id = c.instructor_id LEFT JOIN instructor_earnings ie ON u.id = ie.instructor_id WHERE u.role = 'instructor' GROUP BY u.id ORDER BY total_earnings DESC LIMIT 5");

?>