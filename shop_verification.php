<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/upload_handler.php';
requireUserType(['shop']);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get current shop data
$stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->execute([$user_id]);
$shop = $stmt->fetch();

// Check if shop exists
if (!$shop) {
    header('Location: shop_setup.php');
    exit();
}

// Get verification status from database columns (with fallback checks)
$is_verified = (isset($shop['verified_badge']) && $shop['verified_badge'] == 1);
$is_approved = (isset($shop['is_approved']) && $shop['is_approved'] == 1);
$verification_level = $shop['verification_level'] ?? 'none';

// If already verified, show message instead of redirecting
if ($is_verified) {
    $already_verified = true;
} else {
    $already_verified = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_verification'])) {
    $business_license = $shop['business_license_image'] ?? '';
    $trade_license = $shop['trade_license_image'] ?? '';
    $id_copy = '';
    
    // Upload Business License
    if (isset($_FILES['business_license']) && $_FILES['business_license']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/verifications/business_licenses/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $result = uploadFile($_FILES['business_license'], $upload_dir, ['jpg', 'jpeg', 'png', 'pdf'], 5242880);
        if ($result['success']) {
            $business_license = $result['path'];
        } else {
            $error = 'Business license upload failed: ' . $result['error'];
        }
    } elseif (empty($business_license)) {
        $error = 'Please upload your business license.';
    }
    
    // Upload Trade License (optional)
    if (isset($_FILES['trade_license']) && $_FILES['trade_license']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/verifications/trade_licenses/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $result = uploadFile($_FILES['trade_license'], $upload_dir, ['jpg', 'jpeg', 'png', 'pdf'], 5242880);
        if ($result['success']) {
            $trade_license = $result['path'];
        }
    }
    
    // Upload ID Copy (optional)
    if (isset($_FILES['id_copy']) && $_FILES['id_copy']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/verifications/ids/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $result = uploadFile($_FILES['id_copy'], $upload_dir, ['jpg', 'jpeg', 'png', 'pdf'], 5242880);
        if ($result['success']) {
            $id_copy = $result['path'];
        }
    }
    
    if (empty($error)) {
        try {
            // Check if columns exist, if not, use only existing ones
            $columns_to_update = [];
            $params = [];
            
            // Check which columns exist in the shops table
            $stmt_check = $pdo->query("SHOW COLUMNS FROM shops");
            $existing_columns = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('business_license_image', $existing_columns)) {
                $columns_to_update[] = "business_license_image = ?";
                $params[] = $business_license;
            }
            if (in_array('trade_license_image', $existing_columns)) {
                $columns_to_update[] = "trade_license_image = ?";
                $params[] = $trade_license;
            }
            if (in_array('verification_level', $existing_columns)) {
                $columns_to_update[] = "verification_level = ?";
                $params[] = 'pending';
            }
            if (in_array('is_approved', $existing_columns)) {
                $columns_to_update[] = "is_approved = ?";
                $params[] = 0;
            }
            if (in_array('updated_at', $existing_columns)) {
                $columns_to_update[] = "updated_at = NOW()";
            }
            
            if (!empty($columns_to_update)) {
                $params[] = $user_id;
                $sql = "UPDATE shops SET " . implode(", ", $columns_to_update) . " WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute($params)) {
                    $success = 'Your verification documents have been submitted successfully! Our team will review them within 2-3 business days. You will be notified once verified.';
                    // Refresh shop data
                    $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $shop = $stmt->fetch();
                    $verification_level = 'pending';
                } else {
                    $error = 'Failed to submit verification. Please try again.';
                }
            } else {
                $error = 'Database setup incomplete. Please contact support.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Verification - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f5f5f0; }
        .file-upload-area {
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #fafafa;
        }
        .file-upload-area:hover { border-color: #f97316; background: #fff7ed; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="orange-gradient px-6 py-4 text-white">
            <h1 class="text-2xl font-bold">Get Verified</h1>
            <p class="text-orange-100 text-sm">Verify your shop to earn the trust badge</p>
        </div>
        
        <div class="p-6">
            
            <!-- ALREADY VERIFIED MESSAGE -->
            <?php if ($already_verified): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                        <div>
                            <h3 class="font-bold">Your Shop is Verified!</h3>
                            <p class="text-sm">You already have the verified badge on your shop profile.</p>
                            <a href="dashboard_shop.php" class="inline-block mt-2 text-green-700 text-sm font-semibold hover:underline">← Go to Dashboard</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
            
                <!-- PENDING APPROVAL MESSAGE -->
                <?php if (($is_approved === false || $is_approved === 0) && $verification_level !== 'pending' && !$success && !$error): ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded-lg mb-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-clock text-xl"></i>
                            <div>
                                <h3 class="font-bold">Account Pending Approval</h3>
                                <p class="text-sm">Your shop registration is pending admin approval. You will be notified once approved. This usually takes 1-2 business days.</p>
                                <p class="text-xs mt-1">You can still browse the website but cannot post reports until approved.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- PENDING VERIFICATION MESSAGE -->
                <?php if ($verification_level === 'pending' || ($is_approved && !$is_verified && !$success)): ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 px-4 py-3 rounded mb-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-clock text-xl"></i>
                            <div>
                                <h3 class="font-bold">Verification Pending</h3>
                                <p class="text-sm">Your verification documents are being reviewed by our team. You will be notified once approved.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- SUCCESS MESSAGE -->
                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-check-circle text-xl text-green-600"></i>
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($success); ?></p>
                                <a href="dashboard_shop.php" class="inline-block mt-2 text-green-700 text-sm hover:underline">← Return to Dashboard</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- ERROR MESSAGE -->
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-circle text-xl"></i>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- VERIFICATION FORM (show only if not verified and not pending) -->
                <?php if (!$is_verified && $verification_level !== 'pending' && !$success): ?>
                    <div class="bg-blue-50 p-4 rounded-lg mb-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                            <div>
                                <p class="font-semibold text-gray-800">Why Get Verified?</p>
                                <p class="text-sm text-gray-600">Verified shops receive a trust badge, appear higher in search results, and get more customer trust.</p>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Business License <span class="text-red-500">*</span></label>
                            <div class="file-upload-area" onclick="document.getElementById('business_license').click()">
                                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-600">Click to upload business license</p>
                                <p class="text-xs text-gray-400">Supported: JPG, PNG, PDF (Max 5MB)</p>
                                <input type="file" id="business_license" name="business_license" accept="image/*,.pdf" class="hidden" required>
                            </div>
                            <div id="licensePreview" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Trade License (Optional)</label>
                            <div class="file-upload-area" onclick="document.getElementById('trade_license').click()">
                                <i class="fas fa-file-alt text-2xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-600">Click to upload trade license</p>
                                <input type="file" id="trade_license" name="trade_license" accept="image/*,.pdf" class="hidden">
                            </div>
                            <div id="tradePreview" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Owner ID / Passport (Optional)</label>
                            <div class="file-upload-area" onclick="document.getElementById('id_copy').click()">
                                <i class="fas fa-id-card text-2xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-600">Click to upload ID copy</p>
                                <input type="file" id="id_copy" name="id_copy" accept="image/*,.pdf" class="hidden">
                            </div>
                            <div id="idPreview" class="mt-2"></div>
                        </div>
                        
                        <div class="bg-gray-50 p-3 rounded-lg mb-4 text-sm text-gray-600">
                            <i class="fas fa-info-circle text-orange-500 mr-2"></i>
                            Your documents are secure and will only be used for verification purposes.
                        </div>
                        
                        <button type="submit" name="submit_verification" class="orange-gradient text-white px-6 py-2 rounded-full font-semibold w-full">
                            <i class="fas fa-paper-plane mr-2"></i> Submit Verification
                        </button>
                    </form>
                <?php endif; ?>
            
            <?php endif; ?>
            
            <div class="mt-6 text-center">
                <a href="dashboard_shop.php" class="text-gray-500 hover:text-orange-600 text-sm">← Back to Dashboard</a>
            </div>
        </div>
    </div>
    
    <!-- Benefits of verification -->
    <div class="grid md:grid-cols-3 gap-4 mt-6">
        <div class="bg-white rounded-xl p-4 text-center shadow-sm">
            <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
            <h3 class="font-semibold text-sm">Trust Badge</h3>
            <p class="text-xs text-gray-500">Verified badge on your profile</p>
        </div>
        <div class="bg-white rounded-xl p-4 text-center shadow-sm">
            <i class="fas fa-chart-line text-orange-500 text-2xl mb-2"></i>
            <h3 class="font-semibold text-sm">More Visibility</h3>
            <p class="text-xs text-gray-500">Higher in search results</p>
        </div>
        <div class="bg-white rounded-xl p-4 text-center shadow-sm">
            <i class="fas fa-handshake text-blue-500 text-2xl mb-2"></i>
            <h3 class="font-semibold text-sm">Customer Trust</h3>
            <p class="text-xs text-gray-500">Build credibility with buyers</p>
        </div>
    </div>
</div>

<script>
// File input preview and validation
document.getElementById('business_license')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            this.value = '';
            document.getElementById('licensePreview').innerHTML = '';
        } else {
            document.getElementById('licensePreview').innerHTML = `<div class="text-xs text-green-600 mt-1"><i class="fas fa-check-circle"></i> ${file.name} uploaded</div>`;
        }
    }
});

document.getElementById('trade_license')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            this.value = '';
            document.getElementById('tradePreview').innerHTML = '';
        } else {
            document.getElementById('tradePreview').innerHTML = `<div class="text-xs text-green-600 mt-1"><i class="fas fa-check-circle"></i> ${file.name} uploaded</div>`;
        }
    }
});

document.getElementById('id_copy')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            this.value = '';
            document.getElementById('idPreview').innerHTML = '';
        } else {
            document.getElementById('idPreview').innerHTML = `<div class="text-xs text-green-600 mt-1"><i class="fas fa-check-circle"></i> ${file.name} uploaded</div>`;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>