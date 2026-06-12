<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);

// Initialize variables
$total_reports = 0;
$active_reports = 0;
$pending_claims = 0;
$notif_count = 0;
$active_boosts = 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reports WHERE user_id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
if ($result) $total_reports = $result['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as active FROM reports WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
if ($result) $active_reports = $result['active'];

$stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM claims WHERE claimant_user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
if ($result) $pending_claims = $result['pending'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$notif_count = $stmt->fetchColumn();

// Get active boosts count
$stmt = $pdo->prepare("SELECT COUNT(*) as active_boosts FROM reports WHERE user_id = ? AND is_boosted = 1 AND (boost_expires IS NULL OR boost_expires > NOW())");
$stmt->execute([$user_id]);
$boost_result = $stmt->fetch();
if ($boost_result) $active_boosts = $boost_result['active_boosts'];

// Include boost fields in recent reports query
$stmt = $pdo->prepare("SELECT *, is_boosted, boost_package, boost_expires FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_reports = $stmt->fetchAll();


$stmt = $pdo->prepare("
    SELECT c.*, r.title as report_title, c.created_at as claim_date
    FROM claims c 
    JOIN reports r ON c.report_id = r.id 
    WHERE c.claimant_user_id = ? 
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
    <title>Findex - My Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f5f5f0; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; transition: all 0.3s ease; border: 1px solid #eef2ff; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .dashboard-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .claim-status-badge { transition: all 0.2s ease; }
        .view-details-btn { opacity: 0.7; transition: opacity 0.2s ease; }
        .claim-item:hover .view-details-btn { opacity: 1; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-6 md:py-8">
    
    <div class="orange-gradient rounded-2xl text-white p-6 md:p-8 mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <p class="text-orange-100 mt-2">Track your reports, manage claims, and recover your precious items.</p>
            </div>
            <a href="report_item.php" class="bg-white text-orange-600 px-6 py-2 rounded-full font-semibold shadow-md hover:shadow-lg transition whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i> New Report
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 md:gap-6 mb-8">
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
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center"><i class="fas fa-file-alt text-orange-600 text-xl"></i></div>
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
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-chart-line text-green-600 text-xl"></i></div>
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
                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center"><i class="fas fa-gavel text-amber-600 text-xl"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">Notifications</p>
                    <?php if ($notif_count > 0): ?>
                        <div class="text-3xl font-bold text-purple-600"><?php echo number_format($notif_count); ?></div>
                    <?php else: ?>
                        <div class="text-xs text-gray-400 font-normal">No new alerts</div>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center"><i class="fas fa-bell text-purple-600 text-xl"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wide mb-1">Active Boosts</p>
                    <?php if ($active_boosts > 0): ?>
                        <div class="text-3xl font-bold text-orange-600"><?php echo number_format($active_boosts); ?></div>
                        <div class="text-xs text-gray-500 mt-1">Boosted reports</div>
                    <?php else: ?>
                        <div class="text-xs text-gray-400 font-normal">No active boosts</div>
                        <a href="boost_report.php" class="text-xs text-orange-500 hover:underline mt-1 inline-block">Boost a report →</a>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center"><i class="fas fa-rocket text-orange-600 text-xl"></i></div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 md:gap-8">
        <div class="dashboard-card">
            <div class="p-5 md:p-6 border-b bg-gray-50">
                <h2 class="text-lg md:text-xl font-bold flex items-center"><i class="fas fa-file-alt text-orange-500 mr-2"></i> Recent Reports</h2>
            </div>
            <div class="divide-y">
                <?php if (empty($recent_reports)): ?>
                    <div class="p-8 md:p-12 text-center text-gray-500"><i class="fas fa-inbox text-3xl mb-2"></i><p class="text-xs">No reports yet</p><a href="report_item.php" class="inline-block mt-3 text-orange-600 text-xs">Create your first report →</a></div>
                <?php else: ?>
                    <?php foreach ($recent_reports as $report): ?>
                        <div class="p-4 md:p-5 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $report['report_type'] === 'stolen' ? 'bg-red-100 text-red-800' : ($report['report_type'] === 'lost' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                            <?php echo ucfirst($report['report_type']); ?>
                                        </span>
                                        <span class="text-xs text-gray-400"><?php echo date('M d, Y', strtotime($report['created_at'])); ?></span>
                                        <?php if (isset($report['is_boosted']) && $report['is_boosted'] == 1): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-700">
                                                <i class="fas fa-rocket text-xs"></i> Boosted
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($report['title']); ?></h3>
                                    <p class="text-gray-500 text-xs mt-1"><?php echo truncateText($report['description'], 80); ?></p>
                                </div>
                                <div class="flex flex-col items-end gap-2 ml-3">
                                    <?php if (!isset($report['is_boosted']) || !$report['is_boosted']): ?>
                                        <a href="boost_report.php?report_id=<?php echo $report['id']; ?>" 
                                           class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1 whitespace-nowrap">
                                            <i class="fas fa-rocket text-xs"></i> Boost
                                        </a>
                                    <?php endif; ?>
                                    <a href="view_report.php?id=<?php echo $report['id']; ?>" class="text-orange-600 hover:text-orange-700 text-sm">View <i class="fas fa-arrow-right ml-1"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="p-4 bg-gray-50 text-center"><a href="my_reports.php" class="text-orange-600 text-sm hover:underline">View all reports →</a></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="p-5 md:p-6 border-b bg-gray-50">
                <h2 class="text-lg md:text-xl font-bold flex items-center"><i class="fas fa-gavel text-orange-500 mr-2"></i> Recent Claims</h2>
            </div>
            <div class="divide-y">
                <?php if (empty($recent_claims)): ?>
                    <div class="p-8 md:p-12 text-center text-gray-500">
                        <i class="fas fa-hand-peace text-3xl mb-2"></i>
                        <p class="text-xs">No claims filed yet</p>
                        <p class="text-xs text-gray-400 mt-1">Found an item? Submit a claim!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_claims as $claim): ?>
                        <div class="claim-item p-4 md:p-5 hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='view_claim.php?id=<?php echo $claim['id']; ?>'">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                                        <span class="claim-status-badge px-2 py-1 text-xs rounded-full <?php echo $claim['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($claim['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst($claim['status']); ?>
                                        </span>
                                        <?php if ($claim['status'] === 'pending'): ?>
                                            <span class="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded-full">
                                                <i class="fas fa-clock mr-1"></i> Under Review
                                            </span>
                                        <?php elseif ($claim['status'] === 'approved'): ?>
                                            <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">
                                                <i class="fas fa-check-circle mr-1"></i> Ready for Pickup
                                            </span>
                                        <?php elseif ($claim['status'] === 'rejected'): ?>
                                            <span class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded-full">
                                                <i class="fas fa-times-circle mr-1"></i> Need Action
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($claim['report_title']); ?></p>
                                    <div class="flex items-center gap-3 mt-2">
                                        <p class="text-gray-500 text-xs">
                                            <i class="far fa-calendar-alt mr-1"></i> 
                                            Submitted: <?php echo date('M d, Y', strtotime($claim['claim_date'] ?? $claim['created_at'])); ?>
                                        </p>
                                        <?php 
                                        $days_pending = floor((time() - strtotime($claim['created_at'])) / (60 * 60 * 24));
                                        if ($claim['status'] === 'pending' && $days_pending <= 7):
                                        ?>
                                            <p class="text-xs text-blue-600">
                                                <i class="fas fa-hourglass-half mr-1"></i> 
                                                <?php echo $days_pending; ?> day(s) pending
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="view-details-btn text-orange-600 text-sm ml-3 flex items-center gap-1">
                                    <span class="hidden sm:inline">View details</span>
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mt-6 md:mt-8">
        <a href="report_item.php" class="bg-white rounded-xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-10 h-10 orange-gradient rounded-full flex items-center justify-center"><i class="fas fa-plus text-white"></i></div>
            <div><h3 class="font-semibold text-sm">New Report</h3><p class="text-xs text-gray-500">Report lost or stolen item</p></div>
        </a>
        <a href="my_reports.php" class="bg-white rounded-xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-list text-blue-600"></i></div>
            <div><h3 class="font-semibold text-sm">My Reports</h3><p class="text-xs text-gray-500">View all your reports</p></div>
        </a>
        <a href="boost_report.php" class="bg-white rounded-xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center"><i class="fas fa-rocket text-orange-600"></i></div>
            <div><h3 class="font-semibold text-sm">Boost Report</h3><p class="text-xs text-gray-500">Get more visibility</p></div>
        </a>
        <a href="search.php" class="bg-white rounded-xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-search text-green-600"></i></div>
            <div><h3 class="font-semibold text-sm">Search</h3><p class="text-xs text-gray-500">Find lost jewelry</p></div>
        </a>
        <a href="edit_profile.php" class="bg-white rounded-xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition border border-gray-100">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center"><i class="fas fa-user text-purple-600"></i></div>
            <div><h3 class="font-semibold text-sm">Profile</h3><p class="text-xs text-gray-500">Update your info</p></div>
        </a>
    </div>

    <div class="mt-6 md:mt-8 orange-gradient rounded-2xl p-5 md:p-6 text-white">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-center md:text-left">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center"><i class="fas fa-robot text-2xl"></i></div>
                <div><h3 class="text-lg md:text-xl font-bold">Need AI Assistance?</h3><p class="text-orange-100 text-sm">Let our AI help enhance images and analyze your case</p></div>
            </div>
            <a href="ai_assist.php" class="bg-white text-orange-600 px-6 py-2 rounded-full font-semibold hover:shadow-lg transition whitespace-nowrap">Try AI Assistant →</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>