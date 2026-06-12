<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireUserType(['finance', 'admin']);

// Get financial statistics
$stats = [];

// Total revenue
$stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
$stats['total_revenue'] = $stmt->fetchColumn() ?? 0;

// This month revenue
$stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stats['monthly_revenue'] = $stmt->fetchColumn() ?? 0;

// Pending payments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending_review'");
$stats['pending_count'] = $stmt->fetchColumn();

// Total transactions
$stmt = $pdo->query("SELECT COUNT(*) as count FROM payments");
$stats['total_transactions'] = $stmt->fetchColumn();

// Subscription revenue
$stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE plan_type IN ('basic', 'premium') AND status = 'completed'");
$stats['subscription_revenue'] = $stmt->fetchColumn() ?? 0;

// Boost revenue
$stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE plan_type = 'boost' AND status = 'completed'");
$stats['boost_revenue'] = $stmt->fetchColumn() ?? 0;

// Pending withdrawals (if table exists)
$stats['pending_withdrawals'] = 0;
if ($pdo->query("SHOW TABLES LIKE 'withdrawal_requests'")->rowCount() > 0) {
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM withdrawal_requests WHERE status = 'pending'");
    $stats['pending_withdrawals'] = $stmt->fetchColumn() ?? 0;
}

// Get recent payments
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name, u.email 
    FROM payments p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 15
");
$stmt->execute();
$recent_payments = $stmt->fetchAll();

// Get monthly data for chart
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%M') as month,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue,
        COUNT(*) as transactions
    FROM payments
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$monthly_data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Dashboard - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .finance-gradient { background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); }
        .stat-card { transition: all 0.3s ease; background: white; border-radius: 20px; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.12); }
        .status-pending_review { background: #fed7aa; color: #9a3412; }
        .status-completed { background: #d1fae5; color: #059669; }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'includes/navbar.php'; ?>

<!-- Finance Header -->
<div class="finance-gradient text-white py-6">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Finance Dashboard</h1>
                <p class="text-teal-100 text-sm mt-1">Track revenue, manage payments, and financial reports</p>
            </div>
            <div class="flex gap-3">
                <a href="finance_reports.php" class="bg-white/20 px-4 py-2 rounded-lg text-sm hover:bg-white/30 transition">
                    <i class="fas fa-chart-line mr-2"></i> Reports
                </a>
                <a href="admin_finance.php" class="bg-white/20 px-4 py-2 rounded-lg text-sm hover:bg-white/30 transition">
                    <i class="fas fa-cog mr-2"></i> Manage Payments
                </a>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="stat-card p-4 shadow-sm border-l-4 border-teal-500">
            <p class="text-gray-500 text-xs uppercase">Total Revenue</p>
            <p class="text-2xl font-bold text-teal-600"><?php echo number_format($stats['total_revenue'], 2); ?> EGP</p>
        </div>
        <div class="stat-card p-4 shadow-sm border-l-4 border-emerald-500">
            <p class="text-gray-500 text-xs uppercase">This Month</p>
            <p class="text-2xl font-bold text-emerald-600"><?php echo number_format($stats['monthly_revenue'], 2); ?> EGP</p>
        </div>
        <div class="stat-card p-4 shadow-sm border-l-4 border-purple-500">
            <p class="text-gray-500 text-xs uppercase">Subscriptions</p>
            <p class="text-2xl font-bold text-purple-600"><?php echo number_format($stats['subscription_revenue'], 2); ?> EGP</p>
        </div>
        <div class="stat-card p-4 shadow-sm border-l-4 border-blue-500">
            <p class="text-gray-500 text-xs uppercase">Boosts</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['boost_revenue'], 2); ?> EGP</p>
        </div>
        <div class="stat-card p-4 shadow-sm border-l-4 border-amber-500">
            <p class="text-gray-500 text-xs uppercase">Pending Review</p>
            <p class="text-2xl font-bold text-amber-600"><?php echo $stats['pending_count']; ?></p>
        </div>
        <div class="stat-card p-4 shadow-sm border-l-4 border-orange-500">
            <p class="text-gray-500 text-xs uppercase">Transactions</p>
            <p class="text-2xl font-bold text-orange-600"><?php echo number_format($stats['total_transactions']); ?></p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Revenue Trend</h3>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Transaction Volume</h3>
            <canvas id="transactionChart" height="200"></canvas>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid md:grid-cols-4 gap-4 mb-8">
        <a href="admin_finance.php?status=pending_review" class="bg-white rounded-xl p-4 text-center shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-clock text-amber-600 text-xl"></i>
            </div>
            <h3 class="font-semibold text-sm">Review Payments</h3>
            <p class="text-xs text-gray-500 mt-1"><?php echo $stats['pending_count']; ?> pending reviews</p>
        </a>
        <a href="finance_reports.php?type=monthly" class="bg-white rounded-xl p-4 text-center shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-chart-bar text-teal-600 text-xl"></i>
            </div>
            <h3 class="font-semibold text-sm">Generate Report</h3>
            <p class="text-xs text-gray-500 mt-1">Monthly financial summary</p>
        </a>
        <a href="finance_subscriptions.php" class="bg-white rounded-xl p-4 text-center shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-crown text-purple-600 text-xl"></i>
            </div>
            <h3 class="font-semibold text-sm">Subscriptions</h3>
            <p class="text-xs text-gray-500 mt-1">Manage active plans</p>
        </a>
        <a href="finance_withdrawals.php" class="bg-white rounded-xl p-4 text-center shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-money-bill-wave text-red-600 text-xl"></i>
            </div>
            <h3 class="font-semibold text-sm">Withdrawals</h3>
            <p class="text-xs text-gray-500 mt-1">Process payouts</p>
        </a>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">
                <i class="fas fa-history text-teal-500 mr-2"></i> Recent Transactions
            </h3>
            <a href="admin_finance.php" class="text-sm text-teal-600 hover:text-teal-700">View All →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ID</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">User</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Method</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recent_payments as $payment): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-sm">#<?php echo $payment['id']; ?></td>
                        <td class="px-5 py-3">
                            <div class="text-sm font-medium"><?php echo htmlspecialchars($payment['full_name']); ?></div>
                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($payment['email']); ?></div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $payment['plan_type'] === 'premium' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'; ?>">
                                <?php echo ucfirst($payment['plan_type'] ?? 'Boost'); ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 font-semibold"><?php echo number_format($payment['amount'], 2); ?> EGP</td>
                        <td class="px-5 py-3 text-sm"><?php echo ucfirst($payment['payment_method'] ?? 'N/A'); ?></td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 text-xs rounded-full status-<?php echo $payment['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $payment['status'])); ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-sm"><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Revenue Chart
const months = <?php echo json_encode(array_column($monthly_data, 'month')); ?>;
const revenues = <?php echo json_encode(array_column($monthly_data, 'revenue')); ?>;

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Revenue (EGP)',
            data: revenues,
            borderColor: '#0d9488',
            backgroundColor: 'rgba(13, 148, 136, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
});

// Transaction Chart
const transactions = <?php echo json_encode(array_column($monthly_data, 'transactions')); ?>;

new Chart(document.getElementById('transactionChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Transactions',
            data: transactions,
            backgroundColor: '#14b8a6',
            borderRadius: 8
        }]
    },
    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>