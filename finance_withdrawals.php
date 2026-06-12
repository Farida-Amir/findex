<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireUserType(['finance', 'admin']);

// Check if table exists, if not create a demo array
$withdrawals = [];
$pending = 0;
$total_pending = 0;
$total_paid = 0;

// Try to get from database if table exists
try {
    $stmt = $pdo->prepare("SELECT * FROM withdrawal_requests ORDER BY created_at DESC LIMIT 50");
    $stmt->execute();
    $withdrawals = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM withdrawal_requests WHERE status = 'pending'");
    $pending = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM withdrawal_requests WHERE status = 'pending'");
    $total_pending = $stmt->fetchColumn() ?? 0;
    
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM withdrawal_requests WHERE status = 'completed'");
    $total_paid = $stmt->fetchColumn() ?? 0;
} catch (Exception $e) {
    // Table doesn't exist, use demo data
    $withdrawals = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawals Management - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .finance-gradient { background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'includes/navbar.php'; ?>

<div class="finance-gradient text-white py-6">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Withdrawals Management</h1>
                <p class="text-teal-100 text-sm mt-1">Process and track withdrawal requests</p>
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
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-amber-500">
            <p class="text-gray-500 text-xs">Pending Withdrawals</p>
            <p class="text-2xl font-bold text-amber-600"><?php echo $pending; ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-orange-500">
            <p class="text-gray-500 text-xs">Pending Amount</p>
            <p class="text-2xl font-bold text-orange-600"><?php echo number_format($total_pending, 2); ?> EGP</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-green-500">
            <p class="text-gray-500 text-xs">Total Paid Out</p>
            <p class="text-2xl font-bold text-green-600"><?php echo number_format($total_paid, 2); ?> EGP</p>
        </div>
    </div>

    <!-- Withdrawals Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b bg-gray-50">
            <h3 class="font-semibold text-gray-800">
                <i class="fas fa-list mr-2 text-teal-500"></i> Withdrawal Requests
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ID</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Account Holder</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Bank</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($withdrawals)): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-400">
                                <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                No withdrawal requests found
                                <p class="text-xs mt-1">Withdrawal requests will appear here once shops submit them</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $wd): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-sm">#<?php echo $wd['id']; ?></td>
                            <td class="px-5 py-3 text-sm"><?php echo htmlspecialchars($wd['account_holder'] ?? 'N/A'); ?></td>
                            <td class="px-5 py-3 font-semibold"><?php echo number_format($wd['amount'], 2); ?> EGP</td>
                            <td class="px-5 py-3 text-sm"><?php echo htmlspecialchars($wd['bank_name'] ?? 'N/A'); ?></td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo $wd['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($wd['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                            ($wd['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')); ?>">
                                    <?php echo ucfirst($wd['status']); ?>
                                </span>
                             </td>
                            <td class="px-5 py-3 text-sm"><?php echo date('M d, Y', strtotime($wd['created_at'])); ?></td>
                            <td class="px-5 py-3">
                                <?php if ($wd['status'] === 'pending'): ?>
                                    <button onclick="alert('Withdrawal approved (demo) - In production this would process the payout.')" 
                                            class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600">Approve</button>
                                    <button onclick="alert('Withdrawal rejected (demo) - In production this would reject the payout.')" 
                                            class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600 ml-1">Reject</button>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">-</span>
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