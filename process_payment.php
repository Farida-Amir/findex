<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/upload_handler.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$plan = isset($_GET['plan']) ? $_GET['plan'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$amount_param = isset($_GET['amount']) ? (int)$_GET['amount'] : 0;

$prices = [
    'boost_basic' => 50,
    'boost_premium' => 100,
    'boost_featured' => 200,
    'subscription_basic' => 250,
    'subscription_premium' => 400,
    'ai_service' => 50,
];

// Determine amount based on plan or type
if ($plan && in_array($plan, ['basic', 'premium'])) {
    $subscription_key = 'subscription_' . $plan;
    $amount = isset($prices[$subscription_key]) ? $prices[$subscription_key] : 0;
    $payment_type = 'subscription';
} elseif ($type && isset($prices[$type])) {
    $amount = $prices[$type];
    $payment_type = $type;
} elseif ($amount_param > 0) {
    $amount = $amount_param;
    $payment_type = 'general';
} else {
    header('Location: pricing.php');
    exit();
}

// Validate amount is not zero
if ($amount <= 0) {
    header('Location: pricing.php?error=invalid_amount');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = getUserType();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get shop data if shop user
$shop = null;
if ($user_type === 'shop') {
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $shop = $stmt->fetch();
}

$error = '';
$success = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $payment_method = $_POST['payment_method'] ?? '';
        $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);
        $receipt_path = '';
        
        // Validate payment method
        if (empty($payment_method)) {
            $error = 'Please select a payment method.';
        }
        
        // Upload payment receipt/screenshot
        if (empty($error) && isset($_FILES['payment_receipt']) && $_FILES['payment_receipt']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/payments/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $result = uploadFile($_FILES['payment_receipt'], $upload_dir, ['jpg', 'jpeg', 'png', 'pdf'], 5242880);
            if ($result['success']) {
                $receipt_path = $result['path'];
            } else {
                $error = 'Failed to upload receipt: ' . $result['error'];
            }
        } elseif (empty($error)) {
            $error = 'Please upload your payment receipt or screenshot.';
        }
        
        if (empty($error)) {
            try {
                $pdo->beginTransaction();
                
                // Record payment
                $stmt = $pdo->prepare("
                    INSERT INTO payments (user_id, amount, plan_type, transaction_id, payment_method, receipt_image, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending_review', NOW())
                ");
                $stmt->execute([$user_id, $amount, $plan ?: $payment_type, $transaction_id, $payment_method, $receipt_path]);
                $payment_id = $pdo->lastInsertId();
                
                // If subscription, update shop table (FIXED - prevents duplicate error)
                if ($plan && in_array($plan, ['basic', 'premium'])) {
                    $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // FIRST, check if shop exists using a direct query
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM shops WHERE user_id = ?");
                    $check_stmt->execute([$user_id]);
                    $shop_exists = $check_stmt->fetchColumn();
                    
                    if ($shop_exists > 0) {
                        // UPDATE existing record
                        $stmt = $pdo->prepare("
                            UPDATE shops SET 
                                subscription_plan = ?,
                                subscription_expires_at = ?,
                                verified_badge = 1,
                                verification_level = 'pending',
                                updated_at = NOW()
                            WHERE user_id = ?
                        ");
                        $stmt->execute([$plan, $expiry_date, $user_id]);
                    } else {
                        // INSERT new record only if it doesn't exist
                        $business_name = $user['full_name'] ?? 'Business Name';
                        $stmt = $pdo->prepare("
                            INSERT INTO shops (user_id, business_name, subscription_plan, subscription_expires_at, verified_badge, verification_level, created_at)
                            VALUES (?, ?, ?, ?, 1, 'pending', NOW())
                        ");
                        $stmt->execute([$user_id, $business_name, $plan, $expiry_date]);
                    }
                }
                
                $pdo->commit();
                
                // Set success message
                $_SESSION['payment_success'] = true;
                $_SESSION['payment_id'] = $payment_id;
                $_SESSION['payment_amount'] = $amount;
                $_SESSION['payment_type'] = $plan ? 'subscription' : $payment_type;
                
                // Redirect to success page
                header("Location: payment_success.php?payment_id=" . $payment_id . "&type=" . ($plan ? 'subscription' : $payment_type) . "&amount=" . $amount);
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to process payment: ' . $e->getMessage();
            }
        }
    }
}

// Generate CSRF token for security
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f7f6f4; }
        .payment-method-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid #e5e7eb;
        }
        .payment-method-card:hover {
            border-color: #f97316;
            background: #fffaf5;
        }
        .payment-method-card.selected {
            border-color: #f97316;
            background: #fffaf5;
            box-shadow: 0 4px 15px rgba(249,115,22,0.1);
        }
        .upload-area {
            border: 2px dashed #e5e7eb;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
            background: #fafafa;
        }
        .upload-area:hover {
            border-color: #f97316;
            background: #fffaf5;
        }
        .bank-detail-card {
            background: linear-gradient(135deg, #0f0f23, #1a1a2e);
            color: white;
        }
        .copy-btn {
            transition: all 0.2s;
        }
        .copy-btn:hover {
            background: rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-3xl mx-auto px-4 py-12">
    
    <!-- Payment Form -->
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="orange-gradient px-8 py-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Complete Your Payment</h1>
                    <p class="text-orange-100 text-sm mt-1">
                        <?php 
                        if ($plan === 'basic') echo 'Basic Plan - 250 EGP / month';
                        elseif ($plan === 'premium') echo 'Premium Plan - 400 EGP / month';
                        elseif ($type === 'boost_basic') echo 'Basic Boost - 50 EGP';
                        elseif ($type === 'boost_premium') echo 'Premium Boost - 100 EGP';
                        elseif ($type === 'boost_featured') echo 'Featured Boost - 200 EGP';
                        elseif ($type === 'ai_service') echo 'AI Service - 50 EGP';
                        else echo 'Payment - ' . $amount . ' EGP';
                        ?>
                    </p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-lock text-white text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="p-8">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Order Summary -->
            <div class="bg-gray-50 rounded-xl p-5 mb-8">
                <h3 class="font-semibold text-gray-800 mb-3">Order Summary</h3>
                <div class="flex justify-between py-2">
                    <span class="text-gray-600">Item</span>
                    <span class="font-semibold">
                        <?php 
                        if ($plan === 'basic') echo 'Basic Subscription (250 EGP/month)';
                        elseif ($plan === 'premium') echo 'Premium Subscription (400 EGP/month)';
                        elseif ($type === 'boost_basic') echo 'Basic Boost';
                        elseif ($type === 'boost_premium') echo 'Premium Boost';
                        elseif ($type === 'boost_featured') echo 'Featured Boost';
                        else echo 'Payment';
                        ?>
                    </span>
                </div>
                <div class="flex justify-between py-2 border-t border-gray-200">
                    <span class="text-gray-600">Price</span>
                    <span class="font-semibold text-orange-600"><?php echo $amount; ?> EGP</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-600">Tax</span>
                    <span class="font-semibold">0 EGP</span>
                </div>
                <div class="flex justify-between py-3 mt-2 border-t-2 border-gray-200">
                    <span class="font-bold text-gray-800">Total Due Today</span>
                    <span class="font-bold text-orange-600 text-xl"><?php echo $amount; ?> EGP</span>
                </div>
            </div>
            
            <!-- Bank Account Details -->
            <div class="bank-detail-card rounded-xl p-5 mb-8">
                <h3 class="font-semibold mb-3 flex items-center gap-2"><i class="fas fa-university"></i> Bank Account Details</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center py-2 border-b border-white/10">
                        <span class="text-white/70">Bank Name</span>
                        <span class="font-semibold">Commercial International Bank (CIB)</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-white/10">
                        <span class="text-white/70">Account Name</span>
                        <span class="font-semibold">Findex Technology</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-white/10">
                        <span class="text-white/70">Account Number</span>
                        <span class="font-semibold font-mono">1000 1234 5678 9012</span>
                        <button class="copy-btn text-white/50 hover:text-white text-sm ml-2" onclick="copyToClipboard('1000123456789012')"><i class="far fa-copy"></i></button>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-white/70">Amount to Pay</span>
                        <span class="font-bold text-amber-400 text-lg"><?php echo $amount; ?> EGP</span>
                    </div>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <h3 class="font-semibold text-gray-800 mb-3">Payment Method</h3>
                
                <div class="space-y-3 mb-6">
                    <div class="payment-method-card rounded-xl p-4 flex items-center gap-4" data-method="instapay" onclick="selectMethod('instapay')">
                        <input type="radio" name="payment_method" value="instapay" id="method_instapay" class="w-5 h-5 text-orange-500" required>
                        <i class="fas fa-mobile-alt text-2xl text-gray-500 w-10"></i>
                        <div class="flex-1">
                            <div class="font-medium text-gray-800">Instapay</div>
                            <div class="text-xs text-gray-400">Instant bank transfer from your banking app</div>
                        </div>
                        <div class="text-green-600 hidden" id="instapay_check"><i class="fas fa-check-circle"></i></div>
                    </div>
                    
                    <div class="payment-method-card rounded-xl p-4 flex items-center gap-4" data-method="bank_transfer" onclick="selectMethod('bank_transfer')">
                        <input type="radio" name="payment_method" value="bank_transfer" id="method_bank" class="w-5 h-5 text-orange-500">
                        <i class="fas fa-university text-2xl text-gray-500 w-10"></i>
                        <div class="flex-1">
                            <div class="font-medium text-gray-800">Bank Transfer</div>
                            <div class="text-xs text-gray-400">Manual transfer from any bank</div>
                        </div>
                        <div class="text-green-600 hidden" id="bank_check"><i class="fas fa-check-circle"></i></div>
                    </div>
                    
                    <div class="payment-method-card rounded-xl p-4 flex items-center gap-4" data-method="vodafone_cash" onclick="selectMethod('vodafone_cash')">
                        <input type="radio" name="payment_method" value="vodafone_cash" id="method_vodafone" class="w-5 h-5 text-orange-500">
                        <i class="fas fa-mobile-alt text-2xl text-gray-500 w-10"></i>
                        <div class="flex-1">
                            <div class="font-medium text-gray-800">Vodafone Cash</div>
                            <div class="text-xs text-gray-400">Mobile wallet payment</div>
                        </div>
                        <div class="text-green-600 hidden" id="vodafone_check"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                
                <!-- Upload Payment Receipt -->
                <div class="mb-6">
                    <label class="block font-semibold text-gray-800 mb-3">Upload Payment Receipt / Screenshot</label>
                    <div class="upload-area" onclick="document.getElementById('payment_receipt').click()">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-3"></i>
                        <p class="text-sm text-gray-600">Click to upload your payment receipt or screenshot</p>
                        <p class="text-xs text-gray-400 mt-1">Supported: JPG, PNG, PDF (Max 5MB)</p>
                        <input type="file" id="payment_receipt" name="payment_receipt" accept="image/*,.pdf" class="hidden" required>
                    </div>
                    <div id="receipt_preview" class="mt-2"></div>
                    <p class="text-xs text-gray-400 mt-2"><i class="fas fa-info-circle"></i> Please upload a clear screenshot or photo of your payment confirmation</p>
                </div>
                
                <div class="bg-amber-50 rounded-xl p-4 mb-6 text-sm text-amber-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    After making the payment, upload your receipt above. Our team will verify within 24 hours.
                </div>
                
                <button type="submit" name="submit_payment" class="w-full py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl font-semibold hover:from-orange-600 hover:to-orange-700 transition">
                    Submit Payment & Receipt
                </button>
            </form>
            
            <div class="text-center mt-6">
                <a href="<?php echo $plan ? 'pricing.php' : 'boost_report.php'; ?>" class="text-gray-400 hover:text-orange-600 text-sm">← Back</a>
            </div>
        </div>
    </div>
    
    <!-- Instructions -->
    <div class="bg-white rounded-xl p-5 mt-6 shadow-sm">
        <h3 class="font-semibold text-gray-800 mb-2">How to complete your payment:</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
            <li>Transfer <strong><?php echo $amount; ?> EGP</strong> to the bank account above using Instapay or bank transfer</li>
            <li>Take a screenshot or photo of the transaction confirmation</li>
            <li>Upload the receipt using the upload button above</li>
            <li>Click "Submit Payment & Receipt"</li>
            <li>Our team will verify your payment within 24 hours</li>
        </ol>
    </div>
</div>

<script>
function selectMethod(method) {
    // Reset all
    document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('selected');
        card.style.borderColor = '#e5e7eb';
    });
    document.querySelectorAll('.text-green-600').forEach(el => {
        el.classList.add('hidden');
    });
    
    // Select chosen method
    const selectedCard = document.querySelector(`.payment-method-card[data-method="${method}"]`);
    selectedCard.classList.add('selected');
    selectedCard.style.borderColor = '#f97316';
    
    // Check radio button
    let radioId = '';
    if (method === 'instapay') radioId = 'method_instapay';
    else if (method === 'bank_transfer') radioId = 'method_bank';
    else if (method === 'vodafone_cash') radioId = 'method_vodafone';
    
    if (radioId) {
        document.getElementById(radioId).checked = true;
    }
    
    // Show checkmark
    let checkId = '';
    if (method === 'instapay') checkId = 'instapay_check';
    else if (method === 'bank_transfer') checkId = 'bank_check';
    else if (method === 'vodafone_cash') checkId = 'vodafone_check';
    
    if (checkId) {
        document.getElementById(checkId).classList.remove('hidden');
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    alert('Account number copied to clipboard!');
}

// Preview uploaded receipt
document.getElementById('payment_receipt')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const preview = document.getElementById('receipt_preview');
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<div class="mt-2 p-2 bg-green-50 rounded-lg flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span class="text-sm text-green-700">${file.name} uploaded</span>
                    <img src="${e.target.result}" class="w-16 h-16 object-cover rounded ml-auto">
                </div>`;
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = `<div class="mt-2 p-2 bg-green-50 rounded-lg flex items-center gap-3">
                <i class="fas fa-file-pdf text-red-500"></i>
                <span class="text-sm text-green-700">${file.name} uploaded</span>
            </div>`;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>