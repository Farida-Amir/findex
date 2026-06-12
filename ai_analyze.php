<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

if (!$report_id) {
    header('Location: my_reports.php');
    exit();
}

// Get report details
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as user_name 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.id = ?
");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    header('Location: my_reports.php');
    exit();
}

// Mock AI Analysis (in production, call actual AI API)
$ai_analysis = [
    'category' => ucfirst($report['report_type']),
    'confidence' => rand(85, 98),
    'sentiment' => rand(70, 95),
    'keywords' => ['jewelry', 'luxury', 'diamond', 'gold', 'precious'],
    'recommendations' => [
        'This item appears to be high-value based on description',
        'Recommend verifying through serial numbers if available',
        'Suggest cross-referencing with local pawn shops',
        'Consider posting to social media for wider reach'
    ],
    'risk_level' => $report['estimated_value'] > 5000 ? 'High' : ($report['estimated_value'] > 1000 ? 'Medium' : 'Low'),
    'similar_reports' => rand(0, 5),
    'market_value_estimate' => $report['estimated_value'] ?: rand(500, 10000)
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Analysis - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="bg-gray-100">

<?php include 'includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="gradient-bg rounded-2xl p-6 text-white mb-6">
        <div class="flex items-center gap-3">
            <i class="fas fa-robot text-3xl"></i>
            <div>
                <h1 class="text-2xl font-bold">AI Analysis Report</h1>
                <p class="text-white/80 text-sm">Powered by Findex AI Intelligence</p>
            </div>
        </div>
    </div>

    <!-- Report Context -->
    <div class="card p-6 mb-6">
        <h2 class="font-bold text-lg mb-3">Analyzing Report #<?= $report_id ?>: <?= htmlspecialchars($report['title']) ?></h2>
        <div class="flex flex-wrap gap-2">
            <span class="px-2 py-1 bg-gray-100 rounded-lg text-xs">Reported by: <?= htmlspecialchars($report['user_name']) ?></span>
            <span class="px-2 py-1 bg-gray-100 rounded-lg text-xs">Type: <?= ucfirst($report['report_type']) ?></span>
            <span class="px-2 py-1 bg-gray-100 rounded-lg text-xs">Date: <?= date('M d, Y', strtotime($report['incident_date'])) ?></span>
        </div>
    </div>

    <!-- AI Analysis Results -->
    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <!-- Confidence Score -->
        <div class="card p-6 text-center">
            <div class="text-gray-500 text-sm mb-2">AI Confidence Score</div>
            <div class="text-5xl font-bold <?= $ai_analysis['confidence'] >= 90 ? 'text-green-600' : ($ai_analysis['confidence'] >= 75 ? 'text-orange-500' : 'text-red-500') ?>">
                <?= $ai_analysis['confidence'] ?>%
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" style="width: <?= $ai_analysis['confidence'] ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Analysis reliability score</p>
        </div>

        <!-- Risk Assessment -->
        <div class="card p-6 text-center">
            <div class="text-gray-500 text-sm mb-2">Risk Assessment</div>
            <div class="text-3xl font-bold mb-2">
                <?php if ($ai_analysis['risk_level'] === 'High'): ?>
                    <span class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>High Risk</span>
                <?php elseif ($ai_analysis['risk_level'] === 'Medium'): ?>
                    <span class="text-orange-600"><i class="fas fa-chart-line mr-2"></i>Medium Risk</span>
                <?php else: ?>
                    <span class="text-green-600"><i class="fas fa-shield-alt mr-2"></i>Low Risk</span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-500">Based on value and report type</p>
        </div>
    </div>

    <!-- Key Insights -->
    <div class="card p-6 mb-6">
        <h3 class="font-bold text-lg mb-4"><i class="fas fa-chart-bar text-purple-500 mr-2"></i>Key Insights</h3>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-gray-50 rounded-xl p-3">
                <div class="text-xs text-gray-500">Market Value Estimate</div>
                <div class="text-xl font-bold text-gray-800">$<?= number_format($ai_analysis['market_value_estimate'], 2) ?></div>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
                <div class="text-xs text-gray-500">Similar Reports Found</div>
                <div class="text-xl font-bold text-gray-800"><?= $ai_analysis['similar_reports'] ?></div>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
                <div class="text-xs text-gray-500">Sentiment Score</div>
                <div class="text-xl font-bold text-gray-800"><?= $ai_analysis['sentiment'] ?>%</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
                <div class="text-xs text-gray-500">Category</div>
                <div class="text-xl font-bold text-gray-800"><?= $ai_analysis['category'] ?></div>
            </div>
        </div>
    </div>

    <!-- Keywords -->
    <div class="card p-6 mb-6">
        <h3 class="font-bold text-lg mb-3"><i class="fas fa-tags text-purple-500 mr-2"></i>Detected Keywords</h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($ai_analysis['keywords'] as $keyword): ?>
                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm">#<?= $keyword ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="card p-6 mb-6">
        <h3 class="font-bold text-lg mb-3"><i class="fas fa-lightbulb text-yellow-500 mr-2"></i>AI Recommendations</h3>
        <ul class="space-y-2">
            <?php foreach ($ai_analysis['recommendations'] as $rec): ?>
                <li class="flex items-start gap-2">
                    <i class="fas fa-chevron-right text-purple-500 mt-0.5 text-sm"></i>
                    <span class="text-gray-700 text-sm"><?= $rec ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Disclaimer -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex gap-3">
            <i class="fas fa-info-circle text-blue-500 text-xl"></i>
            <div class="text-xs text-blue-800">
                <p class="font-semibold">About this Analysis</p>
                <p>This AI analysis is generated based on available data and should be used as a reference only. 
                Always verify information through official channels before making decisions.</p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-3 mt-6">
        <a href="view_report.php?id=<?= $report_id ?>" class="bg-gray-500 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i> Back to Report
        </a>
        <button onclick="window.print()" class="bg-purple-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-purple-700 transition">
            <i class="fas fa-print mr-2"></i> Print Analysis
        </button>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>