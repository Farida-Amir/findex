<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();
requireUserType(['shop']);

$plan = $_GET['plan'] ?? '';
$error = '';
$success = '';

$plans = [
    'basic' => ['name' => 'Basic', 'price' => 200, 'boosts' => 5],
    'premium' => ['name' => 'Premium', 'price' => 350, 'boosts' => 999]
];

if (!isset($plans[$plan])) {
    header('Location: pricing.php');
    exit();
}

$plan_info = $plans[$plan];
$price = $plan_info['price'];
$boosts_limit = $plan_info['boosts'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $end_date = date('Y-m-d', strtotime('+30 days'));
    
    // Update shops table
    $stmt = $pdo->prepare("
        UPDATE shops SET 
            subscription_type = ?, 
            subscription_expires = ?,
            boosts_remaining = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$plan, $end_date, $boosts_limit, $_SESSION['user_id']]);
    
    // Record payment
    $stmt = $pdo->prepare("
        INSERT INTO payments (user_id, amount, type, status, created_at)
        VALUES (?, ?, 'subscription', 'completed', NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $price]);
    
    $success = "Subscription successful! Your {$plan_info['name']} plan is active until " . date('M d, Y', strtotime($end_date));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Subscribe - Findex</title>
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
            <h1 class="text-2xl font-bold">Subscribe to <?php echo $plan_info['name']; ?> Plan</h1>
            <p class="text-orange-100"><?php echo $price; ?> EGP per month</p>
        </div>
        
        <div class="p-6">
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
                </div>
                <div class="flex gap-3 mt-4">
                    <a href="dashboard_shop.php" class="orange-gradient text-white px-6 py-2 rounded-full font-semibold">Go to Dashboard</a>
                    <a href="boost_report.php" class="bg-gray-500 text-white px-6 py-2 rounded-full font-semibold hover:bg-gray-600">Boost a Report</a>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-2">What's included:</h3>
                    <ul class="space-y-2 text-gray-600">
                        <?php if ($plan === 'basic'): ?>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Verified badge on your profile</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Unlimited report posting</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Basic analytics dashboard</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> <?php echo $boosts_limit; ?> free boosts per month</li>
                        <?php else: ?>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Everything in Basic</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Unlimited boosts</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Featured in search results</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Advanced analytics</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Priority customer support</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <form method="POST">
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <p class="text-sm text-gray-600 mb-2">Total amount:</p>
                        <p class="text-3xl font-bold text-orange-600"><?php echo $price; ?> EGP</p>
                        <p class="text-xs text-gray-400">per month, auto-renewing</p>
                    </div>
                    
                    <div class="bg-yellow-50 p-3 rounded-lg mb-4 text-sm text-yellow-700">
                        <i class="fas fa-info-circle mr-2"></i> 
                        This is a demo payment. In production, you would be redirected to a secure payment gateway.
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="submit" class="orange-gradient text-white px-6 py-2 rounded-full font-semibold flex-1">
                            Confirm Subscription
                        </button>
                        <a href="pricing.php" class="bg-gray-500 text-white px-6 py-2 rounded-full font-semibold hover:bg-gray-600">
                            Cancel
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>