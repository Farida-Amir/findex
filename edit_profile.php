<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Redirect to profile page with edit mode enabled
header('Location: profile.php?id=' . $user_id . '&edit=1');
exit();
?>