<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();
requireUserType(['admin']);

// Handle user actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $user_id = (int)$_GET['id'];
    
    if ($action === 'suspend') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'User suspended successfully.';
    } elseif ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'User activated successfully.';
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type != 'admin'");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'User deleted successfully.';
    }
    header('Location: admin_users.php');
    exit();
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get users
$stmt = $pdo->prepare("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$users = $stmt->fetchAll();

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-gem text-purple-600 text-2xl mr-2"></i>
                    <span class="font-bold text-xl">Admin Panel - Users</span>
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

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">#<?= $user['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($user['full_name']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm"><?= htmlspecialchars($user['email']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $user['user_type'] === 'admin' ? 'bg-purple-100 text-purple-800' : 
                                       ($user['user_type'] === 'shop' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                    <?= ucfirst($user['user_type']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="profile.php?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900">View</a>
                                    <?php if ($user['status'] === 'active'): ?>
                                    <a href="?action=suspend&id=<?= $user['id'] ?>" onclick="return confirm('Suspend this user?')" class="text-yellow-600 hover:text-yellow-900">Suspend</a>
                                    <?php else: ?>
                                    <a href="?action=activate&id=<?= $user['id'] ?>" class="text-green-600 hover:text-green-900">Activate</a>
                                    <?php endif; ?>
                                    <?php if ($user['user_type'] !== 'admin'): ?>
                                    <a href="?action=delete&id=<?= $user['id'] ?>" onclick="return confirm('Delete this user permanently?')" class="text-red-600 hover:text-red-900">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-8 space-x-2">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" 
               class="px-3 py-1 rounded <?= $i == $page ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>