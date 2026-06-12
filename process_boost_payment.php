<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/upload_handler.php';
requireLogin(); // Allow both regular users and shops

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
$boost_type = isset($_GET['type']) ? $_GET['type'] : '';

$boost_packages = [
    'basic' => ['name' => 'Basic Boost', 'price' => 50, 'duration' => 7],
    'premium' => ['name' => 'Premium Boost', 'price' => 100, 'duration' => 30],
    'featured' => ['name' => 'Featured Boost', 'price' => 200, 'duration' => 90]
];

if (!isset($boost_packages[$boost_type]) || !$report_id) {
    header('Location: boost_report.php');
    exit();
}

$package = $boost_packages[$boost_type];
$amount = $package['price'];
$duration = $package['duration'];

// Get report details and verify ownership
$stmt = $pdo->prepare("SELECT id, title, is_boosted, boost_expires FROM reports WHERE id = ? AND user_id = ? AND status = 'active'");
$stmt->execute([$report_id, $user_id]);
$report = $stmt->fetch();

if (!$report) {
    $_SESSION['error'] = 'Report not found or you do not have permission.';
    header('Location: boost_report.php');
    exit();
}

// Check if already boosted
if ($report['is_boosted'] && $report['boost_expires'] > date('Y-m-d H:i:s')) {
    $_SESSION['error'] = 'This report already has an active boost.';
    header('Location: boost_report.php');
    exit();
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $payment_method = $_POST['payment_method'] ?? '';
    $transaction_id = 'BOOST_' . time() . '_' . rand(1000, 9999);
    $receipt_path = '';
    
    if (empty($payment_method)) {
        $error = 'Please select a payment method.';
    } elseif (!isset($_FILES['payment_receipt']) || $_FILES['payment_receipt']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload your payment receipt or screenshot.';
    } else {
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
    }
    
    if (empty($error)) {
        try {
            $pdo->beginTransaction();
            
            // Record payment with boost type
            $plan_type = 'boost_' . $boost_type;
            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, amount, plan_type, transaction_id, payment_method, receipt_image, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending_review', NOW())
            ");
            $stmt->execute([$user_id, $amount, $plan_type, $transaction_id, $payment_method, $receipt_path]);
            $payment_id = $pdo->lastInsertId();
            
            // Store boost request in database for admin verification
            $stmt = $pdo->prepare("
                INSERT INTO boost_requests (payment_id, report_id, user_id, boost_type, amount, duration, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$payment_id, $report_id, $user_id, $boost_type, $amount, $duration]);
            
            $pdo->commit();
            
            $success = true;
            $success_message = "Your boost payment has been submitted successfully! Our team will review your receipt and activate the boost within 24 hours.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to process payment: ' . $e->getMessage();
        }
    }
}

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Boost Payment - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f8f9fc; }
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
        .copy-btn:hover {
            background: rgba(255,255,255,0.2);
        }
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
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-2xl mx-auto px-4 py-10">
    
    <?php if (isset($success) && $success === true): ?>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden text-center p-8">
            <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-check text-emerald-600 text-3xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Payment Submitted Successfully!</h2>
            <p class="text-gray-500 text-sm mb-5"><?php echo $success_message; ?></p>
            <div class="bg-emerald-50 rounded-xl p-3 mb-5 text-sm text-emerald-800">
                <i class="fas fa-clock mr-2"></i> Your boost will be activated once payment is verified by our team.
            </div>
            <div class="flex gap-3 justify-center">
                <a href="boost_report.php" class="inline-block px-5 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-700">Back to Boost</a>
                <a href="dashboard.php" class="inline-block px-5 py-2 bg-orange-500 text-white rounded-lg text-sm font-medium hover:bg-orange-600">Go to Dashboard</a>
            </div>
        </div>
        
    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="orange-gradient px-6 py-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold">Complete Boost Payment</h1>
                        <p class="text-orange-100 text-xs mt-1"><?php echo $package['name']; ?> - <?php echo $amount; ?> EGP</p>
                    </div>
                    <i class="fas fa-rocket text-white text-2xl"></i>
                </div>
            </div>
            
            <div class="p-6">
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-3 py-2 rounded mb-5 text-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Order Summary -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h3 class="font-semibold text-gray-800 text-sm mb-3">Order Summary</h3>
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-gray-500">Report</span>
                        <span class="font-medium"><?php echo htmlspecialchars(substr($report['title'], 0, 50)); ?></span>
                    </div>
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-gray-500">Boost Type</span>
                        <span class="font-medium"><?php echo $package['name']; ?></span>
                    </div>
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-gray-500">Duration</span>
                        <span class="font-medium"><?php echo $duration; ?> days</span>
                    </div>
                    <div class="flex justify-between text-sm py-2 mt-2 border-t border-gray-200">
                        <span class="font-semibold">Total</span>
                        <span class="font-bold text-orange-600"><?php echo $amount; ?> EGP</span>
                    </div>
                </div>
                
                <!-- Bank Details -->
                <div class="bank-detail-card rounded-xl p-4 mb-6">
                    <h3 class="font-semibold text-sm mb-3 flex items-center gap-2"><i class="fas fa-university"></i> Bank Account Details</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between items-center py-1 border-b border-white/10">
                            <span class="text-white/70">Bank Name</span>
                            <span class="font-medium">Commercial International Bank (CIB)</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-white/10">
                            <span class="text-white/70">Account Name</span>
                            <span class="font-medium">Findex Technology</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-white/10">
                            <span class="text-white/70">Account Number</span>
                            <span class="font-mono">1000 1234 5678 9012</span>
                            <button class="copy-btn text-white/50 hover:text-white text-xs ml-2" onclick="copyToClipboard('1000123456789012')"><i class="far fa-copy"></i></button>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-white/70">Amount to Pay</span>
                            <span class="font-bold text-amber-400"><?php echo $amount; ?> EGP</span>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Form -->
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-5">
                        <label class="block font-semibold text-gray-800 text-sm mb-3">Payment Method</label>
                        <div class="space-y-2">
                            <div class="payment-method-card rounded-xl p-3 flex items-center gap-3" data-method="instapay" onclick="selectMethod('instapay')">
                                <input type="radio" name="payment_method" value="instapay" id="method_instapay" class="w-4 h-4 text-orange-500" required>
                                <i class="fas fa-mobile-alt text-gray-500 w-6"></i>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800 text-sm">Instapay</div>
                                    <div class="text-xs text-gray-400">Instant bank transfer from your banking app</div>
                                </div>
                                <div class="text-green-600 hidden" id="instapay_check"><i class="fas fa-check-circle"></i></div>
                            </div>
                            
                            <div class="payment-method-card rounded-xl p-3 flex items-center gap-3" data-method="bank_transfer" onclick="selectMethod('bank_transfer')">
                                <input type="radio" name="payment_method" value="bank_transfer" id="method_bank" class="w-4 h-4 text-orange-500">
                                <i class="fas fa-university text-gray-500 w-6"></i>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800 text-sm">Bank Transfer</div>
                                    <div class="text-xs text-gray-400">Manual transfer from any bank</div>
                                </div>
                                <div class="text-green-600 hidden" id="bank_check"><i class="fas fa-check-circle"></i></div>
                            </div>
                            
                            <div class="payment-method-card rounded-xl p-3 flex items-center gap-3" data-method="vodafone_cash" onclick="selectMethod('vodafone_cash')">
                                <input type="radio" name="payment_method" value="vodafone_cash" id="method_vodafone" class="w-4 h-4 text-orange-500">
                                <i class="fas fa-mobile-alt text-gray-500 w-6"></i>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800 text-sm">Vodafone Cash</div>
                                    <div class="text-xs text-gray-400">Mobile wallet payment</div>
                                </div>
                                <div class="text-green-600 hidden" id="vodafone_check"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Receipt -->
                    <div class="mb-5">
                        <label class="block font-semibold text-gray-800 text-sm mb-2">Upload Payment Receipt / Screenshot</label>
                        <div class="upload-area" onclick="document.getElementById('payment_receipt').click()">
                            <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-600">Click to upload your payment receipt</p>
                            <p class="text-xs text-gray-400">Supported: JPG, PNG, PDF (Max 5MB)</p>
                            <input type="file" id="payment_receipt" name="payment_receipt" accept="image/*,.pdf" class="hidden" required>
                        </div>
                        <div id="receipt_preview" class="mt-2"></div>
                    </div>
                    
                    <div class="bg-amber-50 rounded-xl p-3 mb-5 text-xs text-amber-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        After making the payment, upload your receipt above. Our team will verify within 24 hours.
                    </div>
                    
                    <button type="submit" name="submit_payment" class="w-full py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl font-semibold text-sm hover:from-orange-600 hover:to-orange-700 transition">
                        <i class="fas fa-credit-card mr-2"></i> Submit Payment & Activate Boost
                    </button>
                </form>
                
                <p class="text-center text-xs text-gray-400 mt-4">
                    <i class="fas fa-lock mr-1"></i> Secure payment. Receipt will be verified within 24 hours.
                </p>
            </div>
        </div>
    <?php endif; ?>
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
                    <img src="${e.target.result}" class="w-12 h-12 object-cover rounded ml-auto">
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

// Auto-select first payment method
document.addEventListener('DOMContentLoaded', function() {
    selectMethod('instapay');
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>