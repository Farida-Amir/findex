<?php
/**
 * User Settings Page - Findex
 * Manage profile, password, notifications, and AI preferences
 */

require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);
$user_type = $_SESSION['user_type'];

// Get user settings from database (create table if needed)
$stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch();

if (!$settings) {
    // Initialize default settings
    $stmt = $pdo->prepare("
        INSERT INTO user_settings (user_id, email_notifications, push_notifications, ai_assistant_enabled, dark_mode, language, created_at)
        VALUES (?, 1, 1, 1, 0, 'en', NOW())
    ");
    $stmt->execute([$user_id]);
    
    // Fetch again
    $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch();
}

// Get shop data if user is a shop
$shop_data = null;
if ($user_type === 'shop') {
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $shop_data = $stmt->fetch();
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update Profile
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($full_name)) {
            $error = 'Full name is required.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $phone, $user_id])) {
                $_SESSION['user_name'] = $full_name;
                $message = 'Profile updated successfully!';
                
                // Refresh user data
                $user = getUserData($user_id);
            } else {
                $error = 'Failed to update profile.';
            }
        }
    }
    
    // Update Password
    elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$new_hash, $user_id])) {
                $message = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password.';
            }
        }
    }
    
    // Update Shop Information (for shop users)
    elseif ($user_type === 'shop' && isset($_POST['update_shop'])) {
        $business_name = trim($_POST['business_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        $stmt = $pdo->prepare("
            UPDATE shops SET 
                business_name = ?, 
                address = ?, 
                city = ?, 
                description = ? 
            WHERE user_id = ?
        ");
        if ($stmt->execute([$business_name, $address, $city, $description, $user_id])) {
            $message = 'Shop information updated successfully!';
            // Refresh shop data
            $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $shop_data = $stmt->fetch();
        } else {
            $error = 'Failed to update shop information.';
        }
    }
    
    // Update Notification Settings
    elseif (isset($_POST['update_notifications'])) {
        $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
        $push_notif = isset($_POST['push_notifications']) ? 1 : 0;
        $ai_assistant = isset($_POST['ai_assistant_enabled']) ? 1 : 0;
        $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
        $language = $_POST['language'] ?? 'en';
        
        $stmt = $pdo->prepare("
            UPDATE user_settings SET 
                email_notifications = ?,
                push_notifications = ?,
                ai_assistant_enabled = ?,
                dark_mode = ?,
                language = ?,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        if ($stmt->execute([$email_notif, $push_notif, $ai_assistant, $dark_mode, $language, $user_id])) {
            $message = 'Settings updated successfully!';
            // Refresh settings
            $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
        } else {
            $error = 'Failed to update settings.';
        }
    }
    
    // Delete Account
    elseif (isset($_POST['delete_account']) && isset($_POST['confirm_delete'])) {
        $confirm = $_POST['confirm_delete'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($confirm !== 'DELETE') {
            $error = 'Please type DELETE to confirm account deletion.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = 'Password is incorrect.';
        } else {
            // Soft delete - deactivate account
            $stmt = $pdo->prepare("UPDATE users SET status = 'deleted', deleted_at = NOW() WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                session_destroy();
                header('Location: index.php?account_deleted=1');
                exit();
            } else {
                $error = 'Failed to delete account. Please contact support.';
            }
        }
    }
}

// Get notification count for navbar
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$notif_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Settings - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f5f5f0; }
        .settings-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px; }
        .settings-header { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
        .settings-body { padding: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px; }
        .form-input, .form-select, .form-textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 10px;
            transition: all 0.2s ease; font-size: 14px;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none; border-color: #f97316; ring: 2px solid #f97316;
        }
        .btn-primary { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; padding: 10px 20px; border-radius: 10px; font-weight: 600; transition: all 0.2s; border: none; cursor: pointer; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(249,115,22,0.3); }
        .btn-secondary { background: #f3f4f6; color: #374151; padding: 10px 20px; border-radius: 10px; font-weight: 500; border: 1px solid #d1d5db; cursor: pointer; }
        .btn-danger { background: #ef4444; color: white; padding: 10px 20px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; }
        .toggle-switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: 0.3s; border-radius: 34px; }
        .toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%; }
        input:checked + .toggle-slider { background-color: #f97316; }
        input:checked + .toggle-slider:before { transform: translateX(26px); }
        .settings-nav { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; border-bottom: 1px solid #e5e7eb; padding-bottom: 16px; }
        .settings-nav-btn { background: transparent; border: none; padding: 10px 20px; border-radius: 12px; cursor: pointer; font-weight: 500; color: #6b7280; transition: all 0.2s; }
        .settings-nav-btn.active { background: #f97316; color: white; }
        .settings-nav-btn:hover:not(.active) { background: #fef3c7; color: #f97316; }
        .settings-section { display: none; }
        .settings-section.active { display: block; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-6 md:py-8">
    
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Settings</h1>
        <p class="text-gray-500 mt-1">Manage your account preferences and profile information</p>
    </div>
    
    <!-- Message Alerts -->
    <?php if ($message): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
        <span><i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?></span>
        <button onclick="this.parentElement.remove()" class="text-green-700">&times;</button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center justify-between">
        <span><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?></span>
        <button onclick="this.parentElement.remove()" class="text-red-700">&times;</button>
    </div>
    <?php endif; ?>
    
    <!-- Settings Navigation -->
    <div class="settings-nav">
        <button class="settings-nav-btn active" data-section="profile">👤 Profile</button>
        <button class="settings-nav-btn" data-section="security">🔒 Security</button>
        <?php if ($user_type === 'shop'): ?>
        <button class="settings-nav-btn" data-section="shop">🏪 Shop Info</button>
        <?php endif; ?>
        <button class="settings-nav-btn" data-section="notifications">🔔 Notifications</button>
        <button class="settings-nav-btn" data-section="ai">🤖 AI Assistant</button>
        <button class="settings-nav-btn" data-section="danger">⚠️ Danger Zone</button>
    </div>
    
    <!-- Profile Section -->
    <div id="section-profile" class="settings-section active">
        <div class="settings-card">
            <div class="settings-header">
                <h2 class="text-xl font-bold">Profile Information</h2>
                <p class="text-gray-500 text-sm mt-1">Update your personal information</p>
            </div>
            <div class="settings-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-input" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-input bg-gray-100" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <p class="text-xs text-gray-500 mt-1">Email cannot be changed. Contact support if needed.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="01234567890">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Type</label>
                        <input type="text" class="form-input bg-gray-100" value="<?php echo ucfirst($user_type); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Member Since</label>
                        <input type="text" class="form-input bg-gray-100" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                    </div>
                    <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Security Section -->
    <div id="section-security" class="settings-section">
        <div class="settings-card">
            <div class="settings-header">
                <h2 class="text-xl font-bold">Change Password</h2>
                <p class="text-gray-500 text-sm mt-1">Keep your account secure</p>
            </div>
            <div class="settings-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input" required>
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                    <button type="submit" name="update_password" class="btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Shop Information Section -->
    <?php if ($user_type === 'shop'): ?>
    <div id="section-shop" class="settings-section">
        <div class="settings-card">
            <div class="settings-header">
                <h2 class="text-xl font-bold">Shop Information</h2>
                <p class="text-gray-500 text-sm mt-1">Manage your business details</p>
            </div>
            <div class="settings-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Business Name</label>
                        <input type="text" name="business_name" class="form-input" value="<?php echo htmlspecialchars($shop_data['business_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-input" value="<?php echo htmlspecialchars($shop_data['address'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <select name="city" class="form-select">
                            <option value="">Select City</option>
                            <option value="Cairo" <?php echo ($shop_data['city'] ?? '') == 'Cairo' ? 'selected' : ''; ?>>Cairo</option>
                            <option value="Alexandria" <?php echo ($shop_data['city'] ?? '') == 'Alexandria' ? 'selected' : ''; ?>>Alexandria</option>
                            <option value="Giza" <?php echo ($shop_data['city'] ?? '') == 'Giza' ? 'selected' : ''; ?>>Giza</option>
                            <option value="Luxor" <?php echo ($shop_data['city'] ?? '') == 'Luxor' ? 'selected' : ''; ?>>Luxor</option>
                            <option value="Aswan" <?php echo ($shop_data['city'] ?? '') == 'Aswan' ? 'selected' : ''; ?>>Aswan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Shop Description</label>
                        <textarea name="description" class="form-textarea" rows="4" placeholder="Tell customers about your shop..."><?php echo htmlspecialchars($shop_data['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="bg-amber-50 p-4 rounded-lg mb-4">
                        <p class="text-sm text-amber-800">
                            <i class="fas fa-shield-alt mr-2"></i>
                            <strong>Verification Status:</strong> 
                            <?php 
                            if ($shop_data['verified_badge'] ?? 0) echo '✅ Verified Shop';
                            elseif ($shop_data['is_approved'] ?? 0) echo '⏳ Pending Verification';
                            else echo '⚠️ Not Verified - <a href="shop_verification.php" class="underline">Verify Now</a>';
                            ?>
                        </p>
                    </div>
                    <button type="submit" name="update_shop" class="btn-primary">Save Shop Info</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Notifications Section -->
    <div id="section-notifications" class="settings-section">
        <div class="settings-card">
            <div class="settings-header">
                <h2 class="text-xl font-bold">Notification Preferences</h2>
                <p class="text-gray-500 text-sm mt-1">Control how you receive updates</p>
            </div>
            <div class="settings-body">
                <form method="POST">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <p class="font-medium">Email Notifications</p>
                                <p class="text-xs text-gray-500">Receive updates via email</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="email_notifications" <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <p class="font-medium">Push Notifications</p>
                                <p class="text-xs text-gray-500">Receive browser notifications</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="push_notifications" <?php echo ($settings['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group mt-6">
                        <label class="form-label">Language</label>
                        <select name="language" class="form-select">
                            <option value="en" <?php echo ($settings['language'] ?? 'en') == 'en' ? 'selected' : ''; ?>>English</option>
                            <option value="ar" <?php echo ($settings['language'] ?? 'en') == 'ar' ? 'selected' : ''; ?>>العربية (Arabic)</option>
                        </select>
                    </div>
                    <button type="submit" name="update_notifications" class="btn-primary mt-4">Save Preferences</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- AI Assistant Section -->
    <div id="section-ai" class="settings-section">
        <div class="settings-card">
            <div class="settings-header">
                <h2 class="text-xl font-bold">AI Assistant Settings</h2>
                <p class="text-gray-500 text-sm mt-1">Customize your AI experience</p>
            </div>
            <div class="settings-body">
                <form method="POST">
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <p class="font-medium">Enable AI Assistant</p>
                            <p class="text-xs text-gray-500">Show floating AI button and access AI features</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="ai_assistant_enabled" <?php echo ($settings['ai_assistant_enabled'] ?? 1) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="mt-6 p-4 bg-gradient-to-r from-orange-50 to-amber-50 rounded-lg">
                        <p class="text-sm text-gray-700">
                            <i class="fas fa-robot text-orange-500 mr-2"></i>
                            <strong>AI Features Available:</strong>
                        </p>
                        <ul class="text-sm text-gray-600 mt-2 space-y-1 ml-6 list-disc">
                            <li>🖼️ Image Enhancement & Restoration</li>
                            <li>📝 Automatic Description Analysis</li>
                            <li>🔍 AI-Powered Matching with Database</li>
                            <li>📊 Smart Recommendations</li>
                        </ul>
                    </div>
                    <button type="submit" name="update_notifications" class="btn-primary mt-4">Save AI Settings</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Danger Zone Section -->
    <div id="section-danger" class="settings-section">
        <div class="settings-card border-2 border-red-200">
            <div class="settings-header bg-red-50">
                <h2 class="text-xl font-bold text-red-700">⚠️ Danger Zone</h2>
                <p class="text-red-600 text-sm mt-1">Irreversible actions - proceed with caution</p>
            </div>
            <div class="settings-body">
                <div class="bg-red-50 p-4 rounded-lg mb-4">
                    <h3 class="font-semibold text-red-800 mb-2">Delete Account</h3>
                    <p class="text-sm text-red-700 mb-3">Once you delete your account, all your data will be permanently removed. This action cannot be undone.</p>
                    <form method="POST" onsubmit="return confirm('WARNING: This will permanently delete your account and all associated data. Type DELETE to confirm.');">
                        <div class="form-group">
                            <label class="form-label">Type <span class="font-mono bg-red-100 px-2 py-1 rounded">DELETE</span> to confirm</label>
                            <input type="text" name="confirm_delete" class="form-input border-red-300" placeholder="DELETE" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Enter your password to confirm</label>
                            <input type="password" name="password" class="form-input" required>
                        </div>
                        <button type="submit" name="delete_account" class="btn-danger">Permanently Delete Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include AI Assistant Widget -->
<?php include 'includes/ai_assistant.php'; ?>
<?php include 'includes/footer.php'; ?>

<script>
// Settings navigation
document.querySelectorAll('.settings-nav-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const section = this.dataset.section;
        
        // Update active button
        document.querySelectorAll('.settings-nav-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Show active section
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
        document.getElementById(`section-${section}`).classList.add('active');
    });
});

// Dark mode toggle (if enabled in settings)
<?php if ($settings['dark_mode'] ?? 0): ?>
document.body.classList.add('dark-mode');
<?php endif; ?>
</script>

</body>
</html>