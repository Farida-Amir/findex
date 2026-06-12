<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get current user subscription status if logged in
$user_subscription = null;
$subscription_plan = null;
$is_verified = false;
$shop = null;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'shop') {
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shop = $stmt->fetch();
    
    if ($shop) {
        $is_verified = ($shop['verified_badge'] == 1);
        $subscription_plan = $shop['subscription_plan'] ?? null;
        
        if ($shop['subscription_expires_at'] && strtotime($shop['subscription_expires_at']) > time()) {
            $user_subscription = $shop['subscription_plan'];
        }
    }
}

// Handle subscription request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe_plan']) && isset($_SESSION['user_id'])) {
    $plan = $_POST['subscribe_plan'];
    $amount = ($plan === 'premium') ? 400 : 250; // UPDATED PRICES
    
    $_SESSION['pending_subscription'] = [
        'plan' => $plan,
        'amount' => $amount,
        'shop_id' => $shop['id'] ?? null
    ];
    
    header("Location: process_payment.php?plan=" . $plan);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Pricing & Subscriptions - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8f9fc; }
        
        .plan-card {
            transition: all 0.3s ease;
            background: white;
            border-radius: 20px;
            position: relative;
        }
        .plan-card:hover {
            transform: translateY(-3px);
        }
        .popular-badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #f97316;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 3px 14px;
            border-radius: 20px;
            white-space: nowrap;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 10px;
        }
        .feature-list li i {
            width: 14px;
            color: #f97316;
            font-size: 10px;
        }
        .btn-subscribe {
            background: #1f2937;
            color: white;
            padding: 8px 0;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-subscribe:hover {
            background: #f97316;
        }
        .btn-subscribe-premium {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            padding: 8px 0;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-subscribe-premium:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }
        .current-badge {
            background: #10b981;
            color: white;
            padding: 6px 0;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
        }
        .price {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
        }
        .price small {
            font-size: 12px;
            font-weight: 400;
            color: #9ca3af;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Hero -->
<section class="py-12 text-center" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-white mb-2">Simple Pricing</h1>
        <p class="text-gray-300 text-sm">Get verified and start recovering more items</p>
    </div>
</section>

<div class="max-w-6xl mx-auto px-4 py-12">

    <!-- Current Plan Status -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'shop' && $user_subscription): ?>
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 mb-8 text-center">
            <span class="text-emerald-700 text-sm font-medium">Active <?php echo strtoupper($user_subscription); ?> Plan</span>
            <span class="inline-flex ml-2 bg-emerald-100 px-2 py-0.5 rounded-full text-xs text-emerald-700">Verified Shop</span>
        </div>
    <?php endif; ?>

    <!-- Free Section -->
    <div class="text-center mb-8">
        <div class="inline-block bg-gray-100 px-3 py-1 rounded-full mb-3">
            <span class="text-[10px] font-semibold text-gray-500">FOR INDIVIDUALS</span>
        </div>
        <h2 class="text-xl font-bold text-gray-800">Always Free</h2>
        <p class="text-gray-500 text-xs mt-1">Post reports, search, and claim items at no cost</p>
    </div>

    <div class="max-w-sm mx-auto mb-16">
        <div class="plan-card border border-gray-100 p-5 text-center">
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-user text-gray-500 text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Free Account</h3>
            <div class="price mt-1">0 <small>EGP</small></div>
            <p class="text-gray-400 text-[10px] mt-0">forever</p>
            
            <ul class="feature-list mt-4 text-left">
                <li><i class="fas fa-check"></i> Post lost/stolen/found reports</li>
                <li><i class="fas fa-check"></i> Instant match notifications</li>
                <li><i class="fas fa-check"></i> Search all reports in Egypt</li>
                <li><i class="fas fa-check"></i> AI assistance (pay per use)</li>
            </ul>
            
            <div class="mt-4 pt-3 border-t border-gray-100">
                <div class="flex justify-between text-xs py-1">
                    <span class="text-gray-500">Boost report (7 days)</span>
                    <span class="font-semibold">50 EGP</span>
                </div>
                <div class="flex justify-between text-xs py-1">
                    <span class="text-gray-500">AI image analysis</span>
                    <span class="font-semibold">50 EGP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Shops Section -->
    <div class="text-center mb-8">
        <div class="inline-block bg-gray-100 px-3 py-1 rounded-full mb-3">
            <span class="text-[10px] font-semibold text-gray-500">FOR JEWELRY SHOPS</span>
        </div>
        <h2 class="text-xl font-bold text-gray-800">Choose Your Plan</h2>
        <p class="text-gray-500 text-xs mt-1">Get verified badge and premium features</p>
    </div>

    <div class="grid md:grid-cols-2 gap-6 max-w-3xl mx-auto">
        
        <!-- Basic Plan -->
        <div class="plan-card border border-gray-200 p-5">
            <div class="text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-store text-gray-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Basic Plan</h3>
                <div class="price mt-1">250 <small>EGP</small></div> <!-- UPDATED PRICE -->
                <p class="text-gray-400 text-[10px] mt-0">per month · billed monthly</p>
            </div>
            
            <ul class="feature-list mt-4">
                <li><i class="fas fa-check-circle text-green-500"></i> <strong>Verified Badge</strong> on profile</li>
                <li><i class="fas fa-check-circle text-green-500"></i> Unlimited report posting</li>
                <li><i class="fas fa-check-circle text-green-500"></i> Basic analytics dashboard</li>
                <li><i class="fas fa-check-circle text-green-500"></i> 5 free report boosts / month</li>
                <li><i class="fas fa-check-circle text-green-500"></i> Email support (24h response)</li>
            </ul>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php?type=shop" class="block mt-5 py-2 bg-gray-800 text-white rounded-full text-sm font-medium text-center hover:bg-gray-700">Register as Shop</a>
            <?php elseif ($user_subscription === 'basic'): ?>
                <div class="current-badge mt-5"><i class="fas fa-check mr-1"></i> Current Plan</div>
            <?php else: ?>
                <form method="POST" class="mt-5">
                    <input type="hidden" name="subscribe_plan" value="basic">
                    <button type="submit" class="btn-subscribe w-full">Subscribe Now</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Premium Plan -->
        <div class="plan-card border-2 border-orange-400 p-5 relative">
            <div class="popular-badge">Most Popular</div>
            <div class="text-center">
                <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-crown text-white text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Premium Plan</h3>
                <div class="price mt-1">400 <small>EGP</small></div> <!-- UPDATED PRICE -->
                <p class="text-gray-400 text-[10px] mt-0">per month · billed monthly</p>
            </div>
            
            <ul class="feature-list mt-4">
                <li><i class="fas fa-star text-amber-500 text-[10px]"></i> <strong>Premium Verified Badge</strong> (gold border)</li>
                <li><i class="fas fa-star text-amber-500 text-[10px]"></i> Unlimited report boosts</li>
                <li><i class="fas fa-star text-amber-500 text-[10px]"></i> Featured in search results</li>
                <li><i class="fas fa-star text-amber-500 text-[10px]"></i> Advanced analytics with insights</li>
                <li><i class="fas fa-star text-amber-500 text-[10px]"></i> Priority support (4h response)</li>
                <li><i class="fas fa-star text-amber-500 text-[10px]"></i> Dedicated account manager</li>
            </ul>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php?type=shop" class="block mt-5 py-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-full text-sm font-medium text-center">Register as Shop</a>
            <?php elseif ($user_subscription === 'premium'): ?>
                <div class="current-badge mt-5 bg-amber-500"><i class="fas fa-crown mr-1"></i> Premium Active</div>
            <?php else: ?>
                <form method="POST" class="mt-5">
                    <input type="hidden" name="subscribe_plan" value="premium">
                    <button type="submit" class="btn-subscribe-premium w-full">Subscribe Now</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Trust Badges -->
    <div class="flex flex-wrap justify-center gap-6 mt-12 pt-6 border-t border-gray-200">
        <div class="flex items-center gap-2"><i class="fas fa-shield-alt text-orange-500 text-sm"></i><span class="text-xs text-gray-500">100% Verified Shops</span></div>
        <div class="flex items-center gap-2"><i class="fas fa-handshake text-amber-500 text-sm"></i><span class="text-xs text-gray-500">Official Partnership</span></div>
        <div class="flex items-center gap-2"><i class="fas fa-headset text-emerald-500 text-sm"></i><span class="text-xs text-gray-500">24/7 Support</span></div>
        <div class="flex items-center gap-2"><i class="fas fa-chart-line text-purple-500 text-sm"></i><span class="text-xs text-gray-500">5,000+ Recoveries</span></div>
    </div>

    <!-- FAQ -->
    <div class="mt-12 bg-white rounded-xl p-5 border border-gray-100">
        <h3 class="text-sm font-bold text-gray-800 text-center mb-4">Frequently Asked Questions</h3>
        <div class="grid md:grid-cols-2 gap-4 text-xs">
            <div>
                <p class="font-semibold text-gray-700">Is Findex really free for regular users?</p>
                <p class="text-gray-500 mt-1">Yes. Posting reports, searching, and claiming items is completely free.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-700">Can I cancel anytime?</p>
                <p class="text-gray-500 mt-1">Absolutely. No contracts, no cancellation fees.</p>
            </div>
            <div>
                <p class="font-semibold text-gray-700">What payment methods do you accept?</p>
                <p class="text-gray-500 mt-1">Credit/Debit Cards, Instapay, and Bank Transfer (EGP).</p>
            </div>
            <div>
                <p class="font-semibold text-gray-700">How long does verification take?</p>
                <p class="text-gray-500 mt-1">Within 24 hours after payment confirmation.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>