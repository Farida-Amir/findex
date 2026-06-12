<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();
requireUserType(['admin']);

// Get payment statistics
$stmt = $pdo->query("
    SELECT 
        SUM(amount) as total_revenue,
        COUNT(*) as total_transactions,
        SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as completed_revenue,
        COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as completed_count
    FROM payments
");
$stats = $stmt->fetch();

// Get recent payments
$stmt = $pdo->prepare("
    SELECT p.*, u.email, u.full_name 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 50
");
$stmt->execute();
$payments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg border-b-4 border-orange-500">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-dollar-sign text-orange-600 text-2xl mr-2"></i>
                    <span class="font-bold text-xl">Payment Management</span>
                </div>
                <a href="admin.php" class="text-gray-600 hover:text-orange-600">← Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Stats -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Total Revenue</p>
                <p class="text-3xl font-bold text-green-600">$<?= number_format($stats['total_revenue'] ?? 0, 2) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Completed Revenue</p>
                <p class="text-3xl font-bold text-blue-600">$<?= number_format($stats['completed_revenue'] ?? 0, 2) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Total Transactions</p>
                <p class="text-3xl font-bold text-orange-600"><?= number_format($stats['total_transactions'] ?? 0) ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Completed Transactions</p>
                <p class="text-3xl font-bold text-purple-600"><?= number_format($stats['completed_count'] ?? 0) ?></p>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Transaction ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm">#<?= $payment['id'] ?></td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium"><?= htmlspecialchars($payment['full_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($payment['email']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold">$<?= number_format($payment['amount'], 2) ?></td>
                            <td class="px-6 py-4 text-sm"><?= ucfirst($payment['payment_method']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?= $payment['payment_status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($payment['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                    <?= ucfirst($payment['payment_status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono"><?= htmlspecialchars($payment['transaction_id'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4 text-sm"><?= date('M d, Y', strtotime($payment['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>