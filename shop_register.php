<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/upload_handler.php';
requireLogin();
requireUserType(['shop']);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get existing shop data
$stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->execute([$user_id]);
$shop = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name = trim($_POST['business_name'] ?? '');
    $business_registration_number = trim($_POST['business_registration_number'] ?? '');
    $tax_id = trim($_POST['tax_id'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $errors = array();
    
    if (empty($business_name)) $errors[] = 'Business name is required.';
    if (empty($business_registration_number)) $errors[] = 'Business registration number is required.';
    
    // Handle Business License upload
    $business_license_image = $shop['business_license_image'] ?? '';
    if (isset($_FILES['business_license_image']) && $_FILES['business_license_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['business_license_image'], 'uploads/verifications/business_licenses/', ['jpg', 'jpeg', 'png', 'pdf'], 5242880);
        if ($upload_result['success']) {
            $business_license_image = $upload_result['path'];
        } else {
            $errors[] = 'Business license upload failed: ' . $upload_result['error'];
        }
    }
    
    // Handle Trade License (optional)
    $trade_license_image = $shop['trade_license_image'] ?? '';
    if (isset($_FILES['trade_license_image']) && $_FILES['trade_license_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['trade_license_image'], 'uploads/verifications/trade_licenses/', ['jpg', 'jpeg', 'png', 'pdf'], 5242880);
        if ($upload_result['success']) {
            $trade_license_image = $upload_result['path'];
        }
    }
    
    if (empty($errors)) {
        if ($shop) {
            // Update existing shop
            $stmt = $pdo->prepare("
                UPDATE shops SET 
                    business_name = ?, 
                    business_registration_number = ?, 
                    tax_id = ?, 
                    address = ?, 
                    city = ?, 
                    country = ?, 
                    postal_code = ?, 
                    website = ?, 
                    description = ?,
                    business_license_image = ?,
                    trade_license_image = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $business_name, $business_registration_number, $tax_id, 
                $address, $city, $country, $postal_code, $website, $description,
                $business_license_image, $trade_license_image, $user_id
            ]);
        } else {
            // Insert new shop
            $stmt = $pdo->prepare("
                INSERT INTO shops (
                    user_id, business_name, business_registration_number, tax_id, 
                    address, city, country, postal_code, website, description, 
                    business_license_image, trade_license_image,
                    is_approved, verified_badge, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE, FALSE, NOW())
            ");
            $stmt->execute([
                $user_id, $business_name, $business_registration_number, $tax_id, 
                $address, $city, $country, $postal_code, $website, $description,
                $business_license_image, $trade_license_image
            ]);
        }
        
        $success = 'Shop information saved successfully! Our team will review your application.';
        
        // Refresh shop data
        $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $shop = $stmt->fetch();
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Findex - Shop Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .gold-gradient { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
        
        .form-input, .form-select, .form-textarea {
            width: 100%; padding: 12px 16px; border: 1.5px solid #e5e7eb; border-radius: 12px;
            font-size: 14px; transition: all 0.2s ease; background: white;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #1f2937; margin-bottom: 6px; }
        
        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white; padding: 12px 28px; border-radius: 40px; font-weight: 600;
            transition: all 0.3s ease; border: none; cursor: pointer;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(249,115,22,0.3); }
        
        .card { background: white; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; }
        
        .file-upload-area {
            border: 2px dashed #e5e7eb; border-radius: 12px; padding: 20px;
            text-align: center; transition: all 0.3s ease; cursor: pointer;
            background: #fafafa;
        }
        .file-upload-area:hover { border-color: #f97316; background: #fff7ed; }
        .uploaded-file { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 8px 12px; margin-top: 10px; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-8">
    
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="orange-gradient w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl">
            <i class="fas fa-store text-white text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Jewelry Shop Registration</h1>
        <p class="text-gray-500 text-sm mt-1">Register your business to join our trusted network</p>
    </div>

    <div class="card">
        <!-- Status Banner -->
        <?php if ($shop && $shop['is_approved']): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mx-6 mt-6 rounded-lg">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    <div>
                        <p class="font-semibold text-green-800">Your shop is verified!</p>
                        <p class="text-sm text-green-700">You now have access to all shop features.</p>
                    </div>
                </div>
            </div>
        <?php elseif ($shop && $shop['business_name']): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mx-6 mt-6 rounded-lg">
                <div class="flex items-center gap-3">
                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                    <div>
                        <p class="font-semibold text-yellow-800">Application Submitted</p>
                        <p class="text-sm text-yellow-700">Your shop registration is pending review. We'll notify you once approved.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mx-6 mt-6 rounded-lg">
                <i class="fas fa-check-circle text-green-500 mr-2"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mx-6 mt-6 rounded-lg">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Registration Form - WITH FILE UPLOAD -->
        <form method="POST" action="" enctype="multipart/form-data" class="p-6">
            <div class="grid md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="form-label">Business Name <span class="text-red-500">*</span></label>
                    <input type="text" name="business_name" required class="form-input" 
                           value="<?php echo htmlspecialchars($shop['business_name'] ?? ''); ?>" 
                           placeholder="Enter your registered business name">
                </div>
                
                <div>
                    <label class="form-label">Business Registration Number <span class="text-red-500">*</span></label>
                    <input type="text" name="business_registration_number" required class="form-input" 
                           value="<?php echo htmlspecialchars($shop['business_registration_number'] ?? ''); ?>" 
                           placeholder="Enter business registration number">
                    <p class="text-xs text-gray-400 mt-1">As shown on your business license</p>
                </div>
                
                <div>
                    <label class="form-label">Tax ID / VAT Number</label>
                    <input type="text" name="tax_id" class="form-input" 
                           value="<?php echo htmlspecialchars($shop['tax_id'] ?? ''); ?>" 
                           placeholder="Enter tax ID (if applicable)">
                </div>
                
                <div class="md:col-span-2">
                    <label class="form-label">Business Address</label>
                    <textarea name="address" rows="2" class="form-textarea" 
                              placeholder="Enter your business address"><?php echo htmlspecialchars($shop['address'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-input" 
                           value="<?php echo htmlspecialchars($shop['city'] ?? ''); ?>" 
                           placeholder="City">
                </div>
                
                <div>
                    <label class="form-label">Postal Code</label>
                    <input type="text" name="postal_code" class="form-input" 
                           value="<?php echo htmlspecialchars($shop['postal_code'] ?? ''); ?>" 
                           placeholder="Postal code">
                </div>
                
                <div>
                    <label class="form-label">Country</label>
                    <select name="country" class="form-select">
                        <option value="">Select Country</option>
                        <option value="Egypt" <?php echo ($shop['country'] ?? '') === 'Egypt' ? 'selected' : ''; ?>>Egypt</option>
                        <option value="UAE" <?php echo ($shop['country'] ?? '') === 'UAE' ? 'selected' : ''; ?>>United Arab Emirates</option>
                        <option value="Saudi Arabia" <?php echo ($shop['country'] ?? '') === 'Saudi Arabia' ? 'selected' : ''; ?>>Saudi Arabia</option>
                        <option value="Kuwait" <?php echo ($shop['country'] ?? '') === 'Kuwait' ? 'selected' : ''; ?>>Kuwait</option>
                        <option value="Qatar" <?php echo ($shop['country'] ?? '') === 'Qatar' ? 'selected' : ''; ?>>Qatar</option>
                    </select>
                </div>
                
                <div>
                    <label class="form-label">Website (Optional)</label>
                    <input type="url" name="website" class="form-input" 
                           value="<?php echo htmlspecialchars($shop['website'] ?? ''); ?>" 
                           placeholder="https://www.yourshop.com">
                </div>
                
                <div class="md:col-span-2">
                    <label class="form-label">Business Description</label>
                    <textarea name="description" rows="3" class="form-textarea" 
                              placeholder="Tell us about your jewelry shop..."><?php echo htmlspecialchars($shop['description'] ?? ''); ?></textarea>
                </div>
                
                <!-- Business License Upload -->
                <div>
                    <label class="form-label">Business License <span class="text-red-500">*</span></label>
                    <div class="file-upload-area" onclick="document.getElementById('business_license').click()">
                        <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600">Click to upload business license</p>
                        <p class="text-xs text-gray-400 mt-1">Supported: JPG, PNG, PDF (Max 5MB)</p>
                        <input type="file" id="business_license" name="business_license_image" accept="image/*,.pdf" class="hidden" required="<?php echo empty($shop['business_license_image']) ? 'required' : ''; ?>">
                    </div>
                    <?php if (!empty($shop['business_license_image'])): ?>
                        <div class="uploaded-file mt-2">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            <span class="text-sm text-gray-600">License uploaded</span>
                            <a href="<?php echo htmlspecialchars($shop['business_license_image']); ?>" target="_blank" class="text-orange-600 text-sm ml-2">View</a>
                        </div>
                    <?php endif; ?>
                    <div id="licensePreview" class="mt-2 hidden"></div>
                </div>
                
                <!-- Trade License Upload -->
                <div>
                    <label class="form-label">Trade License (Optional)</label>
                    <div class="file-upload-area" onclick="document.getElementById('trade_license').click()">
                        <i class="fas fa-file-alt text-2xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600">Click to upload trade license</p>
                        <input type="file" id="trade_license" name="trade_license_image" accept="image/*,.pdf" class="hidden">
                    </div>
                    <?php if (!empty($shop['trade_license_image'])): ?>
                        <div class="uploaded-file mt-2">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            <span class="text-sm text-gray-600">Trade license uploaded</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-6 pt-4 border-t flex justify-end">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Save Shop Information
                </button>
            </div>
        </form>
    </div>

    <!-- Benefits Section -->
    <div class="grid md:grid-cols-3 gap-5 mt-8">
        <div class="bg-white rounded-xl p-5 text-center shadow-sm border border-gray-100">
            <div class="w-12 h-12 orange-gradient rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-check-circle text-white text-lg"></i>
            </div>
            <h3 class="font-semibold text-sm">Verified Badge</h3>
            <p class="text-xs text-gray-500 mt-1">Get a verified badge on your profile</p>
        </div>
        <div class="bg-white rounded-xl p-5 text-center shadow-sm border border-gray-100">
            <div class="w-12 h-12 gold-gradient rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-chart-line text-white text-lg"></i>
            </div>
            <h3 class="font-semibold text-sm">Analytics Dashboard</h3>
            <p class="text-xs text-gray-500 mt-1">Track your reports and engagement</p>
        </div>
        <div class="bg-white rounded-xl p-5 text-center shadow-sm border border-gray-100">
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-handshake text-gray-600 text-lg"></i>
            </div>
            <h3 class="font-semibold text-sm">Trusted Network</h3>
            <p class="text-xs text-gray-500 mt-1">Connect with verified partners</p>
        </div>
    </div>
</div>

<script>
// File upload preview
document.getElementById('business_license')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const preview = document.getElementById('licensePreview');
        preview.innerHTML = `<div class="uploaded-file"><i class="fas fa-file text-orange-500 mr-1"></i> ${file.name}</div>`;
        preview.classList.remove('hidden');
    }
});
</script>

</body>
</html>