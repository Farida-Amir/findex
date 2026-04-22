<?php
include("../includes/db.php");
session_start();

$uid = $_SESSION['user_id'] ?? 1;

$conn->query("UPDATE users SET last_active = NOW() WHERE id = $uid");
?>