<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireUserType(['admin']);

// Get system statistics
$stats = [];

// User statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'shop'");
$stats['total_shops'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'user'");
$stats['total_individuals'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()");
$stats['new_today'] = $stmt->fetchColumn();

// Report statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reports");
$stats['total_reports'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM reports WHERE moderation_status = 'pending'");
$stats['pending_reports'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM reports WHERE DATE(created_at) = CURDATE()");
$stats['reports_today'] = $stmt->fetchColumn();

// Claim statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM claims");
$stats['total_claims'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM claims WHERE status = 'pending'");
$stats['pending_claims'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM claims WHERE status = 'approved'");
$stats['approved_claims'] = $stmt->fetchColumn();

// Shop statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM shops WHERE is_approved = 0");
$stats['pending_shops'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM shops WHERE is_approved = 1 AND verified_badge = 1");
$stats['verified_shops'] = $stmt->fetchColumn();

// Payment statistics
$stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
$stats['total_revenue'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending_review'");
$stats['pending_payments'] = $stmt->fetchColumn();

// AI Statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM ai_requests");
$stats['total_ai_requests'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM voice_interactions");
$stats['total_voice_commands'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM report_matches");
$stats['total_matches'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM behavior_logs WHERE risk_level = 'high'");
$stats['high_risk_sessions'] = $stmt->fetchColumn() ?? 0;

// Get recent activities
$stmt = $pdo->query("
    SELECT 'user' as type, id, full_name as name, 'registered' as action, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_users = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT 'report' as type, id, title as name, 'submitted' as action, created_at 
    FROM reports 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_reports = $stmt->fetchAll();

// Merge recent activities
$recent_activities = array_merge($recent_users, $recent_reports);
usort($recent_activities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_activities = array_slice($recent_activities, 0, 8);

// Get monthly stats for charts
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%M') as month,
        COUNT(*) as users
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$monthly_users = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%M') as month,
        COUNT(*) as reports
    FROM reports 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
");
$monthly_reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Dashboard - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        .admin-gradient { 
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%); 
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #f97316, #ea580c, #f97316);
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }
        
        .stat-card:hover::before {
            transform: translateX(0);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.12);
        }
        
        .quick-action-btn {
            transition: all 0.2s ease;
            background: white;
            border-radius: 14px;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px rgba(0, 0, 0, 0.1);
        }
        
        .section-card {
            background: white;
            border-radius: 16px;
            transition: all 0.2s ease;
        }
        
        .ai-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            position: relative;
            overflow: hidden;
        }
        
        .activity-item {
            transition: all 0.2s ease;
        }
        
        .activity-item:hover {
            background-color: #f8fafc;
            transform: translateX(2px);
        }
        
        .pending-item {
            transition: all 0.2s ease;
            border-left: 2px solid transparent;
        }
        
        .pending-item:hover {
            background-color: #f8fafc;
            border-left-color: #f97316;
            transform: translateX(2px);
        }
        
        /* Chart container - perfect size */
        .chart-container {
            position: relative;
            height: 260px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50">

<?php include 'includes/navbar.php'; ?>

<!-- Admin Header -->
<div class="admin-gradient text-white py-5">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight">Admin Dashboard</h1>
                <p class="text-gray-300 text-xs mt-0.5">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></p>
            </div>
            <div class="flex gap-2">
                <span class="bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg text-xs">
                    <i class="fas fa-calendar-alt mr-1 text-xs"></i> <?php echo date('F j, Y'); ?>
                </span>
                <span class="bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-lg text-xs">
                    <i class="fas fa-chart-line mr-1 text-xs"></i> Live
                </span>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 py-6">
    
    <!-- Stats Row 1 -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
        <div class="stat-card p-3 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-500 text-sm"></i>
                </div>
                <span class="text-[11px] text-green-600 bg-green-50 px-1.5 py-0.5 rounded">+<?php echo $stats['new_today']; ?></span>
            </div>
            <p class="text-gray-500 text-[11px] uppercase tracking-wide">Total Users</p>
            <p class="text-xl font-bold text-gray-800"><?php echo number_format($stats['total_users']); ?></p>
        </div>
        
        <div class="stat-card p-3 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-emerald-500 text-sm"></i>
                </div>
                <span class="text-[11px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded"><?php echo $stats['pending_reports']; ?></span>
            </div>
            <p class="text-gray-500 text-[11px] uppercase tracking-wide">Total Reports</p>
            <p class="text-xl font-bold text-gray-800"><?php echo number_format($stats['total_reports']); ?></p>
        </div>
        
        <div class="stat-card p-3 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-gavel text-purple-500 text-sm"></i>
                </div>
                <span class="text-[11px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded"><?php echo $stats['pending_claims']; ?></span>
            </div>
            <p class="text-gray-500 text-[11px] uppercase tracking-wide">Total Claims</p>
            <p class="text-xl font-bold text-gray-800"><?php echo number_format($stats['total_claims']); ?></p>
        </div>
        
        <div class="stat-card p-3 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <div class="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-orange-500 text-sm"></i>
                </div>
                <span class="text-[11px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded"><?php echo $stats['pending_payments']; ?></span>
            </div>
            <p class="text-gray-500 text-[11px] uppercase tracking-wide">Revenue</p>
            <p class="text-base font-bold text-orange-600"><?php echo number_format($stats['total_revenue'], 0); ?> EGP</p>
        </div>
        
        <div class="stat-card p-3 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <div class="w-8 h-8 bg-cyan-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-store text-cyan-500 text-sm"></i>
                </div>
                <span class="text-[11px] text-green-600 bg-green-50 px-1.5 py-0.5 rounded"><?php echo $stats['verified_shops']; ?></span>
            </div>
            <p class="text-gray-500 text-[11px] uppercase tracking-wide">Shops</p>
            <p class="text-xl font-bold text-gray-800"><?php echo number_format($stats['total_shops']); ?></p>
        </div>
        
        <div class="stat-card p-3 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <div class="w-8 h-8 bg-rose-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-rose-500 text-sm"></i>
                </div>
                <span class="text-[11px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">review</span>
            </div>
            <p class="text-gray-500 text-[11px] uppercase tracking-wide">Pending</p>
            <p class="text-xl font-bold text-gray-800"><?php echo $stats['pending_shops'] + $stats['pending_payments'] + $stats['pending_reports']; ?></p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-4 md:grid-cols-8 gap-2 mb-6">
        <a href="admin_users.php" class="quick-action-btn p-2.5 text-center shadow-sm border border-gray-100">
            <i class="fas fa-users text-indigo-500 text-base mb-0.5 block"></i>
            <p class="text-[11px] font-medium text-gray-700">Users</p>
        </a>
        <a href="admin_reports.php" class="quick-action-btn p-2.5 text-center shadow-sm border border-gray-100">
            <i class="fas fa-file-alt text-emerald-500 text-base mb-0.5 block"></i>
            <p class="text-[11px] font-medium text-gray-700">Reports</p>
        </a>
        <a href="admin_claims.php" class="quick-action-btn p-2.5 text-center shadow-sm border border-gray-100">
            <i class="fas fa-gavel text-amber-500 text-base mb-0.5 block"></i>
            <p class="text-[11px] font-medium text-gray-700">Claims</p>
        </a>
        <a href="admin_shops.php" class="quick-action-btn p-2.5 text-center shadow-sm border border-gray-100">
            <i class="fas fa-store text-sky-500 text-base mb-0.5 block"></i>
            <p class="text-[11px] font-medium text-gray-700">Shops</p>
        </a>
        <a href="admin_payments.php" class="quick-action-btn p-2.5 text-center shadow-sm border border-gray-100">
            <i class="fas fa-credit-card text-orange-500 text-base mb-0.5 block"></i>
            <p class="text-[11px] font-medium text-gray-700">Payments</p>
        </a>
        <a href="admin_verifications.php" class="quick-action-btn p-2.5 text-center shadow-sm border border-gray-100">
            <i class="fas fa-shield-alt text-purple-500 text-base mb-0.5 block"></i>
            <p class="text-[11px] font-medium text-gray-700">Verify</p>
        </a>
        <a href="admin_notifications.php" class="quick-action-btn p-2.5 text-center shadow-sm border border-gray-100">
            <i class="fas fa-bell text-rose-500 text-base mb-0.5 block"></i>
            <p class="text-[11px] font-medium text-gray-700">Notify</p>
        </a>
        <a href="admin_finance.php" class="quick-action-btn p-2.5 text-center shadow-sm border border-gray-100">
            <i class="fas fa-chart-line text-teal-500 text-base mb-0.5 block"></i>
            <p class="text-[11px] font-medium text-gray-700">Finance</p>
        </a>
    </div>

    <!-- AI Intelligence Section -->
    <div class="ai-card p-4 mb-6 text-white shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-4">
            <div>
                <h2 class="text-base font-bold flex items-center gap-1.5">
                    <i class="fas fa-microchip text-base"></i> Intelligence Hub
                </h2>
                <p class="text-purple-200 text-xs mt-0.5">Real-time AI analytics and behavioral insights</p>
            </div>
            <a href="admin_ai_analytics.php" class="bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg text-xs font-medium transition backdrop-blur-sm">
                <i class="fas fa-chart-line text-xs mr-1"></i> Full Analytics
            </a>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white/10 rounded-lg p-2.5 text-center">
                <i class="fas fa-microphone-alt text-sm mb-1 inline-block"></i>
                <p class="text-xl font-bold"><?php echo $stats['total_voice_commands']; ?></p>
                <p class="text-[11px] text-purple-200">Voice Commands</p>
            </div>
            <div class="bg-white/10 rounded-lg p-2.5 text-center">
                <i class="fas fa-brain text-sm mb-1 inline-block"></i>
                <p class="text-xl font-bold"><?php echo $stats['total_ai_requests']; ?></p>
                <p class="text-[11px] text-purple-200">AI Requests</p>
            </div>
            <div class="bg-white/10 rounded-lg p-2.5 text-center">
                <i class="fas fa-link text-sm mb-1 inline-block"></i>
                <p class="text-xl font-bold"><?php echo $stats['total_matches']; ?></p>
                <p class="text-[11px] text-purple-200">Matches</p>
            </div>
            <div class="bg-white/10 rounded-lg p-2.5 text-center">
                <i class="fas fa-exclamation-triangle text-sm mb-1 inline-block"></i>
                <p class="text-xl font-bold text-red-300"><?php echo $stats['high_risk_sessions']; ?></p>
                <p class="text-[11px] text-purple-200">High Risk</p>
            </div>
        </div>
    </div>

    <!-- Charts Row - FIXED SIZE (not too big) -->
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="section-card p-5 shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-bold text-gray-800">User Growth</h2>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">Last 6 months</span>
            </div>
            <div class="chart-container">
                <canvas id="userChart"></canvas>
            </div>
        </div>

        <div class="section-card p-5 shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-bold text-gray-800">Reports Trend</h2>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">Last 6 months</span>
            </div>
            <div class="chart-container">
                <canvas id="reportsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Pending Items -->
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <!-- Recent Activity -->
        <div class="section-card shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                    <i class="fas fa-history text-gray-400 text-xs"></i> Recent Activity
                </h2>
            </div>
            <div class="divide-y max-h-80 overflow-y-auto">
                <?php foreach ($recent_activities as $activity): ?>
                <div class="activity-item p-3 transition">
                    <div class="flex items-start gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center <?php echo $activity['type'] === 'user' ? 'bg-blue-50' : 'bg-green-50'; ?>">
                            <i class="fas <?php echo $activity['type'] === 'user' ? 'fa-user-plus' : 'fa-upload'; ?> text-xs <?php echo $activity['type'] === 'user' ? 'text-blue-500' : 'text-green-500'; ?>"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($activity['name']); ?></p>
                            <p class="text-xs text-gray-500 mt-0.5"><?php echo ucfirst($activity['action']); ?> • <?php echo timeAgo($activity['created_at']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($recent_activities)): ?>
                    <div class="p-6 text-center text-gray-400">
                        <i class="fas fa-inbox text-2xl mb-2 opacity-50"></i>
                        <p class="text-xs">No recent activity</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Items -->
        <div class="section-card shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                    <i class="fas fa-clock text-amber-500 text-xs"></i> Pending Items
                </h2>
            </div>
            <div class="divide-y">
                <div class="pending-item p-3 flex justify-between items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 bg-amber-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-alt text-amber-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Reports to Review</p>
                            <p class="text-xs text-gray-500">Pending moderation</p>
                        </div>
                    </div>
                    <a href="admin_reports.php?status=pending" class="bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-xs px-2.5 py-1 rounded-lg transition">
                        <?php echo $stats['pending_reports']; ?> →
                    </a>
                </div>
                
                <div class="pending-item p-3 flex justify-between items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 bg-purple-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-gavel text-purple-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Claims to Resolve</p>
                            <p class="text-xs text-gray-500">Awaiting approval</p>
                        </div>
                    </div>
                    <a href="admin_claims.php?status=pending" class="bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold text-xs px-2.5 py-1 rounded-lg transition">
                        <?php echo $stats['pending_claims']; ?> →
                    </a>
                </div>
                
                <div class="pending-item p-3 flex justify-between items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 bg-sky-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-store text-sky-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Shops to Verify</p>
                            <p class="text-xs text-gray-500">Registration pending</p>
                        </div>
                    </div>
                    <a href="admin_shops.php" class="bg-sky-50 hover:bg-sky-100 text-sky-700 font-semibold text-xs px-2.5 py-1 rounded-lg transition">
                        <?php echo $stats['pending_shops']; ?> →
                    </a>
                </div>
                
                <div class="pending-item p-3 flex justify-between items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 bg-teal-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-credit-card text-teal-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Payments to Verify</p>
                            <p class="text-xs text-gray-500">Awaiting confirmation</p>
                        </div>
                    </div>
                    <a href="admin_payments.php?status=pending_review" class="bg-teal-50 hover:bg-teal-100 text-teal-700 font-semibold text-xs px-2.5 py-1 rounded-lg transition">
                        <?php echo $stats['pending_payments']; ?> →
                    </a>
                </div>
                
                <div class="pending-item p-3 flex justify-between items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 bg-indigo-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-id-card text-indigo-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Verification Requests</p>
                            <p class="text-xs text-gray-500">Documents to review</p>
                        </div>
                    </div>
                    <a href="admin_verifications.php" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs px-2.5 py-1 rounded-lg transition">
                        Review →
                    </a>
                </div>
                
                <div class="pending-item p-3 flex justify-between items-center bg-gradient-to-r from-purple-50 to-transparent">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-robot text-purple-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">AI Matches to Review</p>
                            <p class="text-xs text-gray-500">Pending confirmation</p>
                        </div>
                    </div>
                    <a href="admin_matches.php" class="bg-purple-100 hover:bg-purple-200 text-purple-700 font-semibold text-xs px-2.5 py-1 rounded-lg transition">
                        <?php echo $stats['total_matches']; ?> →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Footer -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white rounded-xl p-3 text-center shadow-sm border border-gray-100">
            <p class="text-xl font-bold text-green-600"><?php echo $stats['approved_claims']; ?></p>
            <p class="text-[11px] text-gray-500">Approved Claims</p>
        </div>
        <div class="bg-white rounded-xl p-3 text-center shadow-sm border border-gray-100">
            <p class="text-xl font-bold text-purple-600"><?php echo $stats['verified_shops']; ?></p>
            <p class="text-[11px] text-gray-500">Verified Shops</p>
        </div>
        <div class="bg-white rounded-xl p-3 text-center shadow-sm border border-gray-100">
            <p class="text-xl font-bold text-blue-600"><?php echo $stats['total_individuals']; ?></p>
            <p class="text-[11px] text-gray-500">Individual Users</p>
        </div>
        <div class="bg-white rounded-xl p-3 text-center shadow-sm border border-gray-100">
            <p class="text-xl font-bold text-orange-600"><?php echo $stats['reports_today']; ?></p>
            <p class="text-[11px] text-gray-500">Reports Today</p>
        </div>
        <div class="bg-white rounded-xl p-3 text-center shadow-sm border border-gray-100">
            <p class="text-xl font-bold text-indigo-600"><?php echo $stats['total_matches']; ?></p>
            <p class="text-[11px] text-gray-500">AI Matches</p>
        </div>
    </div>
</div>

<script>
// User Growth Chart - Perfect size
const userCtx = document.getElementById('userChart').getContext('2d');
const userMonths = <?php echo json_encode(array_column($monthly_users, 'month')); ?>;
const userCounts = <?php echo json_encode(array_column($monthly_users, 'users')); ?>;

new Chart(userCtx, {
    type: 'line',
    data: {
        labels: userMonths,
        datasets: [{
            label: 'New Users',
            data: userCounts,
            borderColor: '#f97316',
            backgroundColor: 'rgba(249, 115, 22, 0.06)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#f97316',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { 
                position: 'bottom', 
                labels: { 
                    boxWidth: 10, 
                    font: { size: 11 } 
                } 
            }
        },
        scales: {
            y: { 
                beginAtZero: true, 
                grid: { color: '#e5e7eb' }, 
                ticks: { font: { size: 10 } } 
            },
            x: { 
                grid: { display: false }, 
                ticks: { font: { size: 10 } } 
            }
        }
    }
});

// Reports Chart - Perfect size
const reportsCtx = document.getElementById('reportsChart').getContext('2d');
const reportsMonths = <?php echo json_encode(array_column($monthly_reports, 'month')); ?>;
const reportsCounts = <?php echo json_encode(array_column($monthly_reports, 'reports')); ?>;

new Chart(reportsCtx, {
    type: 'bar',
    data: {
        labels: reportsMonths,
        datasets: [{
            label: 'Reports Submitted',
            data: reportsCounts,
            backgroundColor: '#f97316',
            borderRadius: 6,
            barPercentage: 0.7,
            categoryPercentage: 0.8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { 
                position: 'bottom', 
                labels: { 
                    boxWidth: 10, 
                    font: { size: 11 } 
                } 
            }
        },
        scales: {
            y: { 
                beginAtZero: true, 
                grid: { color: '#e5e7eb' }, 
                ticks: { font: { size: 10 } } 
            },
            x: { 
                grid: { display: false }, 
                ticks: { font: { size: 10 } } 
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>