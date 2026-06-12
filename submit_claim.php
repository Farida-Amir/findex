<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

// ============================================
// ONLY ALLOW SHOP USERS TO SUBMIT CLAIMS
// ============================================
if ($_SESSION['user_type'] !== 'shop' && $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = 'Only verified jewelry shops can submit claims for security reasons. Please register as a shop to claim items.';
    header('Location: register.php?type=shop');
    exit();
}

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    header('Location: search.php');
    exit();
}

// Shop cannot claim their own report
if ($report['user_id'] == $_SESSION['user_id']) {
    $_SESSION['error'] = 'You cannot claim your own report.';
    header('Location: view_report.php?id=' . $report_id);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM claims WHERE report_id = ? AND claimant_user_id = ?");
$stmt->execute([$report_id, $_SESSION['user_id']]);
$existing_claim = $stmt->fetch();

$error = '';
$success = '';

// Get shop details for auto-fill
$shop_stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
$shop_stmt->execute([$_SESSION['user_id']]);
$shop = $shop_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claimant_name = trim($_POST['claimant_name'] ?? $_SESSION['user_name']);
    $claimant_contact = trim($_POST['claimant_contact'] ?? '');
    $evidence = trim($_POST['evidence'] ?? '');
    $shop_business_name = trim($_POST['shop_business_name'] ?? '');
    
    $proof_document = '';
    if (isset($_FILES['proof_document']) && $_FILES['proof_document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/proofs/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = time() . '_' . basename($_FILES['proof_document']['name']);
        if (move_uploaded_file($_FILES['proof_document']['tmp_name'], $upload_dir . $filename)) {
            $proof_document = $upload_dir . $filename;
        }
    }
    
// For shops, evidence is optional (they are verified businesses)
$is_shop = ($_SESSION['user_type'] === 'shop');
$is_admin = ($_SESSION['user_type'] === 'admin');

if ($is_shop || $is_admin) {
    // Shops don't need evidence - they are trusted
    $evidence = $evidence ?: 'Claim submitted by verified shop. Owner will contact claimant directly.';
} else {
    // Regular users need evidence
    if (empty($evidence)) {
        $error = 'Please provide evidence of ownership.';
    }
}

if (empty($claimant_name) || empty($claimant_contact)) {
    $error = 'Please fill in your name and contact information.';
} else {
        
        if ($stmt->execute([$report_id, $_SESSION['user_id'], $claimant_name, $claimant_contact, $evidence, $proof_document, $shop_business_name])) {
            $success = 'Your claim has been submitted successfully! The owner will review your claim.';
            
            $claim_id = $pdo->lastInsertId();
            
            // NOTIFY THE REPORT OWNER
            if (function_exists('notifyUser')) {
                notifyUser(
                    $report['user_id'],
                    'claim',
                    'New Claim from Shop',
                    "A verified shop '{$shop_business_name}' has claimed '{$report['title']}'. Please review the claim.",
                    "view_claim.php?id={$claim_id}"
                );
            }
            
            // NOTIFY THE CLAIMANT (confirmation)
            if (function_exists('notifyUser')) {
                notifyUser(
                    $_SESSION['user_id'],
                    'claim_submitted',
                    'Claim Submitted',
                    "Your shop has submitted a claim for '{$report['title']}'. The owner will review it.",
                    "view_claim.php?id={$claim_id}"
                );
            }
        } else {
            $error = 'Failed to submit claim. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Item - Findex (Shop Only)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
        .shop-badge { background: #e0e7ff; color: #3730a3; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-8">
    
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="orange-gradient px-6 py-4 text-white">
            <h1 class="text-2xl font-bold">Claim This Item</h1>
            <p class="text-orange-100 text-sm mt-1">As a verified shop, you can claim this item for your customer</p>
        </div>
        
        <!-- Shop Info Banner -->
        <div class="bg-indigo-50 p-4 m-6 rounded-lg flex items-center gap-3">
            <i class="fas fa-store text-indigo-600 text-xl"></i>
            <div>
                <p class="font-semibold text-indigo-800">Verified Shop Account</p>
                <p class="text-indigo-600 text-sm">You are claiming as a verified jewelry shop. This adds trust to your claim.</p>
            </div>
        </div>
        
        <div class="bg-amber-50 p-4 mx-6 rounded-lg">
            <h3 class="font-semibold text-gray-800">Item You're Claiming:</h3>
            <p class="text-gray-700 mt-1"><?php echo htmlspecialchars($report['title']); ?></p>
            <p class="text-gray-500 text-sm mt-1">Reported as: <?php echo ucfirst($report['report_type']); ?> • Location: <?php echo htmlspecialchars($report['location']); ?></p>
        </div>
        
        <?php if ($existing_claim): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mx-6 mb-6 rounded">
                <p class="text-yellow-800 text-sm">
                    <i class="fas fa-clock mr-2"></i> You have already submitted a claim for this item. 
                    Status: <strong><?php echo ucfirst($existing_claim['status']); ?></strong>
                </p>
                <a href="view_claim.php?id=<?php echo $existing_claim['id']; ?>" class="text-yellow-700 text-sm mt-2 inline-block">View your claim →</a>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mx-6 mb-6 rounded">
                <p class="text-green-800 text-sm"><i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?></p>
                <a href="search.php" class="text-green-700 text-sm mt-2 inline-block">← Back to Search</a>
            </div>
        <?php elseif (!$existing_claim): ?>
            <form method="POST" enctype="multipart/form-data" class="p-6">
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 p-3 mb-4 rounded">
                        <p class="text-red-700 text-sm"><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Shop Business Name *</label>
                    <input type="text" name="shop_business_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" 
                           value="<?php echo htmlspecialchars($shop['business_name'] ?? $_SESSION['user_name']); ?>">
                    <p class="text-xs text-gray-400 mt-1">This helps the item owner identify your shop</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Your Name *</label>
                    <input type="text" name="claimant_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" 
                           value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Contact Information (Phone/Email) *</label>
                    <input type="text" name="claimant_contact" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" 
                           placeholder="Phone number or email where we can reach you">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Proof of Ownership / Evidence *</label>
                    <textarea name="evidence" rows="5" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" 
                              placeholder="Describe why this item belongs to your customer. Include details like: purchase receipt, unique markings, serial numbers, photos, or any other proof..."></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Upload Supporting Document (Optional)</label>
                    <input type="file" name="proof_document" accept="image/*,.pdf" class="w-full">
                    <p class="text-xs text-gray-400 mt-1">Upload receipt, photo of the item, or any other proof (Max 5MB)</p>
                </div>
                
                <div class="bg-blue-50 p-3 rounded-lg mb-4 text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i> 
                    As a verified shop, your claim will be prioritized. The item owner will review your claim.
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="orange-gradient text-white px-6 py-2 rounded-full font-semibold">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Claim as Shop
                    </button>
                    <a href="view_report.php?id=<?php echo $report_id; ?>" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-full font-semibold hover:bg-gray-400 transition">
                        Cancel
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>