<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireUserType(['admin', 'finance']);

// Handle payment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $payment_id = (int)$_POST['payment_id'];
    $new_status = $_POST['payment_status'];
    $notes = $_POST['notes'] ?? '';
    
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $notes, $_SESSION['user_id'], $payment_id]);
    
    // If payment is completed, activate subscription or boost
    if ($new_status === 'completed') {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch();
        
        if ($payment['plan_type'] === 'basic' || $payment['plan_type'] === 'premium') {
            $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
            $stmt = $pdo->prepare("
                UPDATE shops SET 
                    subscription_plan = ?,
                    subscription_expires_at = ?,
                    verified_badge = 1,
                    verification_level = 'verified'
                WHERE user_id = ?
            ");
            $stmt->execute([$payment['plan_type'], $expiry_date, $payment['user_id']]);
        }
    }
    
    $_SESSION['success'] = "Payment status updated to " . ucfirst($new_status);
    header('Location: admin_finance.php');
    exit();
}

// Get all payments with filters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? '';

$sql = "
    SELECT p.*, u.full_name, u.email, u.user_type 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    WHERE 1=1
";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
}
if ($type_filter) {
    $sql .= " AND p.plan_type = ?";
    $params[] = $type_filter;
}

$sql .= " ORDER BY p.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN status = 'pending_review' THEN amount ELSE 0 END) as pending_revenue,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN status = 'pending_review' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_manual_count
    FROM payments
");
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Management - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f8f9fc; }
        .status-badge {
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-pending_review { background: #fed7aa; color: #9a3412; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-failed { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Financial Management</h1>
        <p class="text-gray-500 text-sm">Track payments, manage subscriptions, and review financial transactions</p>
        <a href="dashboard_finance.php" class="text-teal-600 hover:text-teal-700 text-sm mt-2 inline-block">← Back to Dashboard</a>
    </div>

    <!-- Financial Stats -->
    <div class="grid md:grid-cols-4 gap-5 mb-8">
        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl p-5 text-white">
            <p class="text-emerald-100 text-xs">Total Revenue</p>
            <p class="text-3xl font-bold"><?php echo number_format($stats['total_revenue'] ?? 0, 2); ?> EGP</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border">
            <p class="text-gray-500 text-xs">Pending Review</p>
            <p class="text-3xl font-bold text-amber-600"><?php echo number_format($stats['pending_revenue'] ?? 0, 2); ?> EGP</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border">
            <p class="text-gray-500 text-xs">Completed Transactions</p>
            <p class="text-3xl font-bold text-green-600"><?php echo number_format($stats['completed_count'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border">
            <p class="text-gray-500 text-xs">Pending Verification</p>
            <p class="text-3xl font-bold text-orange-600"><?php echo number_format($stats['pending_count'] ?? 0); ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending_review" <?php echo $status_filter === 'pending_review' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Type</label>
                <select name="type" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="">All Types</option>
                    <option value="basic" <?php echo $type_filter === 'basic' ? 'selected' : ''; ?>>Basic</option>
                    <option value="premium" <?php echo $type_filter === 'premium' ? 'selected' : ''; ?>>Premium</option>
                    <option value="boost" <?php echo $type_filter === 'boost' ? 'selected' : ''; ?>>Boost</option>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
                <a href="admin_finance.php" class="text-gray-500 hover:text-orange-600 text-sm ml-2">Clear</a>
            </div>
        </form>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 px-4 py-3 rounded mb-4 text-sm">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Payments Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border">
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
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($payments as $payment): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4 text-sm">#<?php echo $payment['id']; ?></td>
                        <td class="px-5 py-4">
                            <div class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($payment['full_name']); ?></div>
                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($payment['email']); ?></div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $payment['plan_type'] === 'premium' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'; ?>">
                                <?php echo ucfirst($payment['plan_type'] ?? 'Boost'); ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-sm font-semibold"><?php echo number_format($payment['amount'], 2); ?> EGP</td>
                        <td class="px-5 py-4 text-sm"><?php echo ucfirst($payment['payment_method'] ?? 'N/A'); ?></td>
                        <td class="px-5 py-4">
                            <span class="status-badge status-<?php echo $payment['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $payment['status'])); ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-sm"><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                        <td class="px-5 py-4">
                            <?php if ($payment['status'] === 'pending_review' || $payment['status'] === 'pending'): ?>
                                <button onclick="openReviewModal(<?php echo $payment['id']; ?>)" 
                                        class="bg-orange-500 text-white px-3 py-1 rounded text-xs hover:bg-orange-600">
                                    Review
                                </button>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl max-w-md w-full mx-4 overflow-hidden">
        <div class="orange-gradient px-5 py-4 text-white">
            <h3 class="font-bold">Review Payment</h3>
        </div>
        <form method="POST" class="p-5">
            <input type="hidden" name="payment_id" id="modal_payment_id">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
                <select name="payment_status" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="completed">Approve - Mark as Completed</option>
                    <option value="failed">Reject - Mark as Failed</option>
                </select>
            </div>
            
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes</label>
                <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Add notes about this transaction..."></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" name="update_payment" class="flex-1 bg-orange-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-orange-700">Submit</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg text-sm font-medium hover:bg-gray-300">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(paymentId) {
    document.getElementById('modal_payment_id').value = paymentId;
    document.getElementById('reviewModal').classList.remove('hidden');
    document.getElementById('reviewModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('reviewModal').classList.add('hidden');
    document.getElementById('reviewModal').classList.remove('flex');
}

// Make sure modal closes when clicking outside
document.getElementById('reviewModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>