<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();
requireUserType(['moderator', 'admin']);

// Helper function to truncate text
function truncateText($text, $length = 100) {
    if (empty($text)) {
        return '';
    }
    if (strlen($text) <= $length) {
        return htmlspecialchars($text);
    }
    return htmlspecialchars(substr($text, 0, $length)) . '...';
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get pending reviews count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE moderation_status = 'pending' OR moderation_status IS NULL");
$stmt->execute();
$pending_reports = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE status = 'pending'");
$stmt->execute();
$pending_claims = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM shops WHERE is_approved = FALSE");
$stmt->execute();
$pending_shops = $stmt->fetchColumn();

// Get recent reports needing moderation
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as user_name 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.moderation_status = 'pending' OR r.moderation_status IS NULL
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$reports_queue = $stmt->fetchAll();

// Get flagged content
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as user_name 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'flagged' 
    ORDER BY r.updated_at DESC 
    LIMIT 10
");
$stmt->execute();
$flagged_content = $stmt->fetchAll();

// Get total reports count (for stats)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports");
$stmt->execute();
$total_reports = $stmt->fetchColumn();

// Get resolved reports count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE status = 'resolved' OR moderation_status = 'approved'");
$stmt->execute();
$resolved_reports = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard - Findex Trial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
        }
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-gem text-purple-600 text-2xl mr-2"></i>
                    <span class="font-bold text-xl">FindeX Trial - Moderator Panel</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="moderator.php" class="text-purple-600 font-semibold">Dashboard</a>
                    <a href="moderator_reports.php" class="text-gray-600 hover:text-purple-600">Reports Queue</a>
                    <a href="moderator_claims.php" class="text-gray-600 hover:text-purple-600">Claims</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending Reports</p>
                        <p class="text-3xl font-bold text-yellow-600"><?= $pending_reports ?></p>
                    </div>
                    <i class="fas fa-file-alt text-3xl text-yellow-500"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending Claims</p>
                        <p class="text-3xl font-bold text-blue-600"><?= $pending_claims ?></p>
                    </div>
                    <i class="fas fa-gavel text-3xl text-blue-500"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending Shops</p>
                        <p class="text-3xl font-bold text-green-600"><?= $pending_shops ?></p>
                    </div>
                    <i class="fas fa-store text-3xl text-green-500"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Reports</p>
                        <p class="text-3xl font-bold text-purple-600"><?= $total_reports ?></p>
                    </div>
                    <i class="fas fa-chart-line text-3xl text-purple-500"></i>
                </div>
            </div>
        </div>

        <!-- Moderator Stats Row -->
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg shadow p-4 mb-8">
            <div class="flex flex-wrap justify-between items-center">
                <div class="flex items-center gap-3">
                    <i class="fas fa-user-shield text-purple-600 text-2xl"></i>
                    <div>
                        <p class="text-sm text-gray-600">Welcome back,</p>
                        <p class="font-bold text-gray-800"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Moderator') ?></p>
                    </div>
                </div>
                <div class="flex gap-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600"><?= $resolved_reports ?></p>
                        <p class="text-xs text-gray-500">Resolved Reports</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-purple-600"><?= date('M d, Y') ?></p>
                        <p class="text-xs text-gray-500">Today's Date</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Reports Queue -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">Reports Pending Review</h2>
                    <p class="text-sm text-gray-500">Reports waiting for moderation</p>
                </div>
                <div class="divide-y">
                    <?php if (count($reports_queue) > 0): ?>
                        <?php foreach ($reports_queue as $report): ?>
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i> Pending
                                        </span>
                                        <span class="text-sm text-gray-500">Report #<?= $report['id'] ?></span>
                                    </div>
                                    <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($report['title']) ?></h3>
                                    <p class="text-sm text-gray-600">By: <?= htmlspecialchars($report['user_name']) ?></p>
                                    <p class="text-sm text-gray-500 mt-1"><?= truncateText($report['description'], 80) ?></p>
                                    <p class="text-xs text-gray-400 mt-1">Posted: <?= date('M d, Y', strtotime($report['created_at'])) ?></p>
                                </div>
                                <div class="flex space-x-2 ml-4">
                                    <a href="view_report.php?id=<?= $report['id'] ?>&moderate=1" 
                                       class="bg-purple-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-purple-700 transition">
                                        <i class="fas fa-eye mr-1"></i> Review
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-8 text-center text-gray-500">
                            <i class="fas fa-check-circle text-4xl mb-2"></i>
                            <p>All caught up! No pending reports.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-4 border-t bg-gray-50">
                    <a href="moderator_reports.php" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                        View all pending reports <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            <!-- Flagged Content -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">Flagged Content</h2>
                    <p class="text-sm text-gray-500">Content reported by users</p>
                </div>
                <div class="divide-y">
                    <?php if (count($flagged_content) > 0): ?>
                        <?php foreach ($flagged_content as $report): ?>
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                            <i class="fas fa-flag mr-1"></i> Flagged
                                        </span>
                                        <span class="text-sm text-gray-500">Report #<?= $report['id'] ?></span>
                                    </div>
                                    <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($report['title']) ?></h3>
                                    <p class="text-sm text-gray-600">By: <?= htmlspecialchars($report['user_name']) ?></p>
                                    <p class="text-sm text-gray-500 mt-1"><?= truncateText($report['description'], 80) ?></p>
                                </div>
                                <div class="ml-4">
                                    <a href="view_report.php?id=<?= $report['id'] ?>&moderate=1" 
                                       class="text-red-600 hover:text-red-700 text-sm font-medium">
                                        Investigate <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-8 text-center text-gray-500">
                            <i class="fas fa-shield-alt text-4xl mb-2"></i>
                            <p>No flagged content.</p>
                            <p class="text-xs mt-1">All content is clean</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions - FIXED -->
        <div class="mt-8 bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Quick Actions</h2>
                <p class="text-sm text-gray-500">Common moderation tasks</p>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Review Reports -->
                <a href="moderator_reports.php" class="bg-blue-50 text-blue-700 p-4 rounded-lg text-center hover:bg-blue-100 transition group">
                    <i class="fas fa-file-alt text-2xl mb-2 block"></i>
                    <span class="text-sm font-medium">Review Reports</span>
                    <?php if ($pending_reports > 0): ?>
                        <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-red-500 text-white rounded-full"><?= $pending_reports ?></span>
                    <?php endif; ?>
                </a>
                
                <!-- Review Claims -->
                <a href="moderator_claims.php" class="bg-green-50 text-green-700 p-4 rounded-lg text-center hover:bg-green-100 transition">
                    <i class="fas fa-gavel text-2xl mb-2 block"></i>
                    <span class="text-sm font-medium">Review Claims</span>
                    <?php if ($pending_claims > 0): ?>
                        <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-red-500 text-white rounded-full"><?= $pending_claims ?></span>
                    <?php endif; ?>
                </a>
                
                <!-- Verify Shops - Shows tooltip and only for admins or disabled for moderators -->
                <?php if ($user_type === 'admin'): ?>
                    <a href="admin_shops.php" class="bg-orange-50 text-orange-700 p-4 rounded-lg text-center hover:bg-orange-100 transition">
                        <i class="fas fa-store text-2xl mb-2 block"></i>
                        <span class="text-sm font-medium">Verify Shops</span>
                        <?php if ($pending_shops > 0): ?>
                            <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-red-500 text-white rounded-full"><?= $pending_shops ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <div class="tooltip bg-gray-100 text-gray-400 p-4 rounded-lg text-center cursor-not-allowed">
                        <i class="fas fa-store text-2xl mb-2 block"></i>
                        <span class="text-sm font-medium">Verify Shops</span>
                        <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-gray-300 text-gray-600 rounded-full">Admin Only</span>
                        <div class="tooltip-text">Only Administrators can verify shops. Contact an admin for shop approvals.</div>
                    </div>
                <?php endif; ?>
                
                <!-- My Profile - Works now -->
                <a href="moderator_profile.php" class="bg-gray-50 text-gray-700 p-4 rounded-lg text-center hover:bg-gray-100 transition">
                    <i class="fas fa-user-cog text-2xl mb-2 block"></i>
                    <span class="text-sm font-medium">My Profile</span>
                </a>
            </div>
        </div>
        
        <!-- Recent Activity Tips -->
        <div class="mt-6 bg-white rounded-lg shadow p-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-lightbulb text-yellow-500 text-xl mt-0.5"></i>
                <div class="text-sm text-gray-600">
                    <p class="font-semibold text-gray-800">Moderator Tips</p>
                    <ul class="mt-1 space-y-1 text-xs">
                        <li>• Always review flagged content within 24 hours</li>
                        <li>• For disputed claims, request additional documentation</li>
                        <li>• Use the bulk actions feature to process multiple reports efficiently</li>
                        <li>• Contact admin for shop verification requests</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>