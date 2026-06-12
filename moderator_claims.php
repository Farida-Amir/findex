<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();
requireUserType(['moderator', 'admin']);

// Handle claim moderation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'])) {
    $claim_id = (int)$_POST['claim_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE claims SET status = 'approved', resolved_by = ?, resolved_at = NOW(), moderator_notes = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $notes, $claim_id]);
        
        // Also update the report status
        $stmt = $pdo->prepare("
            UPDATE reports r 
            JOIN claims c ON c.report_id = r.id 
            SET r.status = 'resolved' 
            WHERE c.id = ?
        ");
        $stmt->execute([$claim_id]);
        
        $_SESSION['success'] = 'Claim approved and report marked as resolved.';
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE claims SET status = 'rejected', resolved_by = ?, resolved_at = NOW(), moderator_notes = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $notes, $claim_id]);
        $_SESSION['success'] = 'Claim rejected.';
    } elseif ($action === 'escalate') {
        $stmt = $pdo->prepare("UPDATE claims SET status = 'escalated', moderator_notes = ? WHERE id = ?");
        $stmt->execute([$notes, $claim_id]);
        $_SESSION['success'] = 'Claim escalated for admin review.';
    }
    header('Location: moderator_claims.php');
    exit();
}

// Get pending claims
$stmt = $pdo->prepare("
    SELECT c.*, r.title as report_title, r.report_type, 
           u.full_name as claimant_name, u.email as claimant_email,
           ru.full_name as reporter_name
    FROM claims c
    JOIN reports r ON c.report_id = r.id
    JOIN users u ON c.claimant_user_id = u.id
    JOIN users ru ON r.user_id = ru.id
    WHERE c.status IN ('pending', 'under_review')
    ORDER BY c.created_at ASC
");
$stmt->execute();
$pending_claims = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderate Claims - Findex Trial</title>
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
                    <i class="fas fa-gavel text-orange-600 text-2xl mr-2"></i>
                    <span class="font-bold text-xl">Claim Moderation</span>
                </div>
                <div class="flex space-x-4">
                    <a href="moderator.php" class="text-gray-600 hover:text-orange-600">Dashboard</a>
                    <a href="moderator_reports.php" class="text-gray-600 hover:text-orange-600">Reports</a>
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
            <?php foreach ($pending_claims as $claim): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                    <?= ucfirst(str_replace('_', ' ', $claim['status'])) ?>
                                </span>
                                <span class="text-sm text-gray-500">Claim #<?= $claim['id'] ?></span>
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Claim on: <?= htmlspecialchars($claim['report_title']) ?></h3>
                            
                            <div class="grid md:grid-cols-2 gap-4 text-sm mb-4">
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="font-semibold text-gray-700 mb-2">Claimant Information</p>
                                    <p><strong>Name:</strong> <?= htmlspecialchars($claim['claimant_name']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($claim['claimant_email']) ?></p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="font-semibold text-gray-700 mb-2">Report Information</p>
                                    <p><strong>Reported by:</strong> <?= htmlspecialchars($claim['reporter_name']) ?></p>
                                    <p><strong>Type:</strong> <?= ucfirst($claim['report_type']) ?></p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <p class="font-semibold text-gray-700 mb-1">Evidence Submitted:</p>
                                <div class="bg-gray-50 p-3 rounded">
                                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($claim['evidence'])) ?></p>
                                </div>
                            </div>
                            
                            <?php if ($claim['proof_documents']): ?>
                            <div class="mb-4">
                                <p class="font-semibold text-gray-700 mb-1">Proof Documents:</p>
                                <a href="<?= htmlspecialchars($claim['proof_documents']) ?>" target="_blank" class="text-orange-600 hover:text-orange-700">
                                    <i class="fas fa-file-pdf mr-1"></i> View Document
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <form method="POST" class="mt-4 pt-4 border-t">
                        <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
                        <div class="mb-3">
                            <textarea name="notes" rows="2" class="w-full border rounded-lg p-2 text-sm" placeholder="Moderation notes..."></textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                <i class="fas fa-check mr-1"></i> Approve Claim
                            </button>
                            <button type="submit" name="action" value="reject" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                                <i class="fas fa-times mr-1"></i> Reject Claim
                            </button>
                            <button type="submit" name="action" value="escalate" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Escalate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($pending_claims)): ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-800">No pending claims!</h3>
                <p class="text-gray-600 mt-2">All claims have been processed.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>