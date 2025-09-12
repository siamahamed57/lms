<?php
// admin/payments.php
require_once __DIR__ . '/payments-logic.php';

// Helper function for formatting currency
if (!function_exists('format_currency')) {
    function format_currency($amount) {
        return '$' . number_format($amount, 2);
    }
}
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    :root {
        --primary: #8b5cf6; --secondary: #ec4899; --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
        --glass-bg: rgba(255, 255, 255, 0.05); --glass-border: rgba(255, 255, 255, 0.15);
        --text-primary: #f0f0f0; --text-secondary: #a0a0a0;
    }
    .payment-dashboard { padding: 2rem; }
    .payment-tabs { display: flex; border-bottom: 1px solid var(--glass-border); margin-bottom: 2rem; }
    .payment-tab { padding: 0.75rem 1.5rem; cursor: pointer; color: var(--text-secondary); font-weight: 500; border-bottom: 2px solid transparent; transition: all 0.2s; }
    .payment-tab:hover { color: var(--text-primary); }
    .payment-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.5s; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; }
    .kpi-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; padding: 1.5rem; }
    .kpi-card .label { font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
    .kpi-card .value { font-size: 2rem; font-weight: 700; line-height: 1.1; }
    .kpi-card .icon { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: #fff; }
    .icon.revenue { background: var(--success); } .icon.payout { background: var(--danger); } .icon.pending { background: var(--warning); } .icon.commission { background: var(--primary); }

    .report-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; padding: 1.5rem; margin-top: 2rem; }
    .report-card h3 { font-size: 1.2rem; font-weight: 600; margin-bottom: 1.5rem; }
    .data-list ul { list-style: none; padding: 0; }
    .data-list li { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--glass-border); }
    .data-list li:last-child { border-bottom: none; }
    .item-primary { font-weight: 500; }
    .item-secondary { font-size: 0.85rem; color: var(--text-secondary); }
    .item-value { font-weight: 600; }
    .item-value.positive { color: var(--success); }

    .table-responsive-wrapper { overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { padding: 1rem; text-align: left; vertical-align: middle; border-bottom: 1px solid var(--glass-border); }
    .table thead th { color: var(--text-secondary); font-weight: 500; text-transform: uppercase; font-size: 0.8rem; }
    .badge { display: inline-block; padding: 0.4em 0.7em; font-size: 0.8rem; font-weight: 600; border-radius: 1rem; color: #fff; }
    .badge-success { background-color: rgba(40, 167, 69, 0.5); }
    .badge-danger { background-color: rgba(220, 53, 69, 0.5); }
    .badge-warning { background-color: rgba(255, 193, 7, 0.5); color: #111; }

    #print-payment-report {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        color: var(--text-secondary);
        padding: 0.6rem 1.25rem;
        border-radius: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    #print-payment-report:hover {
        background: rgba(139, 92, 246, 0.15); /* primary color with alpha */
        border-color: var(--primary);
        color: var(--text-primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.2);
    }
    #print-payment-report i {
        transition: transform 0.3s ease;
        margin-right: 0.5rem;
    }
    #print-payment-report:hover i { transform: scale(1.1) rotate(-5deg); }

    @media print {
        body > .dashboard-wrapper > .sidebar,
        body > .mobile-bottom-nav,
        body > .fab,
        .payment-tabs,
        #print-payment-report,
        .table td:last-child, 
        .table th:last-child,
        .edit-form-actions {
            display: none !important;
        }

        body, .payment-dashboard, .main-content {
            background: #fff !important;
            color: #000 !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            height: auto !important;
            overflow: visible !important;
        }

        .tab-content {
            display: block !important;
            animation: none !important;
            margin-bottom: 2rem;
        }

        .kpi-card, .report-card, .card {
            background: #f9f9f9 !important;
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            page-break-inside: avoid;
        }

        #revenuePayoutsChart { display: none; }
        a { text-decoration: none; color: #000; }
        .text-red-400 { color: #dc3545 !important; }
    }
</style>

<div class="payment-dashboard">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold">Payment Management</h1>
        <button id="print-payment-report"><i class="fas fa-print"></i>Print Report</button>
    </div>

    <div class="payment-tabs">
        <div class="payment-tab active" data-tab="overview">Overview</div>
        <div class="payment-tab" data-tab="payouts">Instructor Payouts</div>
        <div class="payment-tab" data-tab="transactions">Student Transactions</div>
        <div class="payment-tab" data-tab="revenue">Revenue</div>
    </div>

    <!-- Overview Tab -->
    <div id="tab-overview" class="tab-content active">
        <div class="kpi-grid">
            <div class="kpi-card"><div class="label"><span class="icon revenue"><i class="fas fa-chart-line"></i></span> Revenue (Today)</div><div class="value"><?= format_currency($payment_overview['revenue_today']) ?></div></div>
            <div class="kpi-card"><div class="label"><span class="icon revenue"><i class="fas fa-calendar-week"></i></span> Revenue (This Week)</div><div class="value"><?= format_currency($payment_overview['revenue_week']) ?></div></div>
            <div class="kpi-card"><div class="label"><span class="icon revenue"><i class="fas fa-calendar-alt"></i></span> Revenue (This Month)</div><div class="value"><?= format_currency($payment_overview['revenue_month']) ?></div></div>
            <div class="kpi-card"><div class="label"><span class="icon payout"><i class="fas fa-hand-holding-usd"></i></span> Payouts (This Month)</div><div class="value"><?= format_currency($payment_overview['payouts_month']) ?></div></div>
            <div class="kpi-card"><div class="label"><span class="icon pending"><i class="fas fa-hourglass-half"></i></span> Pending Payouts</div><div class="value"><?= format_currency($payment_overview['payouts_pending']) ?></div></div>
            <div class="kpi-card"><div class="label"><span class="icon commission"><i class="fas fa-percentage"></i></span> Admin Revenue (All Time)</div><div class="value"><?= format_currency($payment_overview['admin_commission']) ?></div></div>
        </div>

        <div class="report-card">
            <h3>Revenue vs Payouts (Last 12 Months)</h3>
            <div style="position: relative; height:350px;"><canvas id="revenuePayoutsChart"></canvas></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div class="report-card data-list">
                <h3>Top Earning Courses</h3>
                <ul><?php foreach($payment_overview['top_courses'] as $course): ?><li><div class="item-primary"><?= htmlspecialchars($course['title']) ?></div><div class="item-value positive"><?= format_currency($course['total_revenue']) ?></div></li><?php endforeach; ?></ul>
            </div>
            <div class="report-card data-list">
                <h3>Top Earning Instructors</h3>
                <ul><?php foreach($payment_overview['top_instructors'] as $instructor): ?><li><div class="item-primary"><?= htmlspecialchars($instructor['name']) ?></div><div class="item-value positive"><?= format_currency($instructor['total_earnings']) ?></div></li><?php endforeach; ?></ul>
            </div>
        </div>
    </div>

    <!-- Payouts Tab -->
    <div id="tab-payouts" class="tab-content">
        <h3>Instructor Payout Requests</h3>
        <p class="text-secondary mb-4">Review and process pending payout requests from instructors.</p>
        <div class="report-card"><div class="table-responsive-wrapper">
            <table class="table">
                <thead><tr><th>Instructor</th><th>Amount</th><th>Requested</th><th>Payment Details</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($payout_requests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['instructor_name']) ?><br><small class="text-secondary"><?= htmlspecialchars($req['instructor_email']) ?></small></td>
                        <td><?= format_currency($req['amount']) ?></td>
                        <td><?= date('M d, Y', strtotime($req['requested_at'])) ?></td>
                        <td><pre class="bg-gray-800 p-2 rounded-md text-sm"><?= htmlspecialchars($req['payment_details']) ?></pre></td>
                        <td><span class="badge badge-<?= $req['status'] == 'approved' ? 'success' : ($req['status'] == 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($req['status']) ?></span></td>
                        <td>
                            <?php if ($req['status'] == 'pending'): ?>
                            <form method="POST" action="dashboard?page=instructor-payouts">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>"><input type="hidden" name="update_instructor_withdrawal" value="1">
                                <select name="status" class="bg-gray-800 border border-gray-700 rounded-md p-1 mb-2"><option value="approved">Approve</option><option value="rejected">Reject</option></select>
                                <textarea name="admin_notes" class="bg-gray-800 border border-gray-700 rounded-md p-1 w-full text-sm" placeholder="Notes..."></textarea>
                                <button type="submit" class="bg-primary text-white px-3 py-1 rounded-md mt-2 text-sm">Update</button>
                            </form>
                            <?php else: ?><small>Processed on <?= date('M d, Y', strtotime($req['processed_at'])) ?></small><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <!-- Transactions Tab -->
    <div id="tab-transactions" class="tab-content">
        <h3>Student Transactions</h3>
        <p class="text-secondary mb-4">A log of all successful student payments.</p>
        <div class="report-card"><div class="table-responsive-wrapper">
            <table class="table">
                <thead><tr><th>Student</th><th>Course</th><th>Amount</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($student_transactions as $trans): ?>
                    <tr>
                        <td><?= htmlspecialchars($trans['student_name']) ?><br><small class="text-secondary"><?= htmlspecialchars($trans['student_email']) ?></small></td>
                        <td><?= htmlspecialchars($trans['course_title']) ?></td>
                        <td><?= format_currency($trans['sale_amount']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($trans['earned_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <!-- Revenue Tab -->
    <div id="tab-revenue" class="tab-content">
        <h3>Revenue Breakdown</h3>
        <p class="text-secondary mb-4">Details on platform commissions and earnings.</p>
        <div class="report-card data-list">
            <ul>
                <li><div class="item-primary">Total Sales (All Time)</div><div class="item-value positive"><?= format_currency($total_sales) ?></div></li>
                <li><div class="item-primary">Total Paid to Instructors</div><div class="item-value text-red-400">- <?= format_currency($total_instructor_earnings) ?></div></li>
                <li class="border-t-2 border-primary mt-2 pt-2"><div class="item-primary font-bold text-lg">Admin Gross Revenue</div><div class="item-value positive font-bold text-lg"><?= format_currency($payment_overview['admin_commission']) ?></div></li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tab switching logic
    const tabs = document.querySelectorAll('.payment-tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const target = tab.getAttribute('data-tab');
            tabContents.forEach(content => {
                content.classList.toggle('active', content.id === 'tab-' + target);
            });
        });
    });

    // Print button logic
    const printBtn = document.getElementById('print-payment-report');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }

    // Chart.js implementation
    const chartDefaultOptions = {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { color: 'rgba(255, 255, 255, 0.7)' } } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { color: 'rgba(255, 255, 255, 0.7)', callback: value => '$' + value }
            },
            x: {
                grid: { display: false },
                ticks: { color: 'rgba(255, 255, 255, 0.7)' }
            }
        },
        interaction: { intersect: false, mode: 'index' },
    };

    const revenuePayoutsCtx = document.getElementById('revenuePayoutsChart');
    if (revenuePayoutsCtx) {
        new Chart(revenuePayoutsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_data['labels']) ?>,
                datasets: [
                    {
                        label: 'Revenue',
                        data: <?= json_encode($chart_data['revenue']) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.6)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1, borderRadius: 5,
                    },
                    {
                        label: 'Payouts',
                        data: <?= json_encode($chart_data['payouts']) ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.6)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1, borderRadius: 5,
                    }
                ]
            },
            options: chartDefaultOptions
        });
    }
});
</script>