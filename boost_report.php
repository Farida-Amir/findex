<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/upload_handler.php';
requireLogin(); // Allow all logged-in users (both regular and shop)

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if user is a shop owner (for subscription info display only)
$stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->execute([$user_id]);
$shop = $stmt->fetch();

// Get subscription info (ONLY for display purposes - NO free boosts)
$has_active_subscription = false;
$subscription_plan = null;

if ($shop) {
    $stmt = $pdo->prepare("SELECT subscription_plan, subscription_expires_at FROM shops WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $subscription_data = $stmt->fetch();
    
    if ($subscription_data && $subscription_data['subscription_expires_at'] && strtotime($subscription_data['subscription_expires_at']) > time()) {
        $has_active_subscription = true;
        $subscription_plan = $subscription_data['subscription_plan'] ?? null;
    }
}

// Get user's active reports (works for both regular users and shops)
$stmt = $pdo->prepare("SELECT id, title, report_type, created_at, is_boosted, boost_expires FROM reports WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$reports = $stmt->fetchAll();

// Boost packages (paid for ALL users)
$boost_packages = [
    'basic' => ['name' => 'Basic Boost', 'price' => 50, 'duration' => 7, 'color' => 'gray', 'icon' => 'fa-rocket'],
    'premium' => ['name' => 'Premium Boost', 'price' => 100, 'duration' => 30, 'color' => 'orange', 'icon' => 'fa-chart-line'],
    'featured' => ['name' => 'Featured Boost', 'price' => 200, 'duration' => 90, 'color' => 'gold', 'icon' => 'fa-crown']
];

// Handle boost request - Redirect to payment page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['boost_report'])) {
    $report_id = $_POST['report_id'] ?? 0;
    $boost_type = $_POST['boost_type'] ?? 'basic';
    
    if (empty($report_id)) {
        $error = 'Please select a report to boost.';
    } elseif (!isset($boost_packages[$boost_type])) {
        $error = 'Invalid boost package selected.';
    } else {
        // Check if report exists and belongs to user
        $stmt = $pdo->prepare("SELECT is_boosted, boost_expires FROM reports WHERE id = ? AND user_id = ?");
        $stmt->execute([$report_id, $user_id]);
        $report_check = $stmt->fetch();
        
        if (!$report_check) {
            $error = 'Report not found or you do not have permission to boost it.';
        } elseif ($report_check['is_boosted'] && $report_check['boost_expires'] > date('Y-m-d H:i:s')) {
            $error = 'This report already has an active boost.';
        } else {
            // Redirect to payment page with report_id and boost_type
            header("Location: process_boost_payment.php?report_id=" . $report_id . "&type=" . $boost_type);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boost Report - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f8f9fc; }
        
        .boost-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .boost-card:hover {
            transform: translateY(-3px);
            border-color: #f97316;
        }
        .boost-card.selected {
            border-color: #f97316;
            background: #fffaf5;
            box-shadow: 0 4px 15px rgba(249,115,22,0.1);
        }
        .report-item {
            transition: all 0.2s;
            cursor: pointer;
        }
        .report-item:hover {
            background: #fef3e8;
        }
        .report-item.selected {
            background: #fef3e8;
            border-left: 3px solid #f97316;
        }
        .report-item.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f9f9f9;
        }
        .report-item.disabled:hover {
            background: #f9f9f9;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-5xl mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="orange-gradient px-6 py-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Boost Your Report</h1>
                    <p class="text-orange-100 text-sm mt-1">Get more visibility and faster recovery</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-rocket text-white text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            
            <!-- Subscription Status - ONLY SHOW FOR SHOPS -->
            <?php if ($shop && $has_active_subscription): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">Active <?php echo strtoupper($subscription_plan); ?> Plan</p>
                            <p class="text-xs text-gray-500">Enjoy premium shop benefits</p>
                        </div>
                    </div>
                    <a href="pricing.php" class="text-xs text-emerald-600 hover:text-emerald-700">Manage Plan →</a>
                </div>
            <?php endif; ?>
            
            <!-- Error Messages -->
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($reports)): ?>
                <div class="text-center py-12">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-alt text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">No Active Reports</h3>
                    <p class="text-gray-500 text-sm mb-4">You don't have any active reports to boost.</p>
                    <a href="report_item.php" class="inline-block px-5 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-700">Create a Report</a>
                </div>
            <?php else: ?>
                <form method="POST" id="boostForm" onsubmit="return validateForm()">
                    <!-- Select Report -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Select Report to Boost</label>
                        <div class="space-y-2 max-h-64 overflow-y-auto border rounded-lg p-2">
                            <?php foreach ($reports as $report): 
                                $is_boosted_active = ($report['is_boosted'] && $report['boost_expires'] > date('Y-m-d H:i:s'));
                            ?>
                                <div class="report-item p-3 rounded-lg border border-gray-100 flex items-center justify-between <?php echo $is_boosted_active ? 'disabled' : ''; ?>" 
                                     data-id="<?php echo $report['id']; ?>" 
                                     data-boosted="<?php echo $is_boosted_active ? 'true' : 'false'; ?>"
                                     onclick="<?php echo !$is_boosted_active ? "selectReport({$report['id']})" : ''; ?>">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full <?php echo $report['report_type'] === 'stolen' ? 'bg-red-100' : 'bg-yellow-100'; ?> flex items-center justify-center">
                                            <i class="fas <?php echo $report['report_type'] === 'stolen' ? 'fa-exclamation-triangle text-red-500' : 'fa-search text-yellow-600'; ?> text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars(substr($report['title'], 0, 50)); ?></p>
                                            <p class="text-xs text-gray-400">Posted: <?php echo date('M d, Y', strtotime($report['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <?php if ($is_boosted_active): ?>
                                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">
                                            <i class="fas fa-rocket text-xs"></i> Active Boost
                                        </span>
                                    <?php elseif ($report['is_boosted'] && $report['boost_expires'] <= date('Y-m-d H:i:s')): ?>
                                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded-full">
                                            Boost Expired
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="report_id" id="selected_report_id" required>
                    </div>
                    
                    <!-- Boost Packages -->
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Choose Boost Package</label>
                    <div class="grid md:grid-cols-3 gap-4 mb-8">
                        <?php foreach ($boost_packages as $key => $package): ?>
                            <div class="boost-card rounded-xl p-4 text-center border <?php echo $key === 'basic' ? 'selected border-orange-500' : 'border-gray-200'; ?>" data-package="<?php echo $key; ?>" onclick="selectPackage('<?php echo $key; ?>')">
                                <div class="w-12 h-12 <?php 
                                    echo $key === 'basic' ? 'bg-gray-100' : ($key === 'premium' ? 'bg-orange-100' : 'bg-amber-100'); 
                                ?> rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas <?php echo $package['icon']; ?> <?php 
                                        echo $key === 'basic' ? 'text-gray-500' : ($key === 'premium' ? 'text-orange-500' : 'text-amber-500'); 
                                    ?> text-xl"></i>
                                </div>
                                <h3 class="font-bold text-gray-800 text-sm"><?php echo $package['name']; ?></h3>
                                <div class="text-xl font-bold <?php echo $key === 'featured' ? 'text-amber-500' : 'text-orange-500'; ?> mt-1"><?php echo $package['price']; ?> <span class="text-xs text-gray-400">EGP</span></div>
                                <p class="text-xs text-gray-400 mt-1"><?php echo $package['duration']; ?> days visibility</p>
                                <?php if ($key === 'featured'): ?>
                                    <p class="text-xs text-amber-500 mt-1">⭐ Featured badge included</p>
                                <?php endif; ?>
                                <input type="radio" name="boost_type" value="<?php echo $key; ?>" id="package_<?php echo $key; ?>" class="hidden" <?php echo $key === 'basic' ? 'checked' : ''; ?>>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" name="boost_report" class="w-full py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl font-semibold hover:from-orange-600 hover:to-orange-700 transition">
                        <i class="fas fa-credit-card mr-2"></i> Continue to Payment
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- How It Works -->
    <div class="bg-white rounded-xl p-5 mt-6 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 text-sm mb-3">How Boosting Works</h3>
        <div class="grid md:grid-cols-3 gap-4 text-center text-xs">
            <div>
                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2"><span class="font-bold text-orange-500">1</span></div>
                <p class="font-medium text-gray-700">Select Report</p>
                <p class="text-gray-400">Choose which report to boost</p>
            </div>
            <div>
                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2"><span class="font-bold text-orange-500">2</span></div>
                <p class="font-medium text-gray-700">Choose Package</p>
                <p class="text-gray-400">Pick duration and visibility level</p>
            </div>
            <div>
                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2"><span class="font-bold text-orange-500">3</span></div>
                <p class="font-medium text-gray-700">Complete Payment</p>
                <p class="text-gray-400">Pay and get instant visibility</p>
            </div>
        </div>
    </div>
</div>

<script>
function selectReport(reportId) {
    const selectedItem = document.querySelector(`.report-item[data-id="${reportId}"]`);
    const isBoosted = selectedItem.getAttribute('data-boosted') === 'true';
    
    if (isBoosted) {
        alert('This report already has an active boost. Please select another report.');
        return false;
    }
    
    document.querySelectorAll('.report-item').forEach(item => {
        item.classList.remove('selected');
        item.style.background = '';
    });
    selectedItem.classList.add('selected');
    selectedItem.style.background = '#fef3e8';
    document.getElementById('selected_report_id').value = reportId;
    return true;
}

function selectPackage(packageKey) {
    document.querySelectorAll('.boost-card').forEach(card => {
        card.classList.remove('selected');
        card.style.borderColor = '#e5e7eb';
        card.style.background = '';
    });
    const selectedCard = document.querySelector(`.boost-card[data-package="${packageKey}"]`);
    selectedCard.classList.add('selected');
    selectedCard.style.borderColor = '#f97316';
    selectedCard.style.background = '#fffaf5';
    document.getElementById(`package_${packageKey}`).checked = true;
}

function validateForm() {
    const reportId = document.getElementById('selected_report_id').value;
    if (!reportId) {
        alert('Please select a report to boost.');
        return false;
    }
    
    const selectedItem = document.querySelector(`.report-item[data-id="${reportId}"]`);
    if (selectedItem && selectedItem.getAttribute('data-boosted') === 'true') {
        alert('This report already has an active boost. Please select another report.');
        return false;
    }
    
    return true;
}

// Auto-select first non-boosted report
<?php if (!empty($reports)): 
    $first_valid_report = null;
    foreach ($reports as $report) {
        $is_boosted_active = ($report['is_boosted'] && $report['boost_expires'] > date('Y-m-d H:i:s'));
        if (!$is_boosted_active) {
            $first_valid_report = $report;
            break;
        }
    }
    if ($first_valid_report): ?>
        selectReport(<?php echo $first_valid_report['id']; ?>);
    <?php endif; ?>
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>