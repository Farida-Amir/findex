<?php
require_once __DIR__ . '/../config/db.php';


if (!defined('SITE_URL')) {
    define('SITE_URL', '/');
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type
 */
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get current user name
 */
function getUserName() {
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * Get current user email
 */
function getUserEmail() {
    return $_SESSION['user_email'] ?? '';
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to access this page.';
        header('Location: ' . SITE_URL . 'login.php');
        exit();
    }
}

/**
 * Require specific user type
 */
function requireUserType($types) {
    requireLogin();
    $types = is_array($types) ? $types : [$types];
    if (!in_array(getUserType(), $types)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        
        // Redirect based on user type
        $user_type = getUserType();
        if ($user_type === 'user') {
            $redirect = 'dashboard_user.php';
        } elseif ($user_type === 'shop') {
            $redirect = 'dashboard_shop.php';
        } elseif ($user_type === 'admin') {
            $redirect = 'dashboard_admin.php';
        } elseif ($user_type === 'moderator') {
            $redirect = 'moderator.php';
        } elseif ($user_type === 'finance') {
            $redirect = 'dashboard_finance.php';
        } else {
            $redirect = 'index.php';
        }
        
        header('Location: ' . SITE_URL . $redirect);
        exit();
    }
}


function loginUser($email, $password, $remember = false) {
    global $pdo;
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = 'No account found with this email address.';
        return false;
    }
    
    // Check if user is suspended
    if ($user['status'] === 'suspended') {
        $_SESSION['error'] = 'Your account has been suspended. Please contact support.';
        return false;
    }
    
    // Check if user is pending approval
    if ($user['status'] === 'pending') {
        $_SESSION['error'] = 'Your account is pending approval. You will be notified once approved.';
        return false;
    }
    
    // Verify password
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        return true;
    }
    
    $_SESSION['error'] = 'Incorrect password. Please try again.';
    return false;
}

/**
 * Register new user
 */
function registerUser($email, $password, $full_name, $phone, $user_type = 'user', $national_id = null) {
    global $pdo;
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered. Please login.'];
    }
    
    // Validate phone (Egyptian format)
    if (!preg_match('/^01[0-9]{9}$/', $phone)) {
        return ['success' => false, 'message' => 'Please enter a valid Egyptian phone number (e.g., 01234567890).'];
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Shops require admin approval, regular users are active immediately
    $status = ($user_type === 'shop') ? 'pending' : 'active';
    
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, full_name, phone, user_type, national_id_number, status, is_verified, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    
    if ($stmt->execute([$email, $password_hash, $full_name, $phone, $user_type, $national_id, $status])) {
        $user_id = $pdo->lastInsertId();
        
        if ($user_type === 'shop') {
            $stmt = $pdo->prepare("INSERT INTO shops (user_id, business_name, is_approved, verification_level) VALUES (?, ?, 0, 'pending')");
            $stmt->execute([$user_id, $full_name]);
            
            return ['success' => true, 'message' => 'Registration successful! Your shop account is pending admin approval. You will be notified once approved.', 'user_id' => $user_id];
        }
        
        return ['success' => true, 'message' => 'Registration successful! Please login.', 'user_id' => $user_id];
    }
    
    return ['success' => false, 'message' => 'Registration failed. Please try again.'];
}

/**
 * Logout user
 */
function logoutUser() {
    session_destroy();
    session_start();
    $_SESSION['success'] = 'You have been successfully logged out.';
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}
?>