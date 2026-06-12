<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a shop user
requireUserType(['shop']);

$user_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

$sql = "SELECT * FROM reports WHERE user_id = ?";
$params = [$user_id];

if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}
if ($type_filter !== 'all') {
    $sql .= " AND report_type = ?";
    $params[] = $type_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Findex - Shop Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f5f5f0; }
        .card { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .filter-select { border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px 12px; font-size: 13px; }
        .filter-select:focus { outline: none; border-color: #f97316; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">My Shop Reports</h1>
            <p class="text-gray-500 text-sm mt-1">Manage and track all your jewelry reports</p>
        </div>
        <a href="report_item.php" class="orange-gradient text-white px-5 py-2 rounded-full text-sm font-semibold shadow-md hover:shadow-lg transition">
            <i class="fas fa-plus mr-2"></i> New Report
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status" class="filter-select">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Report Type</label>
                <select name="type" class="filter-select">
                    <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="lost" <?php echo $type_filter === 'lost' ? 'selected' : ''; ?>>Lost</option>
                    <option value="stolen" <?php echo $type_filter === 'stolen' ? 'selected' : ''; ?>>Stolen</option>
                    <option value="found" <?php echo $type_filter === 'found' ? 'selected' : ''; ?>>Found</option>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-orange-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
            </div>
            <div>
                <a href="shop_reports.php" class="text-gray-500 hover:text-orange-600 text-sm py-2 px-3 inline-block">
                    <i class="fas fa-redo-alt mr-1"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Reports List -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="divide-y">
            <?php if (empty($reports)): ?>
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-folder-open text-5xl mb-3 text-gray-300"></i>
                    <p class="text-lg font-medium">No reports found</p>
                    <p class="text-sm mt-1">Create your first report to get started</p>
                    <a href="report_item.php" class="inline-block mt-4 text-orange-600 font-semibold text-sm hover:underline">
                        Create your first report →
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <div class="p-5 hover:bg-gray-50 transition group">
                        <div class="flex flex-wrap justify-between items-start gap-4">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="px-2 py-1 text-xs rounded-full font-medium 
                                        <?php echo $report['report_type'] === 'stolen' ? 'bg-red-100 text-red-800' : 
                                           ($report['report_type'] === 'lost' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                        <?php echo ucfirst($report['report_type']); ?>
                                    </span>
                                    <span class="px-2 py-1 text-xs rounded-full font-medium 
                                        <?php echo $report['status'] === 'active' ? 'bg-blue-100 text-blue-800' : 
                                           ($report['status'] === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo ucfirst($report['status']); ?>
                                    </span>
                                    <?php if ($report['is_boosted']): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                            <i class="fas fa-rocket mr-1"></i> Boosted
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="view_report.php?id=<?php echo $report['id']; ?>" class="block">
                                    <h3 class="font-semibold text-lg text-gray-800 group-hover:text-orange-600 transition">
                                        <?php echo htmlspecialchars($report['title']); ?>
                                    </h3>
                                </a>
                                
                                <p class="text-gray-600 text-sm mt-1 line-clamp-2">
                                    <?php echo htmlspecialchars(substr($report['description'], 0, 120)); ?>...
                                </p>
                                
                                <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-gray-500">
                                    <span><i class="fas fa-map-marker-alt text-orange-500 mr-1"></i> <?php echo htmlspecialchars($report['location']); ?></span>
                                    <span><i class="far fa-calendar text-orange-500 mr-1"></i> <?php echo date('M d, Y', strtotime($report['incident_date'])); ?></span>
                                    <span><i class="far fa-eye text-orange-500 mr-1"></i> <?php echo number_format($report['views_count']); ?> views</span>
                                    <span><i class="far fa-clock text-orange-500 mr-1"></i> <?php echo date('M d, Y', strtotime($report['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="view_report.php?id=<?php echo $report['id']; ?>" class="orange-gradient text-white px-4 py-1.5 rounded-full text-xs font-semibold shadow-sm hover:shadow transition">
                                    View Details <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
            <div class="text-2xl font-bold text-orange-600"><?php echo count($reports); ?></div>
            <div class="text-xs text-gray-500">Total Reports</div>
        </div>
        <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
            <div class="text-2xl font-bold text-green-600">
                <?php 
                $active_count = 0;
                foreach ($reports as $r) {
                    if ($r['status'] === 'active') $active_count++;
                }
                echo $active_count;
                ?>
            </div>
            <div class="text-xs text-gray-500">Active Reports</div>
        </div>
        <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
            <div class="text-2xl font-bold text-blue-600">
                <?php 
                $total_views = 0;
                foreach ($reports as $r) {
                    $total_views += $r['views_count'];
                }
                echo number_format($total_views);
                ?>
            </div>
            <div class="text-xs text-gray-500">Total Views</div>
        </div>
        <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
            <div class="text-2xl font-bold text-purple-600">
                <?php 
                $boosted = 0;
                foreach ($reports as $r) {
                    if ($r['is_boosted']) $boosted++;
                }
                echo $boosted;
                ?>
            </div>
            <div class="text-xs text-gray-500">Boosted Reports</div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>