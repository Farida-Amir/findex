<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireUserType(['shop']);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get current shop data
$stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->execute([$user_id]);
$shop = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name = $_POST['business_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($business_name)) {
        $error = 'Business name is required.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE shops SET 
                business_name = ?, 
                phone = ?, 
                address = ?, 
                city = ?, 
                description = ? 
            WHERE user_id = ?
        ");
        
        if ($stmt->execute([$business_name, $phone, $address, $city, $description, $user_id])) {
            $success = 'Settings updated successfully!';
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $shop = $stmt->fetch();
        } else {
            $error = 'Failed to update settings.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Settings - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f5f5f0; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="orange-gradient px-6 py-4 text-white">
            <h1 class="text-2xl font-bold">Shop Settings</h1>
            <p class="text-orange-100 text-sm">Manage your business information</p>
        </div>
        
        <form method="POST" class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Business Name *</label>
                <input type="text" name="business_name" required value="<?php echo htmlspecialchars($shop['business_name'] ?? ''); ?>" 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Phone Number</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($shop['phone'] ?? ''); ?>" 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="2" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"><?php echo htmlspecialchars($shop['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($shop['city'] ?? ''); ?>" 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Business Description</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"><?php echo htmlspecialchars($shop['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="orange-gradient text-white px-6 py-2 rounded-full font-semibold">Save Changes</button>
                <a href="dashboard_shop.php" class="bg-gray-500 text-white px-6 py-2 rounded-full font-semibold hover:bg-gray-600">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>