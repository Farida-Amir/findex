<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])){
  header("Location: login.php");
  exit;
}

include("includes/header.php");
include("includes/db.php");
?>

<div class="dashboard">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <h3>Menu</h3>

    <a href="/Findex/dashboard.php">Overview</a>
    <a href="/Findex/report.php" class="active">Reports</a>
    <a href="/Findex/chat.php">Chat</a>
    <a href="/Findex/search.php">Search</a>
    <a href="/Findex/logout.php">Logout</a>
  </aside>

  <!-- MAIN -->
  <div class="dashboard-content">

    <!-- HEADER -->
    <div class="page-header">
      <h1>Reports</h1>
      <p>Manage your submitted reports and track items efficiently</p>
    </div>

    <!-- SEARCH -->
    <div class="card report-card-section glass">
      <h3>Search Reports</h3>

      <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by title or description...">
        <button class="btn">Search</button>
      </form>
    </div>

    <!-- FORM -->
    <div class="card report-card-section glass">
      <h3>Submit New Report</h3>

      <form method="POST" class="report-form">

        <div class="form-group">
          <label>Item Title</label>
          <input type="text" name="title" placeholder="e.g. Gold necklace, silver ring..." required>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="desc" placeholder="Add details like location, time, or unique features..." required></textarea>
        </div>

        <div class="form-row">
          <div class="form-group small">
            <label>Type</label>
            <select name="type">
              <option>Lost</option>
              <option>Found</option>
              <option>Stolen</option>
            </select>
          </div>

          <button name="submit" class="btn submit-btn">Submit Report</button>
        </div>

      </form>
    </div>

    <!-- LIST -->
    <div class="card report-card-section glass">
      <h3>Your Reports</h3>

      <div class="reports-list">

      <?php
      $uid = $_SESSION['user_id'];
      $res = $conn->query("SELECT * FROM reports WHERE user_id='$uid' ORDER BY id DESC");

      while($row = $res->fetch_assoc()){
        echo "
          <div class='report-item enhanced'>
            <div class='report-header'>
              <h4>{$row['title']}</h4>
              <span class='report-type {$row['type']}'>{$row['type']}</span>
            </div>

            <p>{$row['description']}</p>

            <div class='report-footer'>
              <span class='report-time'>Submitted recently</span>
            </div>
          </div>
        ";
      }
      ?>

      </div>

    </div>

  </div>

</div>

<?php
if(isset($_POST['submit'])){
  $uid = $_SESSION['user_id'];
  $title = $_POST['title'];
  $desc = $_POST['desc'];
  $type = $_POST['type'];

  $conn->query("INSERT INTO reports(user_id,title,description,type)
                VALUES('$uid','$title','$desc','$type')");

  echo "<script>location.reload();</script>";
}
?>

<?php include("includes/footer.php"); ?>