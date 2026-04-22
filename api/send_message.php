<?php
include("../includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uid = $_SESSION['user_id'] ?? 0;
$msg = $_POST['message'] ?? '';
$receiver = $_POST['receiver'] ?? 0;

if($msg && $receiver){
    $conn->query("
        INSERT INTO messages (sender_id, receiver_id, message)
        VALUES ('$uid','$receiver','$msg')
    ");
}