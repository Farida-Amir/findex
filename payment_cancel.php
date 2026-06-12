<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$payment_id = $_GET['payment_id'] ?? 0;
$type = $_GET['type'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .cancel-gradient { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'includes/navbar.php'; ?>

<div class="max-w-2xl mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden text-center">
        <div class="cancel-gradient px-6 py-8">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-times text-red-600 text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Payment Cancelled</h1>
            <p class="text-red-100 mt-2">Your payment was cancelled or did not complete.</p>
        </div>
        
        <div class="p-8">
            <div class="bg-amber-50 rounded-xl p-5 mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <i class="fas fa-info-circle text-amber-600 text-xl"></i>
                    <p class="text-amber-800 text-sm">No charges have been made to your account.</p>
                </div>
                <p class="text-gray-600 text-sm">
                    Your payment was cancelled. You can try again or contact support if you need assistance.
                </p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-4 mb-6 text-sm text-gray-600">
                <p><strong>Possible reasons:</strong></p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>You cancelled the payment manually</li>
                    <li>Payment gateway timeout or error</li>
                    <li>Insufficient funds or declined transaction</li>
                </ul>
            </div>
            
            <div class="flex gap-4 justify-center flex-wrap">
                <a href="pricing.php" class="px-6 py-3 bg-orange-600 text-white rounded-xl font-semibold hover:bg-orange-700 transition">
                    Try Again
                </a>
                <a href="dashboard_<?php echo getUserType(); ?>.php" class="px-6 py-3 bg-gray-800 text-white rounded-xl font-semibold hover:bg-gray-700 transition">
                    Go to Dashboard
                </a>
                <a href="contact.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>