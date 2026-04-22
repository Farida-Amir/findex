<?php
include("../includes/db.php");

$uid = $_GET['user'];

$res = $conn->query("SELECT * FROM typing WHERE receiver_id = $uid");

if ($res->num_rows > 0) {
    echo "typing...";
}
?>