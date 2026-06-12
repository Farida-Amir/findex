<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();
requireUserType(['admin']);

// Helper function for status badges
function getStatusBadge($status) {
    switch ($status) {
        case 'active':
            return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-circle text-green-500 mr-1" style="font-size: 8px;"></i> Active</span>';
        case 'suspended':
            return '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-circle text-red-500 mr-1" style="font-size: 8px;"></i> Suspended</span>';
        case 'pending':
            return '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-circle text-yellow-500 mr-1" style="font-size: 8px;"></i> Pending</span>';
        case 'verification_required':
            return '<span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800"><i class="fas fa-circle text-orange-500 mr-1" style="font-size: 8px;"></i> Verification Required</span>';
        default:
            return '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Unknown</span>';
    }
}

// Helper function for safe date formatting
function safeDate($date) {
    if (empty($date) || $date === null) {
        return 'Not approved yet';
    }
    return date('M d, Y', strtotime($date));
}

// Handle shop approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shop_id'])) {
    $shop_id = (int)$_POST['shop_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("
            UPDATE shops SET is_approved = TRUE, approved_by = ?, approved_at = NOW(), approval_notes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $notes, $shop_id]);
        
        // Update user status
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = (SELECT user_id FROM shops WHERE id = ?)");
        $stmt->execute([$shop_id]);
        
        $_SESSION['success'] = 'Shop approved successfully.';
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE shops SET approval_notes = ? WHERE id = ?");
        $stmt->execute([$notes, $shop_id]);
        $_SESSION['success'] = 'Shop application rejected.';
    }
    header('Location: admin_shops.php');
    exit();
}

// Get pending shops
$stmt = $pdo->prepare("
    SELECT s.*, u.email, u.full_name, u.created_at as registered_at 
    FROM shops s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.is_approved = FALSE 
    ORDER BY s.created_at ASC
");
$stmt->execute();
$pending_shops = $stmt->fetchAll();

// Get approved shops
$stmt = $pdo->prepare("
    SELECT s.*, u.email, u.full_name, u.status 
    FROM shops s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.is_approved = TRUE 
    ORDER BY s.approved_at DESC 
    LIMIT 20
");
$stmt->execute();
$approved_shops = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shops - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-store text-purple-600 text-2xl mr-2"></i>
                    <span class="font-bold text-xl">Shop Verification</span>
                </div>
                <a href="admin.php" class="text-gray-600 hover:text-purple-600">← Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <!-- Pending Shops -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Pending Shop Verifications</h2>
                <p class="text-gray-500 text-sm">Review and approve shop registrations</p>
            </div>
            <div class="divide-y">
                <?php foreach ($pending_shops as $shop): ?>
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <i class="fas fa-store text-2xl text-gray-400"></i>
                                <h3 class="text-lg font-semibold"><?= htmlspecialchars($shop['business_name']) ?></h3>
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending Review</span>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <p class="text-sm text-gray-600"><strong>Owner:</strong> <?= htmlspecialchars($shop['full_name']) ?></p>
                                    <p class="text-sm text-gray-600"><strong>Email:</strong> <?= htmlspecialchars($shop['email']) ?></p>
                                    <p class="text-sm text-gray-600"><strong>Tax ID:</strong> <?= htmlspecialchars($shop['tax_id'] ?? 'Not provided') ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600"><strong>Address:</strong> <?= htmlspecialchars($shop['address'] ?? 'Not provided') ?></p>
                                    <p class="text-sm text-gray-600"><strong>Website:</strong> <?= $shop['website'] ? '<a href="' . htmlspecialchars($shop['website']) . '" target="_blank" class="text-purple-600">' . htmlspecialchars($shop['website']) . '</a>' : 'Not provided' ?></p>
                                    <p class="text-sm text-gray-600"><strong>Registered:</strong> <?= safeDate($shop['registered_at']) ?></p>
                                </div>
                            </div>
                            
                            <?php if ($shop['business_license']): ?>
                            <div class="mt-3">
                                <a href="<?= htmlspecialchars($shop['business_license']) ?>" target="_blank" class="text-purple-600 text-sm hover:underline">
                                    <i class="fas fa-file-pdf mr-1"></i> View Business License
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ml-6">
                            <form method="POST" class="space-y-2">
                                <input type="hidden" name="shop_id" value="<?= $shop['id'] ?>">
                                <textarea name="notes" rows="2" class="w-64 border rounded-lg p-2 text-sm" placeholder="Approval notes (optional)"></textarea>
                                <div class="flex space-x-2">
                                    <button type="submit" name="action" value="approve" 
                                            class="bg-green-500 text-white px-4 py-1 rounded hover:bg-green-600 text-sm">
                                        <i class="fas fa-check mr-1"></i> Approve
                                    </button>
                                    <button type="submit" name="action" value="reject" 
                                            class="bg-red-500 text-white px-4 py-1 rounded hover:bg-red-600 text-sm">
                                        <i class="fas fa-times mr-1"></i> Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($pending_shops)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-check-circle text-4xl mb-2"></i>
                    <p>No pending shop verifications.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approved Shops -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Verified Shops</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Business Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Subscription</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Approved</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($approved_shops as $shop): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($shop['business_name']) ?></div>
                                <?php if ($shop['verified_badge']): ?>
                                <span class="text-xs text-blue-600"><i class="fas fa-check-circle"></i> Verified</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($shop['full_name']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                    <?= ucfirst($shop['subscription_type'] ?? 'free') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm"><?= safeDate($shop['approved_at'] ?? null) ?></td>
                            <td class="px-6 py-4">
                                <?= getStatusBadge($shop['status'] ?? 'pending') ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="profile.php?id=<?= $shop['user_id'] ?>" class="text-purple-600 hover:text-purple-900">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>