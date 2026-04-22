<?php
include("../includes/db.php");
session_start();

$uid = $_SESSION['user_id'] ?? 1;
$receiver = $_POST['receiver'];

$conn->query("DELETE FROM typing WHERE user_id = $uid");
$conn->query("INSERT INTO typing (user_id, receiver_id) VALUES ($uid, $receiver)");
?>