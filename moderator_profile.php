<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();
requireUserType(['moderator', 'admin']);

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Full name is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $user_id]);
        $_SESSION['user_name'] = $full_name;
        $success = 'Profile updated successfully!';
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Verify current password
    if (!password_verify($current_password, $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);
        $success = 'Password changed successfully!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Profile - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .form-input {
            width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px;
            font-size: 14px; transition: all 0.2s;
        }
        .form-input:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.1); }
        .btn-primary { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; padding: 10px 20px; border-radius: 40px; font-weight: 600; border: none; cursor: pointer; }
    </style>
</head>
<body class="bg-gray-100">

<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <i class="fas fa-gem text-purple-600 text-2xl mr-2"></i>
                <span class="font-bold text-xl">Moderator Profile</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="moderator.php" class="text-gray-600 hover:text-purple-600">Dashboard</a>
                <a href="logout.php" class="text-red-600 hover:text-red-700">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="orange-gradient px-6 py-4">
            <h1 class="text-xl font-bold text-white">My Profile</h1>
            <p class="text-orange-100 text-sm">Manage your account settings</p>
        </div>
        
        <div class="p-6">
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 text-sm"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 text-sm"><?= $error ?></div>
            <?php endif; ?>
            
            <!-- Profile Info Form -->
            <form method="POST" class="mb-8">
                <h2 class="font-semibold text-gray-800 mb-4 pb-2 border-b">Profile Information</h2>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="form-input" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-input bg-gray-100" disabled>
                    <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="form-input">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">User Type</label>
                    <input type="text" value="<?= ucfirst($user['user_type']) ?>" class="form-input bg-gray-100" disabled>
                </div>
                
                <button type="submit" class="btn-primary">Update Profile</button>
            </form>
            
            <!-- Change Password Form -->
            <form method="POST">
                <h2 class="font-semibold text-gray-800 mb-4 pb-2 border-b">Change Password</h2>
                <input type="hidden" name="change_password" value="1">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" name="new_password" class="form-input" required>
                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>
                
                <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded-full text-sm font-semibold hover:bg-gray-800 transition">Change Password</button>
            </form>
            
            <div class="mt-6 pt-4 border-t text-center">
                <a href="moderator.php" class="text-gray-500 hover:text-gray-700 text-sm">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>