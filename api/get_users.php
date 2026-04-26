<?php
include("../includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uid = $_SESSION['user_id'] ?? 0;

$res = $conn->query("SELECT id, name FROM users WHERE id != '$uid'");

while($row = $res->fetch_assoc()){
    $name = htmlspecialchars($row['name']);

    echo "
    <div class='user' onclick='selectUser({$row['id']})'>
        <div class='user-card' data-name='{$name}'></div>
    </div>
    ";
}
?>