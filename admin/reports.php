<?php
// admin/reports.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /lms/login');
    exit;
}

// Include the data fetching logic
require_once __DIR__ . '/reports-logic.php';
?>

<style>
/* ---- [ Import Modern Font & Icons ] ---- */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

/* ---- [ CSS Variables for Easy Theming ] ---- */
:root {
    --primary-color: #b915ff;
    --primary-hover-color: #8b00cc;
    --glass-bg: rgba(255, 255, 255, 0.07);
    --glass-border: rgba(255, 255, 255, 0.2);
    --text-primary: #f0f0f0;
    --text-secondary: #a0a0a0;
    --input-bg: rgba(0, 0, 0, 0.3);
}

.reports-container {
    padding: 1rem;
}

.reports-header {
    margin-bottom: 2rem;
}

.reports-header h1 {
    font-size: 2.25rem;
    font-weight: 700;
}

.reports-header p {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

/* KPI Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.kpi-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: all 0.3s ease;
}
.kpi-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.kpi-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    color: #fff;
}
.kpi-icon.revenue { background: linear-gradient(135deg, #10b981, #059669); }
.kpi-icon.students { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.kpi-icon.instructors { background: linear-gradient(135deg, #f97316, #ea580c); }
.kpi-icon.courses { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

.kpi-info .value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.2;
}
.kpi-info .label {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

/* Chart & Table Grid */
.reports-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 2rem;
}
@media (min-width: 1024px) {
    .reports-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.report-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    padding: 1.5rem 2rem;
}
.report-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

/* Table styles */
.report-table {
    width: 100%;
    border-collapse: collapse;
}
.report-table th, .report-table td {
    padding: 0.75rem 0.5rem;
    text-align: left;
    border-bottom: 1px solid var(--glass-border);
}
.report-table thead th {
    color: var(--text-secondary);
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: 500;
}
.report-table tbody tr:last-child td {
    border-bottom: none;
}
.report-table .course-title { font-weight: 500; }
.report-table .instructor-name { font-size: 0.85rem; color: var(--text-secondary); }
.report-table .enrollment-count { font-weight: 600; text-align: right; }

</style>

<div class="reports-container">
    <div class="reports-header">
        <h1>Analytics & Reports</h1>
        <p>An overview of your platform's performance.</p>
    </div>

    <!-- KPI Widgets -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon revenue"><i class="fas fa-dollar-sign"></i></div>
            <div class="kpi-info">
                <div class="value">$<?= number_format($kpi_total_revenue, 2) ?></div>
                <div class="label">Total Revenue</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon students"><i class="fas fa-user-graduate"></i></div>
            <div class="kpi-info">
                <div class="value"><?= $kpi_total_students ?></div>
                <div class="label">Total Students</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon instructors"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="kpi-info">
                <div class="value"><?= $kpi_total_instructors ?></div>
                <div class="label">Total Instructors</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon courses"><i class="fas fa-book-open"></i></div>
            <div class="kpi-info">
                <div class="value"><?= $kpi_total_courses ?></div>
                <div class="label">Published Courses</div>
            </div>
        </div>
    </div>

    <div class="reports-grid">
        <!-- Sales Report Chart -->
        <div class="report-card">
            <h3>Sales Over Last 30 Days</h3>
            <canvas id="salesChart"></canvas>
        </div>

        <!-- User Registrations Chart -->
        <div class="report-card">
            <h3>New User Registrations</h3>
            <canvas id="registrationsChart"></canvas>
        </div>

        <!-- Most Popular Courses Table -->
        <div class="report-card">
            <h3>Most Popular Courses</h3>
            <table class="report-table">
                <thead>
                    <tr><th>Course</th><th>Enrollments</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($popular_courses)): ?>
                        <tr><td colspan="2" class="text-center py-4">No enrollment data available.</td></tr>
                    <?php else: foreach($popular_courses as $course): ?>
                        <tr>
                            <td>
                                <div class="course-title"><?= htmlspecialchars($course['title']) ?></div>
                                <div class="instructor-name">by <?= htmlspecialchars($course['instructor_name']) ?></div>
                            </td>
                            <td class="enrollment-count"><?= $course['enrollment_count'] ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Instructors Table -->
        <div class="report-card">
            <h3>Top Performing Instructors</h3>
            <table class="report-table">
                <thead>
                    <tr><th>Instructor</th><th>Courses</th><th>Total Earnings</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($top_instructors)): ?>
                        <tr><td colspan="3" class="text-center py-4">No instructor data available.</td></tr>
                    <?php else: foreach($top_instructors as $instructor): ?>
                        <tr>
                            <td><div class="course-title"><?= htmlspecialchars($instructor['name']) ?></div></td>
                            <td class="text-center"><?= $instructor['course_count'] ?></td>
                            <td class="enrollment-count">$<?= number_format($instructor['total_earnings'], 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartConfig = {
        defaults: {
            font: {
                family: 'Poppins, sans-serif',
                size: 13,
            },
            color: 'rgba(255, 255, 255, 0.7)',
        },
        plugins: {
            legend: {
                display: false
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { color: 'rgba(255, 255, 255, 0.7)' }
            },
            x: {
                grid: { display: false },
                ticks: { color: 'rgba(255, 255, 255, 0.7)' }
            }
        }
    };

    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($sales_chart_labels) ?>,
                datasets: [{
                    label: 'Daily Sales',
                    data: <?= json_encode($sales_chart_values) ?>,
                    fill: true,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                }]
            },
            options: chartConfig
        });
    }

    // Registrations Chart
    const regCtx = document.getElementById('registrationsChart');
    if (regCtx) {
        new Chart(regCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($registrations_chart_labels) ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?= json_encode($registrations_chart_values) ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.6)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                }]
            },
            options: chartConfig
        });
    }
});
</script>