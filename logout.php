<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

session_destroy();
session_start();
$_SESSION['success'] = 'You have been logged out successfully.';
header('Location: login.php');
exit();
?>