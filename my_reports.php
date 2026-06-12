<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Findex - My Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .gradient-orange { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .card { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        body { background: #f5f5f0; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="card overflow-hidden">
        <div class="divide-y">
            <?php if (empty($reports)): ?>
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-folder-open text-5xl mb-3"></i>
                <p class="text-lg">No reports found</p>
                <a href="report_item.php" class="inline-block mt-4 text-orange-600">Create your first report →</a>
            </div>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                <div class="p-6 hover:bg-gray-50 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $report['report_type'] === 'stolen' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst($report['report_type']); ?>
                                </span>
                                <?php echo getStatusBadge($report['status']); ?>
                            </div>
                            <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($report['title']); ?></h3>
                            <p class="text-gray-600 text-sm mt-1"><?php echo truncateText($report['description'], 100); ?></p>
                            <p class="text-xs text-gray-500 mt-2"><?php echo timeAgo($report['created_at']); ?></p>
                        </div>
                        <a href="view_report.php?id=<?php echo $report['id']; ?>" class="text-orange-600 hover:text-orange-700">View →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- AI Assistant Widget -->
<?php include 'includes/ai_assistant.php'; ?>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>

</body>
</html>