<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireUserType(['finance', 'admin']);

$report_type = $_GET['type'] ?? 'monthly';
$date_from = $_GET['from'] ?? date('Y-m-01');
$date_to = $_GET['to'] ?? date('Y-m-t');

// Build query based on report type
if ($report_type === 'daily') {
    $sql = "
        SELECT 
            DATE(created_at) as period,
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount,
            SUM(CASE WHEN plan_type IN ('basic', 'premium') THEN amount ELSE 0 END) as subscription_amount,
            SUM(CASE WHEN plan_type = 'boost' THEN amount ELSE 0 END) as boost_amount
        FROM payments
        WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY period DESC
    ";
} else {
    $sql = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as period,
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount,
            SUM(CASE WHEN plan_type IN ('basic', 'premium') THEN amount ELSE 0 END) as subscription_amount,
            SUM(CASE WHEN plan_type = 'boost' THEN amount ELSE 0 END) as boost_amount
        FROM payments
        WHERE status = 'completed' AND created_at BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY period DESC
    ";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
$report_data = $stmt->fetchAll();

// Calculate totals
$total_revenue = array_sum(array_column($report_data, 'total_amount'));
$total_subscriptions = array_sum(array_column($report_data, 'subscription_amount'));
$total_boosts = array_sum(array_column($report_data, 'boost_amount'));
$total_transactions = array_sum(array_column($report_data, 'total_transactions'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Financial Reports</h1>
            <p class="text-gray-500 text-sm">Generate and export revenue reports</p>
        </div>
        <button onclick="window.print()" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-teal-700">
            <i class="fas fa-print mr-2"></i> Print Report
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Report Type</label>
                <select name="type" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    <option value="daily" <?php echo $report_type === 'daily' ? 'selected' : ''; ?>>Daily</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">From Date</label>
                <input type="date" name="from" value="<?php echo $date_from; ?>" class="border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">To Date</label>
                <input type="date" name="to" value="<?php echo $date_to; ?>" class="border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <button type="submit" class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm">Generate</button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-teal-500">
            <p class="text-gray-500 text-xs">Total Revenue</p>
            <p class="text-2xl font-bold text-teal-600"><?php echo number_format($total_revenue, 2); ?> EGP</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-purple-500">
            <p class="text-gray-500 text-xs">Subscriptions</p>
            <p class="text-2xl font-bold text-purple-600"><?php echo number_format($total_subscriptions, 2); ?> EGP</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-blue-500">
            <p class="text-gray-500 text-xs">Boosts</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo number_format($total_boosts, 2); ?> EGP</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-amber-500">
            <p class="text-gray-500 text-xs">Transactions</p>
            <p class="text-2xl font-bold text-amber-600"><?php echo number_format($total_transactions); ?></p>
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Period</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Transactions</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Subscriptions</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Boosts</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($report_data as $row): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-sm font-medium"><?php echo $row['period']; ?></td>
                        <td class="px-5 py-3 text-sm text-right"><?php echo number_format($row['total_transactions']); ?></td>
                        <td class="px-5 py-3 text-sm text-right"><?php echo number_format($row['subscription_amount'], 2); ?> EGP</td>
                        <td class="px-5 py-3 text-sm text-right"><?php echo number_format($row['boost_amount'], 2); ?> EGP</td>
                        <td class="px-5 py-3 text-sm text-right font-semibold"><?php echo number_format($row['total_amount'], 2); ?> EGP</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-bold">
                        <td class="px-5 py-3 text-sm">Total</td>
                        <td class="px-5 py-3 text-sm text-right"><?php echo number_format($total_transactions); ?></td>
                        <td class="px-5 py-3 text-sm text-right"><?php echo number_format($total_subscriptions, 2); ?> EGP</td>
                        <td class="px-5 py-3 text-sm text-right"><?php echo number_format($total_boosts, 2); ?> EGP</td>
                        <td class="px-5 py-3 text-sm text-right"><?php echo number_format($total_revenue, 2); ?> EGP</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>