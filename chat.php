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

    <div class="page-header">
      <h1>Messages</h1>
      <p>Connect and communicate in real-time</p>
    </div>

    <div class="chat-layout enhanced-chat">

      <!-- USERS LIST -->
      <div class="users-list">
        <h3 class="chat-title">Contacts</h3>
        <div id="usersList"></div>
      </div>

      <!-- CHAT AREA -->
      <div class="chat-area">

        <!-- HEADER -->
        <div class="chat-header">
          <h3 id="chatWith">Select a conversation</h3>
        </div>

        <!-- MESSAGES -->
        <div id="messages" class="messages"></div>

        <!-- INPUT -->
        <div class="chat-input">
          <input type="text" id="msg" placeholder="Write a message...">
          <button class="btn" onclick="sendMessage()">Send</button>
        </div>

      </div>

    </div>

  </div>

</div>

<script src="/Findex/js/chat.js"></script>

<?php include("includes/footer.php"); ?>