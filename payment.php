<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$type = $_GET['type'] ?? '';
$amount = $_GET['amount'] ?? 0;
$reference_id = $_GET['reference_id'] ?? 0;

$prices = [
    'boost_basic' => 50,
    'boost_premium' => 100,
    'boost_featured' => 200,
    'subscription_basic' => 200,
    'subscription_premium' => 350,
    'ai_service' => 50,
];

if ($type && isset($prices[$type])) {
    $amount = $prices[$type];
}

if ($amount <= 0) {
    header('Location: pricing.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Payment - Findex</title>
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

<div class="max-w-2xl mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="orange-gradient px-6 py-4 text-white">
            <h1 class="text-2xl font-bold">Complete Payment</h1>
            <p class="text-orange-100 text-sm">Secure payment processing</p>
        </div>
        
        <div class="p-6">
            <div class="bg-gray-50 p-4 rounded-lg mb-6 text-center">
                <p class="text-sm text-gray-600">Amount to pay:</p>
                <p class="text-3xl font-bold text-orange-600"><?php echo $amount; ?> EGP</p>
            </div>
            
            <form method="POST" action="process_payment.php">
                <input type="hidden" name="payment_type" value="<?php echo $type; ?>">
                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                <input type="hidden" name="reference_id" value="<?php echo $reference_id; ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Card Number</label>
                    <input type="text" placeholder="1234 5678 9012 3456" class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:border-orange-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Expiry Date</label>
                        <input type="text" placeholder="MM/YY" class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">CVV</label>
                        <input type="text" placeholder="123" class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:border-orange-500">
                    </div>
                </div>
                
                <div class="bg-yellow-50 p-3 rounded-lg mb-6 text-sm text-yellow-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    This is a demo payment. No real charges will be made.
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="orange-gradient text-white px-6 py-3 rounded-full font-semibold flex-1">
                        Pay <?php echo $amount; ?> EGP
                    </button>
                    <a href="pricing.php" class="bg-gray-500 text-white px-6 py-3 rounded-full font-semibold hover:bg-gray-600">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>