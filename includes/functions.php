<?php
/**
 * Core Functions - Findex Platform
 */

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $timestamp);
}

function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function getStatusBadge($status) {
    $badges = [
        'active' => 'bg-green-100 text-green-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'resolved' => 'bg-blue-100 text-blue-800',
        'closed' => 'bg-gray-100 text-gray-800'
    ];
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class='inline-flex px-2 py-1 text-xs font-semibold rounded-full $class'>" . ucfirst($status) . "</span>";
}

// ============================================
// CSRF PROTECTION FUNCTIONS 
// ============================================

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// ============================================
// RATE LIMITING FUNCTIONS
// ============================================

if (!function_exists('checkRateLimit')) {
    function checkRateLimit($ip, $action = 'login', $limit = 5, $minutes = 15) {
        global $pdo;
        
        try {
            // Check if rate_limit table exists, create if not
            $table_check = $pdo->query("SHOW TABLES LIKE 'rate_limits'");
            if ($table_check->rowCount() == 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS rate_limits (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        ip VARCHAR(45) NOT NULL,
                        action VARCHAR(50) NOT NULL,
                        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_ip_action (ip, action),
                        INDEX idx_attempt_time (attempt_time)
                    )
                ");
            }
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as attempts 
                FROM rate_limits 
                WHERE ip = ? AND action = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([$ip, $action, $minutes]);
            $attempts = $stmt->fetchColumn();
            
            return $attempts < $limit;
        } catch (Exception $e) {
            error_log("Rate limit check failed: " . $e->getMessage());
            return true; // Allow on error to not block users
        }
    }
}

if (!function_exists('recordFailedAttempt')) {
    function recordFailedAttempt($ip, $action = 'login') {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO rate_limits (ip, action) VALUES (?, ?)");
            return $stmt->execute([$ip, $action]);
        } catch (Exception $e) {
            error_log("Failed to record attempt: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('clearRateLimit')) {
    function clearRateLimit($ip, $action = 'login') {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip = ? AND action = ?");
            return $stmt->execute([$ip, $action]);
        } catch (Exception $e) {
            error_log("Failed to clear rate limit: " . $e->getMessage());
            return false;
        }
    }
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

if (!function_exists('notifyShopsAboutNewReport')) {
    function notifyShopsAboutNewReport($report_id, $report_title, $report_type, $location) {
        if (empty($report_id)) return 0;
        
        try {
            global $pdo;
            
            // Check if notifications table exists
            $table_check = $pdo->query("SHOW TABLES LIKE 'notifications'");
            if ($table_check->rowCount() == 0) return 0;
            
            // Get ALL shop users
            $stmt = $pdo->prepare("
                SELECT u.id, u.full_name, u.email
                FROM users u
                WHERE u.user_type = 'shop' AND u.status = 'active'
            ");
            $stmt->execute();
            $shops = $stmt->fetchAll();
            
            if (empty($shops)) {
                error_log("No shop users found in database");
                return 0;
            }
            
            $type_text = ucfirst($report_type);
            $count = 0;
            
            foreach ($shops as $shop) {
                $stmt2 = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                    VALUES (?, 'new_report', ?, ?, ?, NOW())
                ");
                $title = "New {$type_text} Report Posted";
                $message = "A new {$type_text} report '{$report_title}' has been posted in {$location}. Click to view details.";
                $link = "view_report.php?id={$report_id}";
                
                if ($stmt2->execute([$shop['id'], $title, $message, $link])) {
                    $count++;
                }
            }
            
            error_log("Notified {$count} shop users about new report");
            return $count;
            
        } catch (Exception $e) {
            error_log("notifyShopsAboutNewReport failed: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('notifyUser')) {
    function notifyUser($user_id, $type, $title, $message, $link = null) {
        if (empty($user_id)) return false;
        
        try {
            global $pdo;
            
            // Check if notifications table exists
            $table_check = $pdo->query("SHOW TABLES LIKE 'notifications'");
            if ($table_check->rowCount() == 0) return false;
            
            // Insert notification
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            return $stmt->execute([$user_id, $type, $title, $message, $link]);
            
        } catch (Exception $e) {
            error_log("notifyUser failed: " . $e->getMessage());
            return false;
        }
    }
}

?>


<?php
// Add this function to your existing functions.php file

/**
 * Create a notification for a user
 * 
 * @param int $user_id The user ID to notify
 * @param string $type Notification type (verification, order, message, etc.)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $link Optional link to redirect to
 * @return bool True if successful, false otherwise
 */
function createNotification($user_id, $type, $title, $message, $link = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        return $stmt->execute([$user_id, $type, $title, $message, $link]);
    } catch (PDOException $e) {
        error_log("Notification creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notifications count for a user
 */
function getUnreadNotificationsCount($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notification_id, $user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    return $stmt->execute([$notification_id, $user_id]);
}
?>