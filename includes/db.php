<?php
$conn = new mysqli("localhost", "root", "", "findex");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>