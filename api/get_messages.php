<?php
include("../includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uid = $_SESSION['user_id'] ?? 0;
$other = $_GET['user'] ?? 0;

$res = $conn->query("
SELECT * FROM messages 
WHERE (sender_id='$uid' AND receiver_id='$other') 
   OR (sender_id='$other' AND receiver_id='$uid')
ORDER BY id ASC
");

while($row = $res->fetch_assoc()){

    $class = ($row['sender_id'] == $uid) ? "me" : "other";

    echo "<div class='message $class'>{$row['message']}</div>";
}