<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'findex_trial');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site configuration
define('SITE_URL', 'http://localhost/findex_trial/');
define('SITE_NAME', 'Findex');
define('SITE_TAGLINE', 'Recover Lost & Stolen Jewelry');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session security settings - ADDED
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 when using HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically for security - ADDED
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function getUserData($user_id = null) {
    global $pdo;
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if (!$user_id) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}
?>