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
    <a href="/Findex/report.php">Reports</a>
    <a href="/Findex/chat.php">Chat</a>
    <a href="/Findex/search.php">Search</a>
    <a href="/Findex/logout.php">Logout</a>
  </aside>

  <!-- CHAT -->
  <div class="dashboard-content">

    <h1>Chat</h1>

    <div class="chat-container">

      <!-- USERS LIST -->
      <div class="users-list" id="usersList"></div>

      <!-- CHAT BOX -->
      <div class="chat-box">

        <div id="messages" class="messages"></div>

        <div class="chat-input">
          <input type="text" id="msg" placeholder="Type a message...">
          <button onclick="sendMessage()">Send</button>
        </div>

      </div>

    </div>

  </div>

</div>

<script src="/Findex/js/chat.js"></script>

<?php include("includes/footer.php"); ?>