<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Get users with missing phone numbers
$stmt = $pdo->prepare("SELECT id, full_name, email, user_type, phone FROM users WHERE phone IS NULL OR phone = ''");
$stmt->execute();
$missing_phone = $stmt->fetchAll();

// Get shops with missing documents
$stmt = $pdo->prepare("
    SELECT s.user_id, u.full_name, u.email 
    FROM shops s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.business_license_image IS NULL OR s.business_license_image = ''
");
$stmt->execute();
$missing_docs = $stmt->fetchAll();

// Get all users
$stmt = $pdo->prepare("SELECT id, full_name, email, user_type FROM users ORDER BY created_at DESC");
$stmt->execute();
$all_users = $stmt->fetchAll();

// Handle sending notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $recipient_type = $_POST['recipient_type'];
    $specific_user_id = $_POST['specific_user_id'] ?? null;
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $notification_type = $_POST['notification_type'] ?? 'general';
    
    if (empty($title) || empty($message)) {
        $error = 'Please fill in both title and message.';
    } else {
        $sent_count = 0;
        
        if ($recipient_type === 'all') {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE status = 'active'");
            $stmt->execute();
            $users_list = $stmt->fetchAll();
            foreach ($users_list as $user) {
                $stmt2 = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                    VALUES (?, ?, ?, ?, NULL, 0, NOW())
                ");
                $stmt2->execute([$user['id'], $notification_type, $title, $message]);
                $sent_count++;
            }
            $success = "Notification sent to $sent_count users.";
            
        } elseif ($recipient_type === 'missing_phone') {
            foreach ($missing_phone as $user) {
                $stmt2 = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                    VALUES (?, 'missing_info', ?, ?, 'edit_profile.php', 0, NOW())
                ");
                $stmt2->execute([$user['id'], $title, $message]);
                $sent_count++;
            }
            $success = "Reminder sent to $sent_count users with missing phone numbers.";
            
        } elseif ($recipient_type === 'missing_docs') {
            foreach ($missing_docs as $shop) {
                $stmt2 = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                    VALUES (?, 'missing_info', ?, ?, 'shop_verification.php', 0, NOW())
                ");
                $stmt2->execute([$shop['user_id'], $title, $message]);
                $sent_count++;
            }
            $success = "Reminder sent to $sent_count shops with missing documents.";
            
        } elseif ($recipient_type === 'specific' && $specific_user_id) {
            $stmt2 = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                VALUES (?, ?, ?, ?, NULL, 0, NOW())
            ");
            $stmt2->execute([$specific_user_id, $notification_type, $title, $message]);
            $success = 'Notification sent to selected user.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notifications - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-gradient { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); }
        .form-input { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; }
        .form-input:focus { outline: none; border-color: #f97316; }
        .form-textarea { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; }
        .form-textarea:focus { outline: none; border-color: #f97316; }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'includes/navbar.php'; ?>

<div class="admin-gradient text-white py-6">
    <div class="max-w-7xl mx-auto px-6">
        <h1 class="text-2xl font-bold">Send Notifications</h1>
        <p class="text-gray-300 text-sm mt-1">Notify users about missing information or platform updates</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-6 py-8">
    
    <!-- Stats Cards -->
    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-orange-500">
            <p class="text-gray-500 text-xs">Users Missing Phone</p>
            <p class="text-2xl font-bold text-orange-600"><?php echo count($missing_phone); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-blue-500">
            <p class="text-gray-500 text-xs">Shops Missing Documents</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo count($missing_docs); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 border-green-500">
            <p class="text-gray-500 text-xs">Total Active Users</p>
            <p class="text-2xl font-bold text-green-600"><?php echo count($all_users); ?></p>
        </div>
    </div>

    <!-- Notification Form -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h2 class="font-bold text-gray-800">Compose Notification</h2>
        </div>
        <div class="p-6">
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Recipient Type</label>
                    <select name="recipient_type" id="recipient_type" class="form-input" onchange="toggleSpecificUser()">
                        <option value="all">All Active Users</option>
                        <option value="missing_phone">Users with Missing Phone Number</option>
                        <option value="missing_docs">Shops with Missing Documents</option>
                        <option value="specific">Specific User</option>
                    </select>
                </div>
                
                <div id="specific_user_div" style="display: none;" class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select User</label>
                    <select name="specific_user_id" class="form-input">
                        <option value="">Select a user...</option>
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?> (<?php echo $user['email']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notification Type</label>
                    <select name="notification_type" class="form-input">
                        <option value="general">General Announcement</option>
                        <option value="missing_info">Missing Information</option>
                        <option value="verification">Verification Required</option>
                        <option value="reminder">Reminder</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
                    <input type="text" name="title" required class="form-input" placeholder="e.g., Complete Your Profile">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Message</label>
                    <textarea name="message" rows="5" required class="form-textarea" placeholder="Write your message here..."></textarea>
                </div>
                
                <button type="submit" name="send_notification" class="bg-orange-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-orange-700 transition">Send Notification</button>
            </form>
        </div>
    </div>
    
    <!-- Quick Tips -->
    <div class="mt-6 bg-blue-50 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-lightbulb text-blue-600 text-xl"></i>
            <div>
                <h3 class="font-semibold text-blue-800 text-sm">Sample Messages</h3>
                <p class="text-blue-700 text-xs mt-1">"Please complete your phone number in profile settings"</p>
                <p class="text-blue-700 text-xs">"Your business license is pending. Please upload required documents."</p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSpecificUser() {
    var type = document.getElementById('recipient_type').value;
    var div = document.getElementById('specific_user_div');
    div.style.display = (type === 'specific') ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>