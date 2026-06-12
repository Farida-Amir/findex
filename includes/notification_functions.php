<?php

function createNotification($user_id, $type, $title, $message, $link = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        return $stmt->execute([$user_id, $type, $title, $message, $link]);
    } catch (Exception $e) {
        error_log("Notification failed: " . $e->getMessage());
        return false;
    }
}

function getUnreadCount($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function markAsRead($notification_id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    return $stmt->execute([$notification_id, $user_id]);
}

function markAllAsRead($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}
?>