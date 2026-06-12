<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();
requireUserType(['moderator', 'admin']);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE moderation_status = 'pending'");
$stmt->execute();
$pending_reports = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE status = 'pending'");
$stmt->execute();
$pending_claims = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM shops WHERE is_approved = 0");
$stmt->execute();
$pending_shops = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE status = 'flagged'");
$stmt->execute();
$flagged_content = $stmt->fetchColumn();

// Get recent reports needing moderation
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as user_name 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.moderation_status = 'pending' 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_reports = $stmt->fetchAll();

// Get recent claims
$stmt = $pdo->prepare("
    SELECT c.*, r.title as report_title, u.full_name as claimant_name
    FROM claims c
    JOIN reports r ON c.report_id = r.id
    JOIN users u ON c.claimant_user_id = u.id
    WHERE c.status = 'pending'
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_claims = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .moderator-gradient { background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); }
        .stat-card { transition: all 0.3s ease; background: white; border-radius: 16px; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-100">

<?php include 'includes/navbar.php'; ?>

<!-- Header -->
<div class="moderator-gradient text-white py-6">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Moderator Dashboard</h1>
                <p class="text-orange-100 text-sm mt-1">Review reports, manage claims, and moderate content</p>
            </div>
            <div class="flex gap-3">
                <a href="moderator_reports.php" class="bg-white/20 px-4 py-2 rounded-lg text-sm hover:bg-white/30 transition">
                    <i class="fas fa-file-alt mr-2"></i> Reports Queue
                </a>
                <a href="moderator_claims.php" class="bg-white/20 px-4 py-2 rounded-lg text-sm hover:bg-white/30 transition">
                    <i class="fas fa-gavel mr-2"></i> Claims Queue
                </a>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 py-8">
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="stat-card p-5 shadow-sm border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pending Reports</p>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo $pending_reports; ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-alt text-yellow-600 text-xl"></i>
                </div>
            </div>
            <a href="moderator_reports.php" class="text-sm text-orange-600 mt-2 inline-block hover:underline">Review →</a>
        </div>
        
        <div class="stat-card p-5 shadow-sm border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pending Claims</p>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $pending_claims; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-gavel text-blue-600 text-xl"></i>
                </div>
            </div>
            <a href="moderator_claims.php" class="text-sm text-orange-600 mt-2 inline-block hover:underline">Review →</a>
        </div>
        
        <div class="stat-card p-5 shadow-sm border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pending Shops</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $pending_shops; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-store text-green-600 text-xl"></i>
                </div>
            </div>
            <span class="text-sm text-gray-400 mt-2 inline-block">Admin approval only</span>
        </div>
        
        <div class="stat-card p-5 shadow-sm border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Flagged Content</p>
                    <p class="text-3xl font-bold text-red-600"><?php echo $flagged_content; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-flag text-red-600 text-xl"></i>
                </div>
            </div>
            <a href="moderator_reports.php?status=flagged" class="text-sm text-orange-600 mt-2 inline-block hover:underline">Investigate →</a>
        </div>
    </div>

    <!-- Recent Reports Queue -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">
                <i class="fas fa-file-alt text-orange-500 mr-2"></i> Reports Pending Review
            </h2>
            <a href="moderator_reports.php" class="text-sm text-orange-600 hover:underline">View all →</a>
        </div>
        <div class="divide-y">
            <?php if (empty($recent_reports)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-check-circle text-4xl mb-2 text-green-500"></i>
                    <p>No pending reports. All caught up!</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_reports as $report): ?>
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo $report['report_type'] === 'stolen' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($report['report_type']); ?>
                                </span>
                                <span class="text-xs text-gray-400">Report #<?php echo $report['id']; ?></span>
                            </div>
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($report['title']); ?></h3>
                            <p class="text-sm text-gray-500 mt-1">By: <?php echo htmlspecialchars($report['user_name']); ?></p>
                            <p class="text-sm text-gray-400"><?php echo truncateText($report['description'], 80); ?></p>
                        </div>
                        <a href="view_report.php?id=<?php echo $report['id']; ?>&moderate=1" 
                           class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-600 transition ml-4">
                            Review
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Claims Queue -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">
                <i class="fas fa-gavel text-orange-500 mr-2"></i> Claims Pending Review
            </h2>
            <a href="moderator_claims.php" class="text-sm text-orange-600 hover:underline">View all →</a>
        </div>
        <div class="divide-y">
            <?php if (empty($recent_claims)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-check-circle text-4xl mb-2 text-green-500"></i>
                    <p>No pending claims. All caught up!</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_claims as $claim): ?>
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                <span class="text-xs text-gray-400">Claim #<?php echo $claim['id']; ?></span>
                            </div>
                            <p class="font-medium text-gray-800">Claim on: <?php echo htmlspecialchars($claim['report_title']); ?></p>
                            <p class="text-sm text-gray-500">Claimant: <?php echo htmlspecialchars($claim['claimant_name']); ?></p>
                            <p class="text-xs text-gray-400">Submitted: <?php echo date('M d, Y', strtotime($claim['created_at'])); ?></p>
                        </div>
                        <a href="moderator_claims.php?id=<?php echo $claim['id']; ?>" 
                           class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-600 transition ml-4">
                            Review
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Tips -->
    <div class="mt-8 bg-amber-50 rounded-xl p-4 border border-amber-200">
        <div class="flex items-start gap-3">
            <i class="fas fa-lightbulb text-amber-600 text-xl mt-0.5"></i>
            <div>
                <h3 class="font-semibold text-amber-800 text-sm">Moderation Tips</h3>
                <p class="text-amber-700 text-xs mt-1">Always review evidence carefully before approving or rejecting claims. Flag suspicious content for admin review.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>