<?php
// admin/overview.php
// This file is included by api/templates/overview.php
require_once __DIR__ . '/overview-logic.php';

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
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --primary: #8b5cf6; --secondary: #ec4899; --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
    --glass-bg: rgba(255, 255, 255, 0.05); --glass-border: rgba(255, 255, 255, 0.15);
    --text-primary: #f0f0f0; --text-secondary: #a0a0a0;
}
.admin-overview { display: grid; gap: 1.5rem; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; }
.kpi-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; padding: 1.5rem; }
.kpi-card .label { font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
.kpi-card .value { font-size: 2.25rem; font-weight: 700; line-height: 1.1; }
.kpi-card .sub-value { font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem; }
.kpi-card .icon { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: #fff; }
.icon.courses { background: var(--primary); } .icon.users { background: var(--secondary); } .icon.revenue { background: var(--success); } .icon.enrollments { background: var(--warning); }

.main-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
.grid-col-span-2 { grid-column: span 2; } .grid-col-span-1 { grid-column: span 1; }
.report-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; padding: 1.5rem; }
.report-card h3 { font-size: 1.2rem; font-weight: 600; margin-bottom: 1.5rem; }
.quick-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
.action-card { background: rgba(255,255,255,0.08); border-radius: 12px; padding: 1rem; text-align: center; transition: all 0.2s ease; text-decoration: none; color: var(--text-primary); }
.action-card:hover { transform: translateY(-4px); background: var(--primary); }
.action-card .icon { font-size: 1.5rem; margin-bottom: 0.5rem; }
.action-card .text { font-weight: 500; font-size: 0.9rem; }

.data-list ul { list-style: none; padding: 0; }
.data-list li { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--glass-border); }
.data-list li:last-child { border-bottom: none; }
.data-list .item-primary { font-weight: 500; }
.data-list .item-secondary { font-size: 0.85rem; color: var(--text-secondary); }
.data-list .item-value { font-weight: 600; }
.data-list .item-value.positive { color: var(--success); }
.data-list .item-value.negative { color: var(--danger); }

@media (max-width: 1024px) { .main-grid { grid-template-columns: repeat(1, 1fr); } .grid-col-span-2, .grid-col-span-1 { grid-column: span 1 !important; } }
</style>

<div class="admin-overview">
    <!-- 1. Quick KPIs -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="label"><span class="icon revenue"><i class="fas fa-dollar-sign"></i></span> Revenue</div>
            <div class="value">$<?= number_format($kpi['revenue_month'], 2) ?></div>
            <div class="sub-value">This Month</div>
        </div>
        <div class="kpi-card">
            <div class="label"><span class="icon users"><i class="fas fa-user-graduate"></i></span> Students</div>
            <div class="value"><?= number_format($kpi['total_students']) ?></div>
            <div class="sub-value">+<?= $user_insights['new_week'] ?> this week</div>
        </div>
        <div class="kpi-card">
            <div class="label"><span class="icon users"><i class="fas fa-chalkboard-teacher"></i></span> Instructors</div>
            <div class="value"><?= number_format($kpi['total_instructors']) ?></div>
            <div class="sub-value">&nbsp;</div>
        </div>
        <div class="kpi-card">
            <div class="label"><span class="icon courses"><i class="fas fa-book-open"></i></span> Courses</div>
            <div class="value"><?= number_format($kpi['courses_published']) ?></div>
            <div class="sub-value"><?= number_format($kpi['courses_pending']) ?> pending</div>
        </div>
        <div class="kpi-card">
            <div class="label"><span class="icon enrollments"><i class="fas fa-users"></i></span> Enrollments</div>
            <div class="value"><?= number_format($kpi['total_enrollments']) ?></div>
            <div class="sub-value">Avg. Completion: <?= number_format($kpi['avg_completion_rate'], 1) ?>%</div>
        </div>
        <div class="kpi-card">
            <div class="label"><span class="icon revenue" style="background: var(--danger);"><i class="fas fa-tags"></i></span> Coupons Used</div>
            <div class="value"><?= number_format($kpi['coupons_used']) ?></div>
            <div class="sub-value">Total Redemptions</div>
        </div>
    </div>

    <div class="main-grid">
        <!-- 6. Revenue Graph -->
        <div class="report-card grid-col-span-2">
            <h3>Revenue Trend (Last 30 Days)</h3>
            <div style="position: relative; height:350px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- 2. User Insights -->
        <div class="report-card grid-col-span-1 data-list">
            <h3>User Insights</h3>
            <ul>
                <li><div><div class="item-primary">New Sign-ups (Today)</div></div> <div class="item-value"><?= $user_insights['new_today'] ?></div></li>
                <li><div><div class="item-primary">New Sign-ups (Week)</div></div> <div class="item-value"><?= $user_insights['new_week'] ?></div></li>
                <li><div><div class="item-primary">New Sign-ups (Month)</div></div> <div class="item-value"><?= $user_insights['new_month'] ?></div></li>
            </ul>
            <h4 style="font-size: 1rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem;">Recently Registered</h4>
            <ul>
                <?php if(empty($user_insights['recent_students'])): ?><li><div class="item-secondary">No new students today.</div></li><?php else: foreach($user_insights['recent_students'] as $student): ?>
                <li>
                    <div>
                        <div class="item-primary"><?= htmlspecialchars($student['name']) ?></div>
                        <div class="item-secondary"><?= htmlspecialchars($student['email']) ?></div>
                    </div>
                    <div class="item-secondary"><?= time_ago($student['created_at']) ?></div>
                </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>

        <!-- 3. Course Insights -->
        <div class="report-card grid-col-span-1 data-list">
            <h3>Course Insights</h3>
            <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">Most Popular Courses</h4>
            <ul>
                <?php foreach($course_insights['popular_courses'] as $course): ?>
                <li>
                    <div><div class="item-primary"><?= htmlspecialchars($course['title']) ?></div></div>
                    <div class="item-value"><?= $course['enrollments'] ?> enrollments</div>
                </li>
                <?php endforeach; ?>
            </ul>
            <h4 style="font-size: 1rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem;">Pending Approval</h4>
            <ul>
                <?php if(empty($course_insights['pending_courses'])): ?>
                    <li><div><div class="item-secondary">No courses are pending review.</div></div></li>
                <?php else: foreach($course_insights['pending_courses'] as $course): ?>
                <li>
                    <div>
                        <div class="item-primary"><?= htmlspecialchars($course['title']) ?></div>
                        <div class="item-secondary">by <?= htmlspecialchars($course['instructor_name']) ?></div>
                    </div>
                    <a href="dashboard?page=manage&status=pending" class="item-value" style="color: var(--primary)">Review</a>
                </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>

        <!-- Enrollments Chart -->
        <div class="report-card grid-col-span-1">
            <h3>Enrollments per Course</h3>
            <div style="position: relative; height:250px;">
                <canvas id="enrollmentChart"></canvas>
            </div>
        </div>

        <!-- 4. Financial Snapshot -->
        <div class="report-card grid-col-span-1 data-list">
            <h3>Financial Snapshot</h3>
            <div class="data-list">
                <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">All-Time Revenue</h4>
                <ul><li><div><div class="item-primary">Total Sales</div></div> <div class="item-value positive">$<?= number_format($kpi['revenue_all_time'], 2) ?></div></li></ul>
            </div>
            <div class="data-list" style="margin-top: 1.5rem;">
                <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">Instructor Payouts</h4>
                <ul>
                    <li><div><div class="item-primary">Pending</div><div class="item-secondary"><?= $payout_summary['pending']['count'] ?? 0 ?> requests</div></div> <div class="item-value negative">-$<?= number_format($payout_summary['pending']['total'] ?? 0, 2) ?></div></li>
                    <li><div><div class="item-primary">Approved</div><div class="item-secondary"><?= $payout_summary['approved']['count'] ?? 0 ?> requests</div></div> <div class="item-value positive">$<?= number_format($payout_summary['approved']['total'] ?? 0, 2) ?></div></li>
                </ul>
            </div>
            <div class="data-list" style="margin-top: 1.5rem;">
                <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">Top Earning Courses</h4>
                 <ul>
                    <?php if(empty($financial_snapshot['top_earning_courses'])): ?><li><div class="item-secondary">No sales data yet.</div></li><?php else: foreach($financial_snapshot['top_earning_courses'] as $course): ?>
                    <li>
                        <div><div class="item-primary"><?= htmlspecialchars($course['title']) ?></div></div>
                        <div class="item-value positive">$<?= number_format($course['total_revenue'], 2) ?></div>
                    </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>

        <!-- 5. Activity Feed & User Distro -->
        <div class="report-card grid-col-span-2">
             <div class="main-grid" style="grid-template-columns: 1fr 300px; gap: 2rem;">
                <div class="data-list">
                    <h3>Activity Feed</h3>
                    <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">Latest Enrollments</h4>
                    <ul>
                        <?php foreach($activity_feed['latest_enrollments'] as $enrollment): ?>
                        <li>
                            <div>
                                <div class="item-primary"><?= htmlspecialchars($enrollment['student_name']) ?></div>
                                <div class="item-secondary">enrolled in "<?= htmlspecialchars($enrollment['course_title']) ?>"</div>
                            </div>
                            <div class="item-secondary"><?= time_ago($enrollment['enrolled_at']) ?></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="text-center">
                    <h3>User Distribution</h3>
                    <div style="position: relative; height:250px;">
                        <canvas id="userDistChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Quick Actions -->
        <div class="report-card grid-col-span-2">
            <h3>Quick Actions</h3>
            <div class="quick-actions-grid">
                <a href="dashboard?page=create-course" class="action-card"><div class="icon"><i class="fas fa-plus-circle"></i></div><div class="text">Add Course</div></a>
                <a href="dashboard?page=users" class="action-card"><div class="icon"><i class="fas fa-users-cog"></i></div><div class="text">Manage Users</div></a>
                <a href="dashboard?page=instructor-payouts" class="action-card"><div class="icon"><i class="fas fa-money-check-alt"></i></div><div class="text">Process Payouts</div></a>
                <a href="dashboard?page=reports" class="action-card"><div class="icon"><i class="fas fa-chart-bar"></i></div><div class="text">Full Reports</div></a>
                <a href="dashboard?page=settings" class="action-card"><div class="icon"><i class="fas fa-cogs"></i></div><div class="text">Settings</div></a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartDefaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { 
                    color: 'rgba(255, 255, 255, 0.7)',
                    callback: function(value) { return '$' + value; }
                }
            },
            x: {
                grid: { display: false },
                ticks: { color: 'rgba(255, 255, 255, 0.7)' }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index',
        },
    };

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($sales_chart_labels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($sales_chart_values) ?>,
                    fill: true,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: chartDefaultOptions
        });
    }

    // Enrollments Chart
    const enrollmentCtx = document.getElementById('enrollmentChart');
    if(enrollmentCtx) {
        new Chart(enrollmentCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($enrollment_chart_labels) ?>,
                datasets: [{
                    label: 'Enrollments',
                    data: <?= json_encode($enrollment_chart_values) ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.6)',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                }]
            },
            options: {
                ...chartDefaultOptions,
                scales: {
                    ...chartDefaultOptions.scales,
                    y: { ...chartDefaultOptions.scales.y, ticks: { ...chartDefaultOptions.scales.y.ticks, callback: function(value) { return value; } } }
                }
            }
        });
    }

    // User Distribution Pie Chart
    const userDistCtx = document.getElementById('userDistChart');
    if (userDistCtx) {
        new Chart(userDistCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($user_dist_labels) ?>,
                datasets: [{
                    label: 'User Distribution',
                    data: <?= json_encode($user_dist_values) ?>,
                    backgroundColor: [
                        'rgba(236, 72, 153, 0.7)', // secondary
                        'rgba(139, 92, 246, 0.7)', // primary
                    ],
                    borderColor: [
                        'rgba(236, 72, 153, 1)',
                        'rgba(139, 92, 246, 1)',
                    ],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'rgba(255, 255, 255, 0.8)',
                            font: { size: 14 }
                        }
                    }
                }
            }
        });
    }
});
</script>