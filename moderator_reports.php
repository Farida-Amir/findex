<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();
requireUserType(['moderator', 'admin']);

// Handle moderation action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $report_id = (int)$_POST['report_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE reports SET moderation_status = 'approved', moderation_notes = ? WHERE id = ?");
        $stmt->execute([$notes, $report_id]);
        $_SESSION['success'] = 'Report approved successfully.';
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE reports SET moderation_status = 'rejected', status = 'closed', moderation_notes = ? WHERE id = ?");
        $stmt->execute([$notes, $report_id]);
        $_SESSION['success'] = 'Report rejected and closed.';
    } elseif ($action === 'flag') {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'flagged', moderation_notes = ? WHERE id = ?");
        $stmt->execute([$notes, $report_id]);
        $_SESSION['success'] = 'Report flagged for review.';
    }
    header('Location: moderator_reports.php');
    exit();
}

// Get reports pending moderation
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as user_name, u.email as user_email 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.moderation_status = 'pending' 
    ORDER BY r.created_at ASC
");
$stmt->execute();
$pending_reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderate Reports - Findex Trial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg border-b-4 border-orange-500">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-orange-600 text-2xl mr-2"></i>
                    <span class="font-bold text-xl">Content Moderation</span>
                </div>
                <div class="flex space-x-4">
                    <a href="moderator.php" class="text-gray-600 hover:text-orange-600">Dashboard</a>
                    <a href="moderator_claims.php" class="text-gray-600 hover:text-orange-600">Claims</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <div class="grid gap-6">
            <?php foreach ($pending_reports as $report): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending Review</span>
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?= $report['report_type'] === 'stolen' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= ucfirst($report['report_type']) ?>
                                </span>
                                <span class="text-sm text-gray-500">Report #<?= $report['id'] ?></span>
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($report['title']) ?></h3>
                            <p class="text-gray-600 mb-4"><?= nl2br(htmlspecialchars($report['description'])) ?></p>
                            
                            <div class="grid md:grid-cols-2 gap-4 text-sm mb-4">
                                <div>
                                    <p><strong class="text-gray-700">Reported by:</strong> <?= htmlspecialchars($report['user_name']) ?> (<?= htmlspecialchars($report['user_email']) ?>)</p>
                                    <p><strong class="text-gray-700">Location:</strong> <?= htmlspecialchars($report['location']) ?></p>
                                    <p><strong class="text-gray-700">Incident Date:</strong> <?= date('F d, Y', strtotime($report['incident_date'])) ?></p>
                                </div>
                                <div>
                                    <p><strong class="text-gray-700">Created:</strong> <?= date('F d, Y H:i', strtotime($report['created_at'])) ?></p>
                                    <p><strong class="text-gray-700">Police Report:</strong> <?= $report['police_report_number'] ?: 'Not provided' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" class="mt-4 pt-4 border-t">
                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                        <div class="mb-3">
                            <textarea name="notes" rows="2" class="w-full border rounded-lg p-2 text-sm" placeholder="Moderation notes..."></textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                <i class="fas fa-check mr-1"></i> Approve
                            </button>
                            <button type="submit" name="action" value="reject" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                                <i class="fas fa-times mr-1"></i> Reject
                            </button>
                            <button type="submit" name="action" value="flag" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">
                                <i class="fas fa-flag mr-1"></i> Flag
                            </button>
                            <a href="view_report.php?id=<?= $report['id'] ?>" target="_blank" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                                <i class="fas fa-external-link-alt mr-1"></i> View Full
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($pending_reports)): ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-800">All caught up!</h3>
                <p class="text-gray-600 mt-2">No reports pending moderation.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>