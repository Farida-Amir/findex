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
    <a href="/Findex/search.php" class="active">Search</a>
    <a href="/Findex/logout.php">Logout</a>
  </aside>

  <div class="dashboard-content">

    <div class="page-header">
      <h1>Search</h1>
      <p>Find users and reports instantly</p>
    </div>

    <!-- SEARCH -->
    <div class="card search-box">
      <input type="text" id="searchInput" placeholder="Search users or reports...">
    </div>

    <!-- TABS -->
    <div class="search-tabs">
      <button class="tab active" onclick="setTab('all')">All</button>
      <button class="tab" onclick="setTab('users')">Users</button>
      <button class="tab" onclick="setTab('reports')">Reports</button>
    </div>

    <!-- RESULTS -->
    <div id="results"></div>

  </div>

</div>

<script src="/Findex/js/search.js"></script>

<?php include("includes/footer.php"); ?>