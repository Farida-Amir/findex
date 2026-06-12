<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$payment_id = $_GET['payment_id'] ?? 0;
$type = $_GET['type'] ?? '';
$amount = $_GET['amount'] ?? 0;

// Clear session payment data
$payment_success = $_SESSION['payment_success'] ?? false;
unset($_SESSION['payment_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-gradient { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'includes/navbar.php'; ?>

<div class="max-w-2xl mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden text-center">
        <div class="success-gradient px-6 py-8">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-green-600 text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Payment Submitted!</h1>
            <p class="text-green-100 mt-2">Your payment has been received and is pending verification.</p>
        </div>
        
        <div class="p-8">
            <div class="bg-gray-50 rounded-xl p-5 mb-6">
                <div class="flex justify-between py-2">
                    <span class="text-gray-600">Transaction Type</span>
                    <span class="font-semibold capitalize"><?php echo htmlspecialchars($type ?: 'Payment'); ?></span>
                </div>
                <div class="flex justify-between py-2 border-t border-gray-200">
                    <span class="text-gray-600">Amount Paid</span>
                    <span class="font-bold text-green-600 text-xl"><?php echo number_format($amount, 2); ?> EGP</span>
                </div>
                <div class="flex justify-between py-2 border-t border-gray-200">
                    <span class="text-gray-600">Payment ID</span>
                    <span class="font-mono text-sm">#<?php echo $payment_id; ?></span>
                </div>
                <div class="flex justify-between py-2 border-t border-gray-200">
                    <span class="text-gray-600">Date</span>
                    <span class="text-sm"><?php echo date('F d, Y H:i:s'); ?></span>
                </div>
            </div>
            
            <div class="bg-amber-50 rounded-xl p-4 mb-6 text-sm text-amber-800">
                <i class="fas fa-clock mr-2"></i>
                Your payment is pending verification. Our team will review your receipt within 24 hours.
            </div>
            
            <div class="flex gap-4 justify-center">
                <a href="dashboard_<?php echo getUserType(); ?>.php" class="px-6 py-3 bg-gray-800 text-white rounded-xl font-semibold hover:bg-gray-700 transition">
                    Go to Dashboard
                </a>
                <a href="pricing.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition">
                    View Plans
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>