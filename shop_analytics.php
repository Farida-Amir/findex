<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireUserType(['shop']);

$user_id = $_SESSION['user_id'];

// Get total reports
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reports WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_reports = $stmt->fetchColumn();

// Get total views
$stmt = $pdo->prepare("SELECT SUM(views_count) as total FROM reports WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_views = $stmt->fetchColumn() ?: 0;

// Get reports by type
$stmt = $pdo->prepare("
    SELECT report_type, COUNT(*) as count 
    FROM reports 
    WHERE user_id = ? 
    GROUP BY report_type
");
$stmt->execute([$user_id]);
$type_stats = $stmt->fetchAll();

// Get monthly reports
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%M') as month, COUNT(*) as count 
    FROM reports 
    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY MONTH(created_at)
    ORDER BY created_at ASC
");
$stmt->execute([$user_id]);
$monthly = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Analytics - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f5f5f0; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Shop Analytics</h1>
    
    <!-- Stats Cards -->
    <div class="grid md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 text-center shadow">
            <div class="text-3xl font-bold text-orange-600"><?php echo $total_reports; ?></div>
            <div class="text-sm text-gray-500">Total Reports</div>
        </div>
        <div class="bg-white rounded-xl p-5 text-center shadow">
            <div class="text-3xl font-bold text-blue-600"><?php echo number_format($total_views); ?></div>
            <div class="text-sm text-gray-500">Total Views</div>
        </div>
        <div class="bg-white rounded-xl p-5 text-center shadow">
            <div class="text-3xl font-bold text-green-600"><?php echo $total_reports > 0 ? round($total_views / $total_reports) : 0; ?></div>
            <div class="text-sm text-gray-500">Avg Views/Report</div>
        </div>
        <div class="bg-white rounded-xl p-5 text-center shadow">
            <div class="text-3xl font-bold text-purple-600"><?php echo date('M Y'); ?></div>
            <div class="text-sm text-gray-500">Current Month</div>
        </div>
    </div>
    
    <!-- Reports by Type -->
    <div class="bg-white rounded-xl p-6 shadow mb-6">
        <h2 class="font-bold text-lg mb-4">Reports by Type</h2>
        <div class="space-y-3">
            <?php foreach ($type_stats as $stat): ?>
                <div class="flex justify-between items-center">
                    <span class="capitalize"><?php echo $stat['report_type']; ?></span>
                    <span class="font-semibold"><?php echo $stat['count']; ?> reports</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="orange-gradient h-2 rounded-full" style="width: <?php echo ($stat['count'] / max(array_sum(array_column($type_stats, 'count')), 1)) * 100; ?>%"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Tips -->
    <div class="gold-gradient rounded-xl p-5 text-white">
        <div class="flex items-center gap-3">
            <i class="fas fa-lightbulb text-2xl"></i>
            <div>
                <h3 class="font-bold">Pro Tip</h3>
                <p class="text-sm opacity-90">Boost important reports to get more views and faster recovery!</p>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>