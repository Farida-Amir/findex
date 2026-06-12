<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/upload_handler.php';

if (isLoggedIn()) {
    $redirect = 'dashboard_' . getUserType() . '.php';
    header("Location: $redirect");
    exit();
}

$error = '';
$success = '';
$user_type = $_GET['type'] ?? 'regular';
$step = $_GET['step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? 1;
    
    if ($step == 1) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $user_type = $_POST['user_type'] ?? 'regular';
        
        $errors = array();
        
        if (empty($email)) $errors[] = 'Email is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
        if (empty($password)) $errors[] = 'Password is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
        if (empty($full_name)) $errors[] = 'Full name is required.';
        if (empty($phone)) $errors[] = 'Phone number is required.';
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered.';
        }
        
        if (empty($errors)) {
            $_SESSION['temp_registration'] = [
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $full_name,
                'phone' => $phone,
                'user_type' => $user_type
            ];
            header("Location: register.php?type=" . urlencode($user_type) . "&step=2");
            exit();
        } else {
            $error = implode('<br>', $errors);
        }
    } 
    elseif ($step == 2) {
        $temp_data = $_SESSION['temp_registration'] ?? array();
        
        if (empty($temp_data)) {
            header("Location: register.php");
            exit();
        }
        
        if ($user_type === 'regular') {
            $national_id_number = trim($_POST['national_id_number'] ?? '');
            $errors = array();
            
            if (empty($national_id_number)) {
                $errors[] = 'National ID number is required.';
            }
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (
                            email, password_hash, full_name, phone, user_type, 
                            national_id_number, status, is_verified, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, 'active', TRUE, NOW())
                    ");
                    
                    $stmt->execute([
                        $temp_data['email'],
                        $temp_data['password'],
                        $temp_data['full_name'],
                        $temp_data['phone'],
                        'regular',
                        $national_id_number
                    ]);
                    
                    $user_id = $pdo->lastInsertId();
                    
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_type'] = 'regular';
                    $_SESSION['user_name'] = $temp_data['full_name'];
                    $_SESSION['user_email'] = $temp_data['email'];
                    
                    unset($_SESSION['temp_registration']);
                    header('Location: dashboard_user.php');
                    exit();
                    
                } catch (Exception $e) {
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            } else {
                $error = implode('<br>', $errors);
            }
        } 
        elseif ($user_type === 'shop') {
            $business_name = trim($_POST['business_name'] ?? '');
            $business_registration_number = trim($_POST['business_registration_number'] ?? '');
            $tax_id = trim($_POST['tax_id'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $governorate = trim($_POST['governorate'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $website = trim($_POST['website'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            $errors = array();
            
            if (empty($business_name)) $errors[] = 'Business name is required.';
            if (empty($business_registration_number)) $errors[] = 'Business registration number is required.';
            if (empty($address)) $errors[] = 'Business address is required.';
            if (empty($city)) $errors[] = 'City is required.';
            if (empty($governorate)) $errors[] = 'Governorate is required.';
            
            // Handle Business License upload
            $business_license_image = '';
            if (isset($_FILES['business_license_image']) && $_FILES['business_license_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/verifications/business_licenses/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $upload_result = uploadFile($_FILES['business_license_image'], $upload_dir, ['jpg', 'jpeg', 'png', 'pdf'], 5242880);
                if ($upload_result['success']) {
                    $business_license_image = $upload_result['path'];
                } else {
                    $errors[] = 'Business license upload failed: ' . $upload_result['error'];
                }
            } else {
                $errors[] = 'Please upload your business license.';
            }
            
            // Handle Trade License upload (Optional)
            $trade_license_image = '';
            if (isset($_FILES['trade_license_image']) && $_FILES['trade_license_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/verifications/trade_licenses/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $upload_result = uploadFile($_FILES['trade_license_image'], $upload_dir, ['jpg', 'jpeg', 'png', 'pdf'], 5242880);
                if ($upload_result['success']) {
                    $trade_license_image = $upload_result['path'];
                }
            }
            
            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (
                            email, password_hash, full_name, phone, user_type, status, is_verified, created_at
                        ) VALUES (?, ?, ?, ?, ?, 'active', TRUE, NOW())
                    ");
                    
                    $stmt->execute([
                        $temp_data['email'],
                        $temp_data['password'],
                        $temp_data['full_name'],
                        $temp_data['phone'],
                        'shop'
                    ]);
                    
                    $user_id = $pdo->lastInsertId();
                    
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
                        $address, $city, $governorate, $postal_code, $website, $description,
                        $business_license_image, $trade_license_image
                    ]);
                    
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_type'] = 'shop';
                    $_SESSION['user_name'] = $temp_data['full_name'];
                    $_SESSION['user_email'] = $temp_data['email'];
                    
                    unset($_SESSION['temp_registration']);
                    header('Location: dashboard_shop.php');
                    exit();
                    
                } catch (Exception $e) {
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Findex - Create Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .gold-gradient { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
        
        .step-number {
            width: 40px; height: 40px; background: #e5e7eb; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center; font-weight: bold;
            transition: all 0.3s ease;
        }
        .step.active .step-number { background: linear-gradient(135deg, #f97316, #ea580c); color: white; box-shadow: 0 4px 10px rgba(249,115,22,0.3); }
        .step.completed .step-number { background: #10b981; color: white; }
        .step-line { position: absolute; top: 20px; left: 0; right: 0; height: 2px; background: #e5e7eb; z-index: 0; }
        
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
            color: white; padding: 12px 24px; border-radius: 40px; font-weight: 600;
            transition: all 0.3s ease; border: none; cursor: pointer;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(249,115,22,0.3); }
        
        .card { background: white; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; }
        
        .file-upload-area {
            border: 2px dashed #e5e7eb; border-radius: 12px; padding: 16px;
            text-align: center; transition: all 0.3s ease; cursor: pointer;
            background: #fafafa;
        }
        .file-upload-area:hover { border-color: #f97316; background: #fff7ed; }
        
        .grid-cols-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        
        .register-container {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Register Form Container -->
<div class="register-container">
    <div class="max-w-2xl w-full">
        <div class="text-center mb-8">
            <div class="w-16 h-16 orange-gradient rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl">
                <i class="fas fa-gem text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">
                Join <span class="gradient-text">Findex</span>
            </h1>
            <p class="text-gray-500 mt-2">Identity verification required</p>
        </div>
        
        <div class="card p-8">
            <div class="relative flex justify-between mb-8">
                <div class="step-line"></div>
                <div class="step text-center relative z-10 flex-1 <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    <div class="step-number mx-auto">1</div>
                    <div class="text-xs mt-2">Basic Info</div>
                </div>
                <div class="step text-center relative z-10 flex-1 <?php echo $step >= 2 ? 'active' : ''; ?>">
                    <div class="step-number mx-auto">2</div>
                    <div class="text-xs mt-2">Verification</div>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['register_success'])): ?>
               <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm">
               <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['register_success']; unset($_SESSION['register_success']); ?>
               </div>
            <?php endif; ?>
            <?php if ($step == 1): ?>
            <form method="POST" action="">
                <input type="hidden" name="step" value="1">
                <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($user_type); ?>">
                
                <div class="mb-4"><label class="form-label">Full Name <span class="text-red-500">*</span></label><input type="text" name="full_name" required class="form-input" placeholder="Enter your full name"></div>
                <div class="mb-4"><label class="form-label">Email Address <span class="text-red-500">*</span></label><input type="email" name="email" required class="form-input" placeholder="you@example.com"></div>
                <div class="mb-4"><label class="form-label">Phone Number <span class="text-red-500">*</span></label><input type="tel" name="phone" required class="form-input" placeholder="+1 234 567 8900"></div>
                <div class="mb-4"><label class="form-label">Password <span class="text-red-500">*</span></label><input type="password" name="password" required class="form-input" placeholder="Create a strong password" id="password"><p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p></div>
                <div class="mb-6"><label class="form-label">Confirm Password <span class="text-red-500">*</span></label><input type="password" name="confirm_password" required class="form-input" placeholder="Confirm your password" id="confirm_password"></div>
                
                <div class="flex items-center mb-6"><input type="checkbox" name="terms" id="terms" required class="mr-2"><label for="terms" class="text-sm text-gray-600">I agree to the <a href="#" class="text-orange-600">Terms of Service</a></label></div>
                
                <button type="submit" class="btn-primary w-full">Continue to Verification</button>
            </form>
            <?php endif; ?>
            
            <?php if ($step == 2): ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($user_type); ?>">
                
                <?php if ($user_type === 'regular'): ?>
                    <div class="bg-orange-50 p-4 rounded-lg mb-6">
                        <div class="flex items-center"><i class="fas fa-shield-alt text-orange-600 text-xl mr-3"></i><div><h3 class="font-semibold text-sm">Identity Verification Required</h3><p class="text-xs text-gray-600">Please provide your National ID number.</p></div></div>
                    </div>
                    <div class="mb-4"><label class="form-label">National ID Number <span class="text-red-500">*</span></label><input type="text" name="national_id_number" required class="form-input" placeholder="Enter your National ID number"><p class="text-xs text-gray-500 mt-1">Passport, Driver's license, or National ID card number</p></div>
                    <button type="submit" class="btn-primary w-full">Complete Registration</button>
                    
                <?php else: ?>
                    <div class="bg-amber-50 p-4 rounded-lg mb-6">
                        <div class="flex items-center"><i class="fas fa-store text-amber-600 text-xl mr-3"></i><div><h3 class="font-semibold text-sm">Business Verification Required</h3><p class="text-xs text-gray-600">Please provide your business license and address details.</p></div></div>
                    </div>
                    
                    <div class="mb-4"><label class="form-label">Business Name <span class="text-red-500">*</span></label><input type="text" name="business_name" required class="form-input" placeholder="Enter your registered business name"></div>
                    
                    <div class="grid-cols-2 mb-4">
                        <div><label class="form-label">Business Registration Number <span class="text-red-500">*</span></label><input type="text" name="business_registration_number" required class="form-input" placeholder="Registration number"></div>
                        <div><label class="form-label">Tax ID / VAT Number</label><input type="text" name="tax_id" class="form-input" placeholder="Tax ID (if applicable)"></div>
                    </div>
                    
                    <div class="mb-4"><label class="form-label">Business Address <span class="text-red-500">*</span></label><textarea name="address" rows="2" required class="form-textarea" placeholder="Street address, building number, area"></textarea></div>
                    
                    <div class="grid-cols-2 mb-4">
                        <div><label class="form-label">City <span class="text-red-500">*</span></label><input type="text" name="city" required class="form-input" placeholder="City"></div>
                        <div><label class="form-label">Postal Code</label><input type="text" name="postal_code" class="form-input" placeholder="Postal code"></div>
                    </div>
                    
                    <div class="grid-cols-2 mb-4">
                        <div>
                            <label class="form-label">Governorate <span class="text-red-500">*</span></label>
                            <select name="governorate" required class="form-select">
                                <option value="">Select Governorate</option>
                                <option value="Cairo">Cairo</option>
                                <option value="Alexandria">Alexandria</option>
                                <option value="Giza">Giza</option>
                                <option value="Port Said">Port Said</option>
                                <option value="Suez">Suez</option>
                                <option value="Luxor">Luxor</option>
                                <option value="Aswan">Aswan</option>
                                <option value="Asyut">Asyut</option>
                                <option value="Beheira">Beheira</option>
                                <option value="Beni Suef">Beni Suef</option>
                                <option value="Dakahlia">Dakahlia</option>
                                <option value="Damietta">Damietta</option>
                                <option value="Fayoum">Fayoum</option>
                                <option value="Gharbia">Gharbia</option>
                                <option value="Ismailia">Ismailia</option>
                                <option value="Kafr El Sheikh">Kafr El Sheikh</option>
                                <option value="Matrouh">Matrouh</option>
                                <option value="Minya">Minya</option>
                                <option value="Monufia">Monufia</option>
                                <option value="New Valley">New Valley</option>
                                <option value="North Sinai">North Sinai</option>
                                <option value="Qalyubia">Qalyubia</option>
                                <option value="Qena">Qena</option>
                                <option value="Red Sea">Red Sea</option>
                                <option value="Sharqia">Sharqia</option>
                                <option value="Sohag">Sohag</option>
                                <option value="South Sinai">South Sinai</option>
                            </select>
                        </div>
                        <div><label class="form-label">Website (Optional)</label><input type="url" name="website" class="form-input" placeholder="https://www.yourshop.com"></div>
                    </div>
                    
                    <div class="mb-4"><label class="form-label">Business Description</label><textarea name="description" rows="2" class="form-textarea" placeholder="Tell us about your jewelry shop..."></textarea></div>
                    
                    <!-- Business License Upload -->
                    <div class="mb-4">
                        <label class="form-label">Business License <span class="text-red-500">*</span></label>
                        <div class="file-upload-area" onclick="document.getElementById('business_license').click()">
                            <i class="fas fa-cloud-upload-alt text-xl text-gray-400 mb-1"></i>
                            <p class="text-xs text-gray-600">Click to upload business license</p>
                            <p class="text-xs text-gray-400">Supported: JPG, PNG, PDF (Max 5MB)</p>
                            <input type="file" id="business_license" name="business_license_image" accept="image/*,.pdf" class="hidden" required>
                        </div>
                        <div id="licensePreview" class="mt-2"></div>
                    </div>
                    
                    <!-- Trade License Upload (Optional) -->
                    <div class="mb-4">
                        <label class="form-label">Trade License (Optional)</label>
                        <div class="file-upload-area" onclick="document.getElementById('trade_license').click()">
                            <i class="fas fa-file-alt text-xl text-gray-400 mb-1"></i>
                            <p class="text-xs text-gray-600">Click to upload trade license</p>
                            <p class="text-xs text-gray-400">Supported: JPG, PNG, PDF (Max 5MB)</p>
                            <input type="file" id="trade_license" name="trade_license_image" accept="image/*,.pdf" class="hidden">
                        </div>
                        <div id="tradeLicensePreview" class="mt-2"></div>
                    </div>
                    
                    <div class="bg-green-50 p-3 rounded-lg mb-4 text-center">
                        <p class="text-xs text-green-700"><i class="fas fa-info-circle mr-1"></i> Your business license will be verified by our team. You'll be notified once approved.</p>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full">Complete Registration</button>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </div>
        
        <div class="mt-6 text-center"><p class="text-gray-600 text-sm">Already have an account? <a href="login.php" class="text-orange-600 font-semibold">Sign In</a></p></div>
        
        <?php if ($step == 1): ?>
        <div class="mt-6 grid grid-cols-2 gap-3">
            <a href="?type=regular&step=1" class="text-center py-3 px-4 rounded-xl border-2 transition-all <?php echo $user_type === 'regular' ? 'border-orange-500 bg-orange-50 text-orange-600' : 'border-gray-200 bg-white text-gray-600 hover:border-orange-300'; ?>">
                <i class="fas fa-user mr-2"></i> Regular User <span class="block text-xs mt-1">With National ID</span>
            </a>
            <a href="?type=shop&step=1" class="text-center py-3 px-4 rounded-xl border-2 transition-all <?php echo $user_type === 'shop' ? 'border-amber-500 bg-amber-50 text-amber-600' : 'border-gray-200 bg-white text-gray-600 hover:border-amber-300'; ?>">
                <i class="fas fa-store mr-2"></i> Jewelry Shop <span class="block text-xs mt-1">With Business License</span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('business_license')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('licensePreview').innerHTML = `<div class="text-xs text-green-600"><i class="fas fa-check-circle mr-1"></i> Business License: ${file.name} uploaded</div>`;
    }
});

document.getElementById('trade_license')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('tradeLicensePreview').innerHTML = `<div class="text-xs text-green-600"><i class="fas fa-check-circle mr-1"></i> Trade License: ${file.name} uploaded</div>`;
    }
});

const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');
if (password && confirmPassword) {
    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords don't match");
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    password.onchange = validatePassword;
    confirmPassword.onkeyup = validatePassword;
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>