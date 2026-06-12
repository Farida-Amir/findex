<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$claim_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT c.*, r.title as report_title, r.report_type, r.user_id as report_owner_id,
           u.full_name as report_owner_name
    FROM claims c
    JOIN reports r ON c.report_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$claim_id]);
$claim = $stmt->fetch();

if (!$claim) {
    header('Location: my_reports.php');
    exit();
}

// Check if user is the claimant or the report owner
$is_claimant = ($claim['claimant_user_id'] == $_SESSION['user_id']);
$is_owner = ($claim['report_owner_id'] == $_SESSION['user_id']);

if (!$is_claimant && !$is_owner && getUserType() !== 'admin') {
    header('Location: my_reports.php');
    exit();
}

// Use created_at instead of submitted_at 
$submitted_date = $claim['created_at'] ?? $claim['submitted_at'] ?? date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Claim Details - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-8">
    
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="orange-gradient px-6 py-4 text-white">
            <h1 class="text-2xl font-bold">Claim Details</h1>
            <p class="text-orange-100 text-sm mt-1">Claim #<?php echo $claim['id']; ?> • Submitted <?php echo date('M d, Y', strtotime($submitted_date)); ?></p>
        </div>
        
        <div class="p-6">
            <!-- Status Badge -->
            <div class="mb-6">
                <?php
                $status_colors = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'under_review' => 'bg-blue-100 text-blue-800',
                    'approved' => 'bg-green-100 text-green-800',
                    'rejected' => 'bg-red-100 text-red-800',
                    'escalated' => 'bg-purple-100 text-purple-800'
                ];
                $color = $status_colors[$claim['status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold <?php echo $color; ?>">
                    <i class="fas <?php echo $claim['status'] === 'pending' ? 'fa-clock' : ($claim['status'] === 'approved' ? 'fa-check-circle' : 'fa-times-circle'); ?>"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $claim['status'])); ?>
                </span>
            </div>
            
            <!-- Claim Information -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Report Details</h3>
                    <p class="text-gray-600"><strong>Title:</strong> <?php echo htmlspecialchars($claim['report_title']); ?></p>
                    <p class="text-gray-600 mt-1"><strong>Type:</strong> <?php echo ucfirst($claim['report_type']); ?></p>
                    <p class="text-gray-600 mt-1"><strong>Report Owner:</strong> <?php echo htmlspecialchars($claim['report_owner_name']); ?></p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Claimant Information</h3>
                    <p class="text-gray-600"><strong>Name:</strong> <?php echo htmlspecialchars($claim['claimant_name']); ?></p>
                    <p class="text-gray-600 mt-1"><strong>Contact:</strong> <?php echo htmlspecialchars($claim['claimant_contact']); ?></p>
                </div>
            </div>
            
            <!-- Evidence -->
            <div class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-2">Evidence Provided</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($claim['evidence'])); ?></p>
                </div>
                <?php if ($claim['proof_documents']): ?>
                    <div class="mt-3">
                        <a href="<?php echo htmlspecialchars($claim['proof_documents']); ?>" target="_blank" class="text-orange-600 hover:text-orange-700 text-sm">
                            <i class="fas fa-file-pdf mr-1"></i> View Supporting Document
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Moderator Notes -->
            <?php if ($claim['moderator_notes']): ?>
                <div class="mb-6 bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-1">Moderator Notes</h3>
                    <p class="text-blue-700 text-sm"><?php echo nl2br(htmlspecialchars($claim['moderator_notes'])); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons (for report owners and admins) -->
            <?php if ($is_owner && $claim['status'] === 'pending'): ?>
                <div class="border-t pt-4 mt-4">
                    <h3 class="font-semibold text-gray-700 mb-3">Review This Claim</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="review_claim.php?id=<?php echo $claim_id; ?>&action=approve" class="bg-green-600 text-white px-5 py-2 rounded-full text-sm font-semibold hover:bg-green-700 transition" 
                           onclick="return confirm('Approve this claim? The item will be marked as resolved.')">
                            <i class="fas fa-check mr-1"></i> Approve Claim
                        </a>
                        <a href="review_claim.php?id=<?php echo $claim_id; ?>&action=reject" class="bg-red-600 text-white px-5 py-2 rounded-full text-sm font-semibold hover:bg-red-700 transition"
                           onclick="return confirm('Reject this claim? Provide a reason.')">
                            <i class="fas fa-times mr-1"></i> Reject Claim
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mt-6 pt-4 border-t">
                <a href="my_reports.php" class="text-gray-600 hover:text-orange-600 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to My Reports
                </a>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>