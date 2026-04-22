<?php
include("../includes/db.php");

$q = $_GET['q'] ?? '';

$data = [
  "users" => [],
  "reports" => []
];

if($q){

  // USERS
  $users = $conn->query("SELECT id,name FROM users WHERE name LIKE '%$q%'");
  while($u = $users->fetch_assoc()){
    $data["users"][] = $u;
  }

  // REPORTS
  $reports = $conn->query("
    SELECT * FROM reports 
    WHERE title LIKE '%$q%' OR description LIKE '%$q%'
  ");

  while($r = $reports->fetch_assoc()){
    $data["reports"][] = $r;
  }
}

echo json_encode($data);