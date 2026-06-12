<?php
// admin_ai_analytics.php - Professional AI Analytics Dashboard
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check admin access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get AI statistics
$stats = [];

// AI Requests Statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM ai_requests");
$stats['total_ai_requests'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM ai_requests WHERE status = 'completed'");
$stats['completed_ai_requests'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM ai_requests WHERE status = 'pending'");
$stats['pending_ai_requests'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM ai_requests WHERE DATE(created_at) = CURDATE()");
$stats['ai_requests_today'] = $stmt->fetchColumn() ?? 0;

// Request types breakdown
$stmt = $pdo->query("
    SELECT request_type, COUNT(*) as count 
    FROM ai_requests 
    GROUP BY request_type 
    ORDER BY count DESC
");
$request_types = $stmt->fetchAll();

// Voice Interactions Statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM voice_interactions");
$stats['total_voice_commands'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM voice_interactions WHERE DATE(created_at) = CURDATE()");
$stats['voice_commands_today'] = $stmt->fetchColumn() ?? 0;

// Intent breakdown
$stmt = $pdo->query("
    SELECT intent, COUNT(*) as count, AVG(confidence) as avg_confidence
    FROM voice_interactions 
    GROUP BY intent 
    ORDER BY count DESC
");
$intent_breakdown = $stmt->fetchAll();

// Behavior Logs Statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM behavior_logs");
$stats['total_behavior_sessions'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM behavior_logs WHERE risk_level = 'high'");
$stats['high_risk_sessions'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM behavior_logs WHERE risk_level = 'medium'");
$stats['medium_risk_sessions'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM behavior_logs WHERE risk_level = 'low'");
$stats['low_risk_sessions'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT AVG(behavior_score) as avg FROM behavior_logs");
$avg_behavior = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['avg_behavior_score'] = round($avg_behavior['avg'] ?? 0, 1);

// Report Matches Statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM report_matches");
$stats['total_matches'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM report_matches WHERE status = 'confirmed'");
$stats['confirmed_matches'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM report_matches WHERE status = 'pending'");
$stats['pending_matches'] = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->query("SELECT AVG(match_score) as avg FROM report_matches");
$avg_match = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['avg_match_score'] = round($avg_match['avg'] ?? 0, 1);

// Daily AI activity for last 7 days
$stmt = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM ai_requests
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$daily_activity = $stmt->fetchAll();

// Recent AI requests with user details
$stmt = $pdo->query("
    SELECT a.*, u.full_name, u.email 
    FROM ai_requests a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 15
");
$recent_requests = $stmt->fetchAll();

// High risk users
$stmt = $pdo->query("
    SELECT DISTINCT u.id, u.full_name, u.email, b.risk_level, b.behavior_score, b.analyzed_at
    FROM behavior_logs b
    JOIN users u ON b.user_id = u.id
    WHERE b.risk_level = 'high'
    ORDER BY b.analyzed_at DESC
    LIMIT 10
");
$high_risk_users = $stmt->fetchAll();

// Recent voice commands
$stmt = $pdo->query("
    SELECT v.*, u.full_name 
    FROM voice_interactions v 
    JOIN users u ON v.user_id = u.id 
    ORDER BY v.created_at DESC 
    LIMIT 10
");
$recent_voice_commands = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Analytics - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .gradient-header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); }
        .stat-card { transition: all 0.3s ease; background: white; border-radius: 16px; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -6px rgba(0,0,0,0.1); }
        .risk-high { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-left: 3px solid #dc2626; }
        .table-hover tbody tr:hover { background-color: #f8fafc; }
        
        /* Chart container - fixed reasonable size */
        .chart-container {
            position: relative;
            height: 260px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'includes/navbar.php'; ?>

<!-- Header -->
<div class="gradient-header text-white py-5">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
            <div>
                <h1 class="text-xl font-bold">AI Analytics Dashboard</h1>
                <p class="text-gray-300 text-xs mt-0.5">Monitor artificial intelligence system performance and usage metrics</p>
            </div>
            <div class="flex gap-2">
                <span class="bg-white/20 px-3 py-1.5 rounded-lg text-xs">
                    <i class="fas fa-chart-line mr-1 text-xs"></i> Real-time Analytics
                </span>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 py-6">

    <!-- Statistics Cards Row 1 -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="stat-card p-4 shadow-sm border-l-4 border-indigo-500">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Total AI Requests</p>
            <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($stats['total_ai_requests']); ?></p>
            <div class="flex justify-between mt-1">
                <span class="text-xs text-green-600"><?php echo $stats['completed_ai_requests']; ?> completed</span>
                <span class="text-xs text-amber-600"><?php echo $stats['pending_ai_requests']; ?> pending</span>
            </div>
        </div>
        
        <div class="stat-card p-4 shadow-sm border-l-4 border-emerald-500">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Voice Commands</p>
            <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($stats['total_voice_commands']); ?></p>
            <p class="text-xs text-emerald-600 mt-1">+<?php echo $stats['voice_commands_today']; ?> today</p>
        </div>
        
        <div class="stat-card p-4 shadow-sm border-l-4 border-purple-500">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Report Matches</p>
            <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($stats['total_matches']); ?></p>
            <div class="flex justify-between mt-1">
                <span class="text-xs text-green-600"><?php echo $stats['confirmed_matches']; ?> confirmed</span>
                <span class="text-xs text-amber-600"><?php echo $stats['pending_matches']; ?> pending</span>
            </div>
        </div>
        
        <div class="stat-card p-4 shadow-sm border-l-4 border-orange-500">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Behavior Sessions</p>
            <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($stats['total_behavior_sessions']); ?></p>
            <div class="flex justify-between mt-1">
                <span class="text-xs text-red-600"><?php echo $stats['high_risk_sessions']; ?> high risk</span>
                <span class="text-xs text-gray-500">avg <?php echo $stats['avg_behavior_score']; ?></span>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row 2 -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card p-4 shadow-sm">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Average Match Score</p>
            <p class="text-2xl font-bold text-purple-600 mt-1"><?php echo $stats['avg_match_score']; ?>%</p>
            <p class="text-xs text-gray-400 mt-1">AI matching accuracy</p>
        </div>
        
        <div class="stat-card p-4 shadow-sm">
            <p class="text-gray-500 text-xs uppercase tracking-wide">AI Requests Today</p>
            <p class="text-2xl font-bold text-blue-600 mt-1"><?php echo $stats['ai_requests_today']; ?></p>
            <p class="text-xs text-gray-400 mt-1">Last 24 hours</p>
        </div>
        
        <div class="stat-card p-4 shadow-sm">
            <p class="text-gray-500 text-xs uppercase tracking-wide">High Risk Users</p>
            <p class="text-2xl font-bold text-red-600 mt-1"><?php echo count($high_risk_users); ?></p>
            <p class="text-xs text-gray-400 mt-1">Requires attention</p>
        </div>
        
        <div class="stat-card p-4 shadow-sm">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Success Rate</p>
            <p class="text-2xl font-bold text-green-600 mt-1">
                <?php 
                $success_rate = $stats['total_ai_requests'] > 0 
                    ? round(($stats['completed_ai_requests'] / $stats['total_ai_requests']) * 100, 1) 
                    : 0;
                echo $success_rate; ?>%
            </p>
            <p class="text-xs text-gray-400 mt-1">Completion rate</p>
        </div>
    </div>

    <!-- Charts Row - FIXED SIZE (not too big) -->
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-semibold text-gray-800">Daily AI Activity</h2>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">Last 7 days</span>
            </div>
            <div class="chart-container">
                <canvas id="dailyActivityChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-semibold text-gray-800">Request Type Distribution</h2>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">By category</span>
            </div>
            <div class="chart-container">
                <canvas id="requestTypeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- High Risk Users Section -->
    <?php if (count($high_risk_users) > 0): ?>
    <div class="bg-white rounded-xl shadow-sm border border-red-200 mb-6 overflow-hidden">
        <div class="bg-red-50 px-5 py-3 border-b border-red-200">
            <h2 class="text-sm font-semibold text-red-700 flex items-center gap-2">
                <i class="fas fa-shield-alt"></i> Security Alerts - High Risk Behavior Detected
            </h2>
            <p class="text-xs text-red-600 mt-0.5">Users exhibiting unusual interaction patterns requiring investigation</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left font-semibold text-gray-600">User</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Risk Level</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Behavior Score</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Detected At</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($high_risk_users as $user): ?>
                    <tr class="hover:bg-red-50 transition">
                        <td class="p-3">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $user['email']; ?></p>
                            </div>
                        </td>
                        <td class="p-3">
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-md text-xs font-medium">HIGH</span>
                        </td>
                        <td class="p-3 font-bold text-red-600"><?php echo $user['behavior_score']; ?></td>
                        <td class="p-3 text-gray-500 text-xs"><?php echo date('M d, Y H:i', strtotime($user['analyzed_at'])); ?></td>
                        <td class="p-3">
                            <a href="profile.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                View Profile →
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
             </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Voice Commands Log -->
    <?php if (count($recent_voice_commands) > 0): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-microphone-alt text-green-600"></i> Voice Command Log
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Recent voice interactions with the AI assistant</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm table-hover">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left font-semibold text-gray-600">User</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Voice Command</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Intent</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Confidence</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($recent_voice_commands as $voice): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-3 font-medium text-gray-800"><?php echo htmlspecialchars($voice['full_name']); ?></td>
                        <td class="p-3 text-gray-600 max-w-md">
                            <span class="italic">"<?php echo htmlspecialchars(substr($voice['voice_text'], 0, 80)); ?><?php echo strlen($voice['voice_text']) > 80 ? '...' : ''; ?>"</span>
                        </td>
                        <td class="p-3">
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded-md text-xs font-medium">
                                <?php echo str_replace('_', ' ', $voice['intent']); ?>
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-green-500 h-1.5 rounded-full" style="width: <?php echo $voice['confidence'] * 100; ?>%"></div>
                                </div>
                                <span class="text-xs text-gray-600"><?php echo round($voice['confidence'] * 100); ?>%</span>
                            </div>
                        </td>
                        <td class="p-3 text-gray-500 text-xs"><?php echo timeAgo($voice['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
             </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent AI Requests -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-database text-purple-600"></i> Recent AI Processing Requests
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Latest AI analysis and generation requests</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm table-hover">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left font-semibold text-gray-600">User</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Request Type</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Input Preview</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Time</th>
                        <th class="p-3 text-left font-semibold text-gray-600">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($recent_requests as $request): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-3">
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($request['full_name']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo $request['email']; ?></p>
                        </td>
                        <td class="p-3">
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-md text-xs font-medium">
                                <?php echo str_replace('_', ' ', $request['request_type']); ?>
                            </span>
                        </td>
                        <td class="p-3 text-gray-500 max-w-xs truncate text-xs">
                            <?php 
                            $input = json_decode($request['input_data'], true);
                            $preview = $input['description'] ?? $input['query'] ?? substr($request['input_data'], 0, 50);
                            echo htmlspecialchars($preview);
                            ?>
                        </td>
                        <td class="p-3">
                            <?php if($request['status'] == 'completed'): ?>
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-xs font-medium">Completed</span>
                            <?php elseif($request['status'] == 'pending'): ?>
                                <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded-md text-xs font-medium">Pending</span>
                            <?php elseif($request['status'] == 'processing'): ?>
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-md text-xs font-medium">Processing</span>
                            <?php else: ?>
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-md text-xs font-medium"><?php echo $request['status']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-gray-500 text-xs"><?php echo timeAgo($request['created_at']); ?></td>
                        <td class="p-3 text-gray-500 text-xs">
                            <?php echo $request['processing_time'] ? $request['processing_time'] . 's' : '-'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
             </table>
        </div>
    </div>

    <!-- Intent Breakdown & Risk Distribution -->
    <div class="grid md:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Voice Command Intent Analysis</h2>
            <div class="space-y-3">
                <?php foreach ($intent_breakdown as $intent): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700"><?php echo ucfirst(str_replace('_', ' ', $intent['intent'])); ?></span>
                        <span class="text-gray-500"><?php echo $intent['count']; ?> commands (<?php echo round($intent['avg_confidence'] * 100); ?>% accuracy)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-500 h-2 rounded-full" style="width: <?php echo ($intent['count'] / max(1, $stats['total_voice_commands'])) * 100; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Behavior Risk Distribution</h2>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700">Low Risk</span>
                        <span class="text-green-600"><?php echo $stats['low_risk_sessions']; ?> sessions</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo ($stats['low_risk_sessions'] / max(1, $stats['total_behavior_sessions'])) * 100; ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700">Medium Risk</span>
                        <span class="text-amber-600"><?php echo $stats['medium_risk_sessions']; ?> sessions</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-amber-500 h-2 rounded-full" style="width: <?php echo ($stats['medium_risk_sessions'] / max(1, $stats['total_behavior_sessions'])) * 100; ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700">High Risk</span>
                        <span class="text-red-600"><?php echo $stats['high_risk_sessions']; ?> sessions</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo ($stats['high_risk_sessions'] / max(1, $stats['total_behavior_sessions'])) * 100; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Daily Activity Chart - Fixed size
const dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
const dailyDates = <?php echo json_encode(array_column($daily_activity, 'date')); ?>;
const dailyCounts = <?php echo json_encode(array_column($daily_activity, 'count')); ?>;

new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyDates,
        datasets: [{
            label: 'AI Requests',
            data: dailyCounts,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.08)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#6366f1',
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

// Request Type Distribution Chart - Fixed size
const typeCtx = document.getElementById('requestTypeChart').getContext('2d');
const typeLabels = <?php echo json_encode(array_column($request_types, 'request_type')); ?>;
const typeCounts = <?php echo json_encode(array_column($request_types, 'count')); ?>;

const cleanLabels = typeLabels.map(label => label.replace(/_/g, ' '));

new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: cleanLabels,
        datasets: [{
            data: typeCounts,
            backgroundColor: ['#6366f1', '#8b5cf6', '#a855f7', '#c084fc', '#e879f9', '#f472b6'],
            borderWidth: 0,
            hoverOffset: 8
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
                    font: { size: 11 },
                    padding: 12
                } 
            }
        },
        cutout: '55%'
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>