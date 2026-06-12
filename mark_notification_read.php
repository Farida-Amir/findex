<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
}

// Redirect back
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'notifications.php'));
exit();
?>