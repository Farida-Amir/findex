<?php
include("../includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uid = $_SESSION['user_id'] ?? 0;

$res = $conn->query("SELECT id, name FROM users WHERE id != '$uid'");

while($row = $res->fetch_assoc()){
    echo "<div class='user' onclick='selectUser({$row['id']})'>{$row['name']}</div>";
}