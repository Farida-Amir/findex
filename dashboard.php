<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])){
  header("Location: login.php");
  exit;
}

include("includes/header.php");
?>

<div class="dashboard">

  <aside class="sidebar">
    <h3>Menu</h3>

    <a href="/Findex/dashboard.php">Overview</a>
    <a href="/Findex/report.php">Reports</a>
    <a href="/Findex/chat.php">Chat</a>
    <a href="/Findex/search.php">Search</a>
    <a href="/Findex/logout.php">Logout</a>
  </aside>

  <div class="dashboard-content">

    <h1>Dashboard</h1>
    <p class="dash-subtitle">Welcome back, <?= htmlspecialchars($_SESSION['name']); ?></p>

    <div class="dash-grid">

      <div class="dash-card">
        <h3>My Reports</h3>
        <p>View and manage your submitted reports.</p>
        <a href="/Findex/report.php" class="btn small">Open</a>
      </div>

      <div class="dash-card">
        <h3>Messages</h3>
        <p>Access your conversations with users.</p>
        <a href="/Findex/chat.php" class="btn small">Open</a>
      </div>

      <div class="dash-card">
        <h3>Search Items</h3>
        <p>Search and browse available items.</p>
        <a href="/Findex/search.php" class="btn small">Search</a>
      </div>

    </div>

    <div class="activity-section">
      <h2>Recent Activity</h2>

      <div class="activity-box" id="activityBox">
        Loading activity...
      </div>
    </div>

  </div>

</div>

<?php include("includes/footer.php"); ?>