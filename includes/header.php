<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Findex</title>
  <link rel="stylesheet" href="/Findex/css/style.css?v=2">
</head>

<body>
<div class="page-wrapper">

<nav class="navbar">
  <a href="/Findex/index.php" class="logo">Findex</a>

  <div class="nav-links">
    <a href="/Findex/index.php">Home</a>

    <?php if(isset($_SESSION['user_id'])): ?>
      <a href="/Findex/chat.php">Chat</a>
      <a href="/Findex/dashboard.php">Dashboard</a>
      <a href="/Findex/report.php">Reports</a>
      <a href="/Findex/search.php">Search</a>

      <span>Hi, <?= htmlspecialchars($_SESSION['name']); ?></span>
      <a href="/Findex/logout.php">Logout</a>
    <?php else: ?>
      <a href="/Findex/auth.php">Login</a>
      <a href="/Findex/auth.php">Register</a>
    <?php endif; ?>
  </div>
</nav>

<main>