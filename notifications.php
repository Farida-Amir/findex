<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['mark_all'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header('Location: notifications.php');
    exit();
}

// Mark single as read
if (isset($_GET['read']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $user_id]);
}

// Get notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unread_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<?php include 'includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4 text-white">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">Notifications</h1>
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_all=1" class="text-sm bg-white/20 px-3 py-1 rounded-full hover:bg-white/30 transition">
                        Mark all as read
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="divide-y">
            <?php if (empty($notifications)): ?>
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-5xl mb-3 text-gray-300"></i>
                    <p class="text-lg">No notifications yet</p>
                    <p class="text-sm mt-1">When you receive updates, they'll appear here</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="p-5 hover:bg-gray-50 transition <?php echo !$notif['is_read'] ? 'bg-orange-50' : ''; ?>">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center
                                    <?php echo $notif['type'] === 'claim' ? 'bg-purple-100 text-purple-600' : 
                                           ($notif['type'] === 'match' ? 'bg-green-100 text-green-600' : 
                                           ($notif['type'] === 'verification' ? 'bg-blue-100 text-blue-600' : 'bg-orange-100 text-orange-600')); ?>">
                                    <i class="fas 
                                        <?php echo $notif['type'] === 'claim' ? 'fa-gavel' : 
                                               ($notif['type'] === 'match' ? 'fa-handshake' : 
                                               ($notif['type'] === 'verification' ? 'fa-check-circle' : 'fa-bell')); ?>">
                                    </i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($notif['title']); ?></h4>
                                    <span class="text-xs text-gray-400"><?php echo timeAgo($notif['created_at']); ?></span>
                                </div>
                                <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <?php if ($notif['link']): ?>
                                    <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="text-orange-600 text-sm mt-2 inline-block hover:underline">
                                        View details →
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <a href="?read=1&id=<?php echo $notif['id']; ?>" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-check-circle"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
</body>
</html>