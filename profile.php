<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_GET['id'] ?? $_SESSION['user_id'];
$viewing_own = ($user_id == $_SESSION['user_id']);
$can_edit = $viewing_own || getUserType() === 'admin';
$edit_mode = isset($_GET['edit']) && $can_edit;

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: dashboard_user.php');
    exit();
}

// Get shop data if applicable
$shop = null;
if ($user['user_type'] === 'shop') {
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $shop = $stmt->fetch();
}

// Handle profile update
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $edit_mode) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($phone)) $errors[] = 'Phone number is required.';
    
    if (!empty($phone) && !preg_match('/^01[0-9]{9}$/', $phone)) {
        $errors[] = 'Please enter a valid Egyptian phone number.';
    }
    
    // Handle profile picture upload
    $profile_image = $user['profile_image'] ?? '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only JPG, PNG, GIF images are allowed.';
        } else {
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $filename)) {
                if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                    unlink($user['profile_image']);
                }
                $profile_image = $upload_dir . $filename;
            }
        }
    }
    
    // Handle password change
    if (!empty($new_password)) {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password.';
        } elseif (!password_verify($current_password, $user_data['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user_id]);
            $success = 'Password updated successfully.';
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, profile_image = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $phone, $profile_image, $user_id])) {
            $_SESSION['user_name'] = $full_name;
            $success = 'Profile updated successfully.';
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            header('Location: profile.php?id=' . $user_id);
            exit();
        } else {
            $error = 'Failed to update profile.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Get user stats
$stmt = $pdo->prepare("SELECT COUNT(*) as report_count FROM reports WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as claim_count FROM claims WHERE claimant_user_id = ?");
$stmt->execute([$user_id]);
$claim_stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as resolved_count FROM claims WHERE claimant_user_id = ? AND status = 'approved'");
$stmt->execute([$user_id]);
$resolved_stats = $stmt->fetch();

$user_ref = 'FND-' . date('Y') . '-' . str_pad($user['id'], 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($user['full_name']); ?> - Profile | Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        /* Elegant Typography */
        .serif-font { font-family: 'Playfair Display', serif; }
        .tracking-wide { letter-spacing: 0.3px; }
        .tracking-wider { letter-spacing: 0.5px; }
        
        .profile-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            border-radius: 24px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(249,115,22,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .profile-avatar {
            width: 112px;
            height: 112px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fff, #fef7ed);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #f97316;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            position: relative;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .avatar-initial {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            font-weight: 700;
            background: linear-gradient(135deg, #f97316, #ea580c);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .edit-icon {
            position: absolute;
            bottom: 4px;
            right: 4px;
            background: #f97316;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid #fff;
            transition: all 0.2s;
        }
        
        .edit-icon:hover {
            background: #ea580c;
            transform: scale(1.05);
        }
        
        .stat-box {
            background: #fff;
            border-radius: 20px;
            padding: 18px 12px;
            text-align: center;
            border: 1px solid rgba(249, 115, 22, 0.08);
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-box::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #f97316, #f59e0b);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .stat-box:hover::after {
            transform: scaleX(1);
        }
        
        .stat-box:hover {
            border-color: rgba(249, 115, 22, 0.2);
            box-shadow: 0 8px 24px rgba(249,115,22,0.08);
        }
        
        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.5px;
        }
        
        .stat-label {
            font-size: 11px;
            font-weight: 500;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }
        
        .info-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid rgba(249, 115, 22, 0.06);
            transition: all 0.2s;
        }
        
        .info-card:hover {
            border-color: rgba(249, 115, 22, 0.15);
            box-shadow: 0 8px 24px rgba(0,0,0,0.04);
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            letter-spacing: -0.3px;
        }
        
        .section-icon {
            width: 36px;
            height: 36px;
            background: #fef7ed;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-label i {
            width: 18px;
            color: #f97316;
            font-size: 13px;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .ref-box {
            background: linear-gradient(135deg, #fffbeb, #fef7ed);
            border: 1px solid #fde68a;
            border-radius: 20px;
            padding: 16px 24px;
            transition: all 0.2s;
        }
        
        .ref-box:hover {
            border-color: #f59e0b;
            box-shadow: 0 4px 16px rgba(245,158,11,0.1);
        }
        
        .ref-label {
            font-size: 10px;
            font-weight: 600;
            color: #b45309;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .ref-number {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: #92400e;
            letter-spacing: 0.5px;
        }
        
        .btn-outline {
            border: 1.5px solid #f97316;
            color: #f97316;
            padding: 8px 24px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: all 0.2s;
            background: #fff;
        }
        
        .btn-outline:hover {
            background: #f97316;
            color: #fff;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            padding: 10px 28px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(249,115,22,0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(249,115,22,0.3);
        }
        
        .btn-secondary {
            background: #475569;
            color: #fff;
            padding: 10px 28px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: all 0.2s;
        }
        
        .btn-secondary:hover {
            background: #334155;
        }
        
        .badge-verified {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .badge-shop {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            background: #fafafa;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #f97316;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.08);
        }
        
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            display: block;
        }
        
        .activity-item {
            padding: 14px;
            border-radius: 14px;
            transition: all 0.2s;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background: #fef7ed;
            border-left: 3px solid #f97316;
            padding-left: 11px;
        }
        
        .activity-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .activity-time {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #f97316, #f59e0b);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .user-name {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .user-meta {
            font-size: 13px;
            font-weight: 500;
            color: #cbd5e1;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-gray-50">

<?php include 'includes/navbar.php'; ?>

<div class="max-w-5xl mx-auto px-4 py-8">
    
    <!-- Profile Header -->
    <div class="profile-header p-6 text-white mb-8">
        <div class="flex flex-col md:flex-row items-center gap-6">
            <!-- Avatar -->
            <div class="profile-avatar">
                <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                <?php else: ?>
                    <span class="avatar-initial"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                <?php endif; ?>
                <?php if ($can_edit && !$edit_mode): ?>
                    <a href="?id=<?php echo $user_id; ?>&edit=1" class="edit-icon">
                        <i class="fas fa-pen text-white text-xs"></i>
                    </a>
                <?php endif; ?>
                <?php if ($edit_mode): ?>
                    <label class="edit-icon" for="profile_upload">
                        <i class="fas fa-camera text-white text-xs"></i>
                    </label>
                <?php endif; ?>
            </div>
            
            <!-- User Info -->
            <div class="flex-1 text-center md:text-left">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mb-2">
                    <h1 class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <?php if ($user['is_verified']): ?>
                        <span class="badge-verified"><i class="fas fa-check-circle mr-1"></i> Verified</span>
                    <?php endif; ?>
                    <?php if ($shop && $shop['verified_badge']): ?>
                        <span class="badge-shop"><i class="fas fa-store mr-1"></i> Verified Shop</span>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 user-meta">
                    <span><i class="fas fa-envelope mr-1 text-orange-400"></i> <?php echo htmlspecialchars($user['email']); ?></span>
                    <span><i class="fas fa-phone mr-1 text-orange-400"></i> <?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                    <span><i class="far fa-calendar mr-1 text-orange-400"></i> Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
            
            <!-- Action Button -->
            <div>
                <?php if ($can_edit && !$edit_mode): ?>
                    <a href="?id=<?php echo $user_id; ?>&edit=1" class="btn-primary inline-flex items-center gap-2">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                <?php elseif ($edit_mode): ?>
                    <a href="profile.php?id=<?php echo $user_id; ?>" class="btn-secondary inline-flex items-center gap-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if (isset($_GET['updated'])): ?>
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 px-4 py-3 rounded-xl mb-6 text-sm">
            <i class="fas fa-check-circle mr-2 text-emerald-500"></i> Profile updated successfully!
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
            <i class="fas fa-exclamation-circle mr-2 text-red-500"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Edit Mode Form -->
    <?php if ($edit_mode): ?>
    <div class="info-card p-6 mb-8">
        <h3 class="section-title mb-5">Edit Profile Information</h3>
        <form method="POST" enctype="multipart/form-data" id="profileForm">
            <input type="file" id="profile_upload" name="profile_image" accept="image/*" class="hidden">
            
            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" required value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-input" placeholder="01234567890">
                </div>
                <div>
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" placeholder="Enter to change password">
                    <p class="text-xs text-gray-400 mt-1">Required only if changing password</p>
                </div>
                <div>
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" placeholder="Min. 6 characters">
                </div>
                <div>
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" placeholder="Re-enter new password">
                </div>
            </div>
            
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="profile.php?id=<?php echo $user_id; ?>" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['report_count']; ?></div>
            <div class="stat-label">Total Reports</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $claim_stats['claim_count']; ?></div>
            <div class="stat-label">Claims Filed</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $resolved_stats['resolved_count']; ?></div>
            <div class="stat-label">Resolved</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $user['is_verified'] ? 'Yes' : 'No'; ?></div>
            <div class="stat-label">Verified</div>
        </div>
        <div class="stat-box">
            <div class="stat-number capitalize"><?php echo $user['status']; ?></div>
            <div class="stat-label">Status</div>
        </div>
    </div>
    
    <!-- Reference Box -->
    <div class="ref-box flex justify-between items-center flex-wrap gap-4 mb-8">
        <div>
            <p class="ref-label">Reference Number</p>
            <p class="ref-number"><?php echo $user_ref; ?></p>
        </div>
        <button onclick="copyToClipboard('<?php echo $user_ref; ?>')" class="btn-outline">
            <i class="fas fa-copy mr-1"></i> Copy
        </button>
    </div>
    
    <!-- Two Column Info -->
    <div class="grid md:grid-cols-2 gap-6">
        
        <!-- Contact Information -->
        <div class="info-card p-6">
            <div class="flex items-center mb-5">
                <div class="section-icon">
                    <i class="fas fa-address-card text-orange-500 text-sm"></i>
                </div>
                <h3 class="section-title">Contact Information</h3>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-phone"></i> Phone</span>
                <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-calendar-alt"></i> Member Since</span>
                <span class="info-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-history"></i> Last Login</span>
                <span class="info-value"><?php echo $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : 'First login'; ?></span>
            </div>
        </div>
        
        <!-- Shop or Account Info -->
        <?php if ($shop): ?>
        <div class="info-card p-6">
            <div class="flex items-center mb-5">
                <div class="section-icon">
                    <i class="fas fa-store text-orange-500 text-sm"></i>
                </div>
                <h3 class="section-title">Shop Information</h3>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-building"></i> Business Name</span>
                <span class="info-value font-semibold"><?php echo htmlspecialchars($shop['business_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-tag"></i> Registration</span>
                <span class="info-value"><?php echo htmlspecialchars($shop['business_registration_number'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-crown"></i> Plan</span>
                <span class="info-value capitalize"><?php echo ucfirst($shop['subscription_type'] ?? 'Free'); ?></span>
            </div>
            <?php if ($shop['address']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-map-marker-alt"></i> Location</span>
                <span class="info-value"><?php echo htmlspecialchars($shop['address']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="info-card p-6">
            <div class="flex items-center mb-5">
                <div class="section-icon">
                    <i class="fas fa-chart-pie text-orange-500 text-sm"></i>
                </div>
                <h3 class="section-title">Account Summary</h3>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-chart-line"></i> Recovery Rate</span>
                <span class="info-value gradient-text font-bold">
                    <?php 
                    $recovery_rate = $stats['report_count'] > 0 ? round(($resolved_stats['resolved_count'] / $stats['report_count']) * 100) : 0;
                    echo $recovery_rate . '%';
                    ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-file-alt"></i> Active Reports</span>
                <span class="info-value font-semibold">
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'active'");
                    $stmt->execute([$user_id]);
                    echo $stmt->fetchColumn();
                    ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-eye"></i> Total Views</span>
                <span class="info-value font-semibold">
                    <?php
                    $stmt = $pdo->prepare("SELECT SUM(views_count) FROM reports WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    echo number_format($stmt->fetchColumn() ?: 0);
                    ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Activity -->
    <div class="info-card p-6 mt-6">
        <div class="flex items-center mb-5">
            <div class="section-icon">
                <i class="fas fa-history text-orange-500 text-sm"></i>
            </div>
            <h3 class="section-title">Recent Activity</h3>
        </div>
        <?php
        $stmt = $pdo->prepare("SELECT id, title, report_type, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
        $stmt->execute([$user_id]);
        $recent_items = $stmt->fetchAll();
        ?>
        
        <?php if (empty($recent_items)): ?>
            <div class="text-center py-10 text-gray-500">
                <i class="fas fa-inbox text-3xl mb-2 text-gray-300"></i>
                <p class="text-sm">No recent activity</p>
                <a href="report_item.php" class="inline-block mt-3 text-orange-600 text-sm hover:underline">Create your first report →</a>
            </div>
        <?php else: ?>
            <?php foreach ($recent_items as $item): ?>
                <div class="activity-item flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full <?php echo $item['report_type'] === 'stolen' ? 'bg-red-100' : ($item['report_type'] === 'lost' ? 'bg-yellow-100' : 'bg-green-100'); ?> flex items-center justify-center">
                            <i class="fas <?php echo $item['report_type'] === 'stolen' ? 'fa-exclamation-triangle text-red-500 text-xs' : ($item['report_type'] === 'lost' ? 'fa-search text-yellow-600 text-xs' : 'fa-check-circle text-green-500 text-xs'); ?>"></i>
                        </div>
                        <div>
                            <p class="activity-title"><?php echo htmlspecialchars($item['title']); ?></p>
                            <p class="activity-time"><?php echo timeAgo($item['created_at']); ?></p>
                        </div>
                    </div>
                    <a href="view_report.php?id=<?php echo $item['id']; ?>" class="text-orange-600 text-sm font-medium hover:underline">View →</a>
                </div>
            <?php endforeach; ?>
            <div class="mt-4 pt-2 text-center">
                <a href="my_reports.php" class="text-orange-600 text-sm font-medium hover:underline">View all reports →</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer Note -->
    <div class="text-center mt-6">
        <p class="text-xs text-gray-400">Need assistance? <a href="contact.php" class="text-orange-500 hover:underline">Contact support</a></p>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Reference number copied: ' + text);
    });
}

document.getElementById('profile_upload')?.addEventListener('change', function(e) {
    if (e.target.files.length > 0) {
        document.getElementById('profileForm').submit();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>