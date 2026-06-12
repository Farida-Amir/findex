<?php
/**
 * Manage Claim - For Shop Owners and Moderators
 * Review, approve, or reject claims on reports
 */

require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';
requireLogin();

$claim_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$claim_id) {
    $_SESSION['error'] = 'Invalid claim ID.';
    header('Location: ' . ($_SESSION['user_type'] === 'shop' ? 'dashboard_shop.php' : 'moderator_reports.php'));
    exit();
}

// Get claim details with report and claimant info
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        r.id as report_id,
        r.title as report_title,
        r.description as report_description,
        r.user_id as report_owner_id,
        u.full_name as claimant_name,
        u.email as claimant_email,
        u.phone as claimant_phone,
        u2.full_name as report_owner_name,
        u2.email as report_owner_email
    FROM claims c
    JOIN reports r ON c.report_id = r.id
    JOIN users u ON c.claimant_user_id = u.id
    JOIN users u2 ON r.user_id = u2.id
    WHERE c.id = ?
");
$stmt->execute([$claim_id]);
$claim = $stmt->fetch();

if (!$claim) {
    $_SESSION['error'] = 'Claim not found.';
    header('Location: dashboard_shop.php');
    exit();
}

// Check permission: Only report owner (shop) or moderator/admin can manage claim
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$can_manage = false;

if ($user_type === 'admin' || $user_type === 'moderator') {
    $can_manage = true;
} elseif ($user_type === 'shop' && $claim['report_owner_id'] == $user_id) {
    $can_manage = true;
}

if (!$can_manage) {
    $_SESSION['error'] = 'You do not have permission to manage this claim.';
    header('Location: dashboard_shop.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $status = '';
    $response_message = trim($_POST['response_message'] ?? '');
    
    if ($action === 'approve') {
        $status = 'approved';
        $_SESSION['success'] = 'Claim approved successfully. The claimant has been notified.';
        
        // Send email notification
        sendClaimStatusEmail(
            $claim['claimant_email'],
            $claim['claimant_name'],
            $claim['report_title'],
            'approved',
            $response_message
        );
        
        // Also update report status if needed
        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$claim['report_id']]);
        
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $_SESSION['success'] = 'Claim rejected. Reason has been sent to the claimant.';
        
        // Send email notification
        sendClaimStatusEmail(
            $claim['claimant_email'],
            $claim['claimant_name'],
            $claim['report_title'],
            'rejected',
            $response_message ?: "The shop has rejected your claim. Please contact support for more information."
        );
    } else {
        $_SESSION['error'] = 'Invalid action.';
        header('Location: manage_claim.php?id=' . $claim_id);
        exit();
    }
    
    // Update claim status
    $stmt = $pdo->prepare("
        UPDATE claims 
        SET status = ?, admin_notes = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$status, $response_message, $claim_id]);
    
    // Create notification for claimant
    $notification_title = $status === 'approved' ? 'Claim Approved! 🎉' : 'Claim Update';
    $notification_message = $status === 'approved' 
        ? "Your claim for '{$claim['report_title']}' has been approved! Contact the shop to arrange pickup."
        : "Your claim for '{$claim['report_title']}' has been reviewed. Check your email for details.";
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, link, created_at)
        VALUES (?, 'claim_status', ?, ?, ?, NOW())
    ");
    $stmt->execute([$claim['claimant_user_id'], $notification_title, $notification_message, "view_claim.php?id=" . $claim_id]);
    
    // Redirect based on user type
    if ($user_type === 'admin' || $user_type === 'moderator') {
        header('Location: moderator_claims.php');
    } else {
        header('Location: dashboard_shop.php');
    }
    exit();
}

// Get any supporting documents for this claim
$stmt = $pdo->prepare("
    SELECT * FROM claim_documents 
    WHERE claim_id = ? 
    ORDER BY uploaded_at DESC
");
$stmt->execute([$claim_id]);
$documents = $stmt->fetchAll();

// Get timeline of claim (status changes)
$stmt = $pdo->prepare("
    SELECT * FROM claim_status_history 
    WHERE claim_id = ? 
    ORDER BY changed_at DESC
");
$stmt->execute([$claim_id]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Manage Claim - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f5f5f0; }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .info-card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-6 md:py-8">
    
    <!-- Page Header -->
    <div class="mb-6">
        <a href="<?php echo ($user_type === 'admin' || $user_type === 'moderator') ? 'moderator_claims.php' : 'dashboard_shop.php'; ?>" 
           class="text-orange-600 hover:text-orange-700 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Manage Claim</h1>
        <p class="text-gray-500 mt-1">Review claim details and make a decision</p>
    </div>

    <!-- Status Banner -->
    <?php if ($claim['status'] !== 'pending'): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo $claim['status'] === 'approved' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
        <div class="flex items-center gap-3">
            <i class="fas <?php echo $claim['status'] === 'approved' ? 'fa-check-circle text-green-600' : 'fa-times-circle text-red-600'; ?> text-2xl"></i>
            <div>
                <p class="font-semibold <?php echo $claim['status'] === 'approved' ? 'text-green-800' : 'text-red-800'; ?>">
                    This claim has been <?php echo $claim['status']; ?>
                </p>
                <?php if ($claim['admin_notes']): ?>
                    <p class="text-sm text-gray-600 mt-1">Note: <?php echo htmlspecialchars($claim['admin_notes']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Left Column - Claim Details -->
        <div class="lg:col-span-2">
            
            <!-- Claim Information -->
            <div class="info-card">
                <h2 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-file-alt text-orange-500 mr-2"></i> Claim Information
                </h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Claim ID</p>
                            <p class="font-medium">#<?php echo $claim['id']; ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Submitted On</p>
                            <p class="font-medium"><?php echo date('F j, Y g:i A', strtotime($claim['created_at'])); ?></p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Status</p>
                        <span class="status-badge status-<?php echo $claim['status']; ?> mt-1 inline-block">
                            <?php echo ucfirst($claim['status']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Report Details -->
            <div class="info-card">
                <h2 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-gem text-orange-500 mr-2"></i> Original Report
                </h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Report Title</p>
                        <p class="font-medium text-lg"><?php echo htmlspecialchars($claim['report_title']); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Description</p>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($claim['report_description'])); ?></p>
                    </div>
                    <div>
                        <a href="view_report.php?id=<?php echo $claim['report_id']; ?>" class="text-orange-600 hover:text-orange-700 text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i> View Full Report
                        </a>
                    </div>
                </div>
            </div>

            <!-- Claimant Information -->
            <div class="info-card">
                <h2 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-user-circle text-orange-500 mr-2"></i> Claimant Details
                </h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Full Name</p>
                            <p class="font-medium"><?php echo htmlspecialchars($claim['claimant_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Email</p>
                            <p class="font-medium"><?php echo htmlspecialchars($claim['claimant_email']); ?></p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Phone</p>
                        <p class="font-medium"><?php echo htmlspecialchars($claim['claimant_phone'] ?: 'Not provided'); ?></p>
                    </div>
                    <?php if ($claim['claimant_statement']): ?>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Claimant's Statement</p>
                        <div class="bg-gray-50 p-4 rounded-lg mt-1">
                            <?php echo nl2br(htmlspecialchars($claim['claimant_statement'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Supporting Documents -->
            <?php if (!empty($documents)): ?>
            <div class="info-card">
                <h2 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-paperclip text-orange-500 mr-2"></i> Supporting Documents
                </h2>
                <div class="space-y-2">
                    <?php foreach ($documents as $doc): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-file-<?php echo $doc['file_type'] === 'image' ? 'image' : 'alt'; ?> text-gray-500"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($doc['file_name']); ?></span>
                        </div>
                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-orange-600 text-sm">
                            <i class="fas fa-download"></i> View
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Decision Form (Only if pending) -->
        <div class="lg:col-span-1">
            <?php if ($claim['status'] === 'pending'): ?>
            <div class="info-card sticky top-24">
                <h2 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-gavel text-orange-500 mr-2"></i> Make Decision
                </h2>
                
                <form method="POST" action="" onsubmit="return confirmDecision()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Response Message (Optional)</label>
                            <textarea name="response_message" rows="4" 
                                      class="w-full border border-gray-300 rounded-lg p-3 focus:ring-orange-500 focus:border-orange-500"
                                      placeholder="Add a note for the claimant..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">This will be included in the email notification.</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <button type="submit" name="action" value="approve" 
                                    class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition flex items-center justify-center gap-2">
                                <i class="fas fa-check-circle"></i> Approve
                            </button>
                            <button type="submit" name="action" value="reject" 
                                    class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition flex items-center justify-center gap-2">
                                <i class="fas fa-times-circle"></i> Reject
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="mt-6 p-3 bg-amber-50 rounded-lg border border-amber-200">
                    <p class="text-xs text-amber-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Guidelines:</strong> Approve only if the claimant provides valid proof of ownership. 
                        Reject if the claim is fraudulent or lacks evidence.
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div class="info-card">
                <h2 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-history text-orange-500 mr-2"></i> Timeline
                </h2>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="font-medium text-sm">Claim Submitted</p>
                            <p class="text-xs text-gray-500"><?php echo date('M d, Y g:i A', strtotime($claim['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-gavel text-orange-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="font-medium text-sm">Status Changed to <?php echo ucfirst($claim['status']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('M d, Y g:i A', strtotime($claim['updated_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Contact Shop/Claimant Section -->
<div class="max-w-7xl mx-auto px-4 pb-8">
    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold mb-3 flex items-center">
            <i class="fas fa-phone-alt text-orange-500 mr-2"></i> Need More Information?
        </h3>
        <div class="flex flex-wrap gap-4">
            <?php if ($user_type === 'shop' || $user_type === 'admin' || $user_type === 'moderator'): ?>
            <a href="mailto:<?php echo $claim['claimant_email']; ?>" class="text-orange-600 hover:text-orange-700">
                <i class="fas fa-envelope mr-1"></i> Contact Claimant
            </a>
            <?php endif; ?>
            <?php if ($claim['report_owner_id'] != $user_id && ($user_type === 'admin' || $user_type === 'moderator')): ?>
            <a href="mailto:<?php echo $claim['report_owner_email']; ?>" class="text-orange-600 hover:text-orange-700">
                <i class="fas fa-store mr-1"></i> Contact Shop Owner
            </a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>support.php" class="text-gray-500 hover:text-gray-600">
                <i class="fas fa-headset mr-1"></i> Contact Support
            </a>
        </div>
    </div>
</div>

<script>
function confirmDecision() {
    let action = event.submitter.value;
    if (action === 'approve') {
        return confirm('Are you sure you want to APPROVE this claim?\n\nThe claimant will be notified and the report will be marked as resolved.');
    } else if (action === 'reject') {
        return confirm('Are you sure you want to REJECT this claim?\n\nThe claimant will be notified of your decision.');
    }
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>