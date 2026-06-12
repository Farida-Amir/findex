<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);

// Get shop data
$stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->execute([$user_id]);
$shop = $stmt->fetch();

// Initialize variables with default values
$total_reports = 0;
$active_reports = 0;
$total_views = 0;
$pending_claims = 0;

// Get total reports count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reports WHERE user_id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
if ($result) {
    $total_reports = $result['total'];
}

// Get active reports count
$stmt = $pdo->prepare("SELECT COUNT(*) as active FROM reports WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
if ($result) {
    $active_reports = $result['active'];
}

// Get total views
$stmt = $pdo->prepare("SELECT SUM(views_count) as total_views FROM reports WHERE user_id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
if ($result && $result['total_views'] !== null) {
    $total_views = $result['total_views'];
}

// Get pending claims on shop's reports
$stmt = $pdo->prepare("
    SELECT COUNT(*) as pending 
    FROM claims c 
    JOIN reports r ON c.report_id = r.id 
    WHERE r.user_id = ? AND c.status = 'pending'
");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
if ($result) {
    $pending_claims = $result['pending'];
}

// Get recent reports
$stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_reports = $stmt->fetchAll();

// Get recent claims on shop items - FIXED: using created_at instead of submitted_at
$stmt = $pdo->prepare("
    SELECT c.*, r.title as report_title, u.full_name as claimant_name, c.created_at as claim_date
    FROM claims c 
    JOIN reports r ON c.report_id = r.id 
    JOIN users u ON c.claimant_user_id = u.id 
    WHERE r.user_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_claims = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Findex - Shop Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .gold-gradient { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        body { background: #f5f5f0; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; transition: all 0.3s ease; border: 1px solid #eef2ff; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .dashboard-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-6 md:py-8">
    
    <!-- Welcome Banner -->
    <div class="orange-gradient rounded-2xl text-white p-6 md:p-8 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl md:text-3xl font-bold">Welcome, <?php echo htmlspecialchars($shop['business_name'] ?? $_SESSION['user_name']); ?>!</h1>
                    <?php if ($shop && $shop['verified_badge']): ?>
                        <span class="bg-white/20 rounded-full px-3 py-1 text-sm"><i class="fas fa-check-circle mr-1"></i> Verified Shop</span>
                    <?php endif; ?>
                </div>
                <p class="text-orange-100">Manage your reports, respond to claims, and track your shop's performance across Egypt.</p>
            </div>
            <a href="report_item.php" class="bg-white text-orange-600 px-6 py-2 rounded-full font-semibold shadow-md hover:shadow-lg transition whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i> New Report
            </a>
        </div>
    </div>

    <!-- Verified Badge Status Card -->
    <?php
    $is_verified = ($shop && $shop['verified_badge'] == 1);
    $is_approved = ($shop && $shop['is_approved'] == 1);
    ?>
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border-l-4 <?php echo $is_verified ? 'border-green-500' : ($is_approved ? 'border-yellow-500' : 'border-red-500'); ?>">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <?php if ($is_verified): ?>
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Verified Shop</p>
                        <p class="text-xs text-gray-500">Your business is verified. Customers trust you more!</p>
                    </div>
                <?php elseif ($is_approved): ?>
                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Pending Verification</p>
                        <p class="text-xs text-gray-500">Your documents are being reviewed. This usually takes 2-3 days.</p>
                    </div>
                <?php else: ?>
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Not Verified Yet</p>
                        <p class="text-xs text-gray-500">Complete your shop verification to get the trust badge.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$is_verified): ?>
                <a href="shop_verification.php" class="orange-gradient text-white px-4 py-2 rounded-full text-sm font-semibold hover:shadow-md transition">
                    <i class="fas fa-shield-alt mr-1"></i> Complete Verification
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
        <div class="stat-card">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">Total Reports</p>
                    <?php if ($total_reports > 0): ?>
                        <div class="text-3xl font-bold text-orange-600"><?php echo number_format($total_reports); ?></div>
                    <?php else: ?>
                        <div class="text-xs text-gray-400 font-normal">No reports yet</div>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-alt text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">Active Reports</p>
                    <?php if ($active_reports > 0): ?>
                        <div class="text-3xl font-bold text-green-600"><?php echo number_format($active_reports); ?></div>
                    <?php else: ?>
                        <div class="text-xs text-gray-400 font-normal">No active reports</div>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">Total Views</p>
                    <?php if ($total_views > 0): ?>
                        <div class="text-3xl font-bold text-blue-600"><?php echo number_format($total_views); ?></div>
                    <?php else: ?>
                        <div class="text-xs text-gray-400 font-normal">No views yet</div>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-eye text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">Pending Claims</p>
                    <?php if ($pending_claims > 0): ?>
                        <div class="text-3xl font-bold text-amber-600"><?php echo number_format($pending_claims); ?></div>
                    <?php else: ?>
                        <div class="text-xs text-gray-400 font-normal">No pending claims</div>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-gavel text-amber-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid lg:grid-cols-2 gap-6 md:gap-8">
        
        <!-- Recent Reports Section -->
        <div class="dashboard-card">
            <div class="p-5 md:p-6 border-b bg-gray-50">
                <h2 class="text-lg md:text-xl font-bold flex items-center">
                    <i class="fas fa-file-alt text-orange-500 mr-2"></i> Recent Reports
                </h2>
            </div>
            <div class="divide-y">
                <?php if (empty($recent_reports)): ?>
                    <div class="p-8 md:p-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-2"></i>
                        <p class="text-xs">No reports yet</p>
                        <a href="report_item.php" class="inline-block mt-3 text-orange-600 text-xs">Create your first report →</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_reports as $report): ?>
                        <div class="p-4 md:p-5 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $report['report_type'] === 'stolen' ? 'bg-red-100 text-red-800' : ($report['report_type'] === 'lost' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                            <?php echo ucfirst($report['report_type']); ?>
                                        </span>
                                        <span class="text-xs text-gray-400"><?php echo number_format($report['views_count']); ?> views</span>
                                    </div>
                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($report['title']); ?></h3>
                                    <p class="text-gray-500 text-xs mt-1"><?php echo date('M d, Y', strtotime($report['created_at'])); ?></p>
                                </div>
                                <a href="view_report.php?id=<?php echo $report['id']; ?>" class="text-orange-600 hover:text-orange-700 text-sm ml-3">
                                    View <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="p-4 bg-gray-50 text-center">
                        <a href="shop_reports.php" class="text-orange-600 text-sm hover:underline">View all reports →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Claims on Shop Items -->
        <div class="dashboard-card">
            <div class="p-5 md:p-6 border-b bg-gray-50">
                <h2 class="text-lg md:text-xl font-bold flex items-center">
                    <i class="fas fa-gavel text-amber-500 mr-2"></i> Claims on Your Items
                </h2>
            </div>
            <div class="divide-y">
                <?php if (empty($recent_claims)): ?>
                    <div class="p-8 md:p-12 text-center text-gray-500">
                        <i class="fas fa-hand-peace text-3xl mb-2"></i>
                        <p class="text-xs">No pending claims</p>
                        <p class="text-xs text-gray-400 mt-1">Great! No one has claimed your items yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_claims as $claim): ?>
                        <div class="p-4 md:p-5 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                            <?php echo ucfirst($claim['status']); ?>
                                        </span>
                                    </div>
                                    <p class="font-medium text-gray-800 text-sm">Claim on: <?php echo htmlspecialchars($claim['report_title']); ?></p>
                                    <p class="text-gray-500 text-xs mt-1">By: <?php echo htmlspecialchars($claim['claimant_name']); ?></p>
                                    <p class="text-gray-500 text-xs">Submitted: <?php echo date('M d, Y', strtotime($claim['claim_date'] ?? $claim['created_at'])); ?></p>
                                </div>
                                <a href="view_claim.php?id=<?php echo $claim['id']; ?>" class="orange-gradient text-white px-3 py-1 rounded-full text-xs font-semibold">
                                    Review
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Shop Specific Quick Actions -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 md:mt-8">
        <a href="shop_analytics.php" class="bg-white rounded-xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-10 h-10 gold-gradient rounded-full flex items-center justify-center"><i class="fas fa-chart-line text-white"></i></div>
            <div><h3 class="font-semibold text-sm">Analytics</h3><p class="text-xs text-gray-500">View shop performance</p></div>
        </a>
        <a href="shop_settings.php" class="bg-white rounded-xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center"><i class="fas fa-store text-gray-600"></i></div>
            <div><h3 class="font-semibold text-sm">Shop Settings</h3><p class="text-xs text-gray-500">Manage your business</p></div>
        </a>
        <a href="shop_verification.php" class="bg-white rounded-xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-check-circle text-blue-600"></i></div>
            <div><h3 class="font-semibold text-sm">Verification</h3><p class="text-xs text-gray-500">Get verified badge</p></div>
        </a>
        <a href="boost_report.php" class="bg-white rounded-xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center"><i class="fas fa-rocket text-purple-600"></i></div>
            <div><h3 class="font-semibold text-sm">Boost Report</h3><p class="text-xs text-gray-500">Increase visibility</p></div>
        </a>
    </div>

    <!-- Shop Tips -->
    <div class="mt-6 md:mt-8 bg-white rounded-2xl p-5 md:p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-lg mb-3 flex items-center"><i class="fas fa-lightbulb text-yellow-500 mr-2"></i> Shop Tips</h3>
        <div class="grid md:grid-cols-3 gap-4 text-sm text-gray-600">
            <div class="flex items-start gap-2"><i class="fas fa-check-circle text-green-500 mt-0.5"></i> Verify your shop to get the trust badge</div>
            <div class="flex items-start gap-2"><i class="fas fa-chart-line text-orange-500 mt-0.5"></i> Boost important reports for more visibility</div>
            <div class="flex items-start gap-2"><i class="fas fa-bell text-purple-500 mt-0.5"></i> Respond quickly to customer claims</div>
        </div>
    </div>
</div>

<!-- AI Assistant Widget -->
<?php include 'includes/ai_assistant.php'; ?>


<?php include 'includes/footer.php'; ?>
</body>
</html>