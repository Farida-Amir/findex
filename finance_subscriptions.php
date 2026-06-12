<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireUserType(['finance', 'admin']);

// Get active subscriptions
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name, u.email 
    FROM shops s
    JOIN users u ON s.user_id = u.id
    WHERE s.subscription_plan IS NOT NULL 
    AND s.subscription_plan != ''
    ORDER BY s.subscription_expires_at ASC
");
$stmt->execute();
$subscriptions = $stmt->fetchAll();

// Get subscription stats
$stmt = $pdo->query("
    SELECT 
        subscription_plan,
        COUNT(*) as count,
        SUM(CASE WHEN subscription_expires_at > NOW() THEN 1 ELSE 0 END) as active_count
    FROM shops 
    WHERE subscription_plan IS NOT NULL AND subscription_plan != ''
    GROUP BY subscription_plan
");
$plan_stats = $stmt->fetchAll();

// Calculate MRR
$mrr = 0;
foreach ($subscriptions as $sub) {
    if ($sub['subscription_plan'] === 'basic') $mrr += 200;
    if ($sub['subscription_plan'] === 'premium') $mrr += 350;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscriptions - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .finance-gradient { background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'includes/navbar.php'; ?>

<!-- Header -->
<div class="finance-gradient text-white py-6">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Manage Subscriptions</h1>
                <p class="text-teal-100 text-sm mt-1">View and manage shop subscription plans</p>
            </div>
            <a href="dashboard_finance.php" class="bg-white/20 px-4 py-2 rounded-lg text-sm hover:bg-white/30 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <!-- Stats Cards -->
    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-teal-500">
            <p class="text-gray-500 text-xs">Monthly Recurring Revenue</p>
            <p class="text-2xl font-bold text-teal-600"><?php echo number_format($mrr, 2); ?> EGP</p>
        </div>
        <?php foreach ($plan_stats as $stat): ?>
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 
            <?php echo $stat['subscription_plan'] === 'premium' ? 'border-purple-500' : 'border-blue-500'; ?>">
            <p class="text-gray-500 text-xs uppercase"><?php echo ucfirst($stat['subscription_plan']); ?> Plan</p>
            <p class="text-2xl font-bold"><?php echo $stat['count']; ?></p>
            <p class="text-xs text-gray-400"><?php echo $stat['active_count']; ?> active</p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Subscriptions Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b bg-gray-50">
            <h3 class="font-semibold text-gray-800">
                <i class="fas fa-list mr-2 text-teal-500"></i> Active Subscriptions
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Shop</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Contact</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Plan</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Expires</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                    </td>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($subscriptions)): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-400">
                                <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                No active subscriptions found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subscriptions as $sub): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($sub['business_name']); ?></div>
                             </td>
                            <td class="px-5 py-3">
                                <div class="text-sm"><?php echo htmlspecialchars($sub['full_name']); ?></div>
                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($sub['email']); ?></div>
                             </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $sub['subscription_plan'] === 'premium' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo ucfirst($sub['subscription_plan']); ?>
                                </span>
                             </td>
                            <td class="px-5 py-3 text-sm">
                                <?php echo $sub['subscription_expires_at'] ? date('M d, Y', strtotime($sub['subscription_expires_at'])) : 'Never'; ?>
                             </td>
                            <td class="px-5 py-3">
                                <?php if ($sub['subscription_expires_at'] && strtotime($sub['subscription_expires_at']) < time()): ?>
                                    <span class="text-red-600 text-xs bg-red-50 px-2 py-1 rounded-full">Expired</span>
                                <?php else: ?>
                                    <span class="text-green-600 text-xs bg-green-50 px-2 py-1 rounded-full">Active</span>
                                <?php endif; ?>
                             </td>
                         </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
             </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>