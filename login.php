<?php include("includes/header.php"); ?>
<?php include("includes/db.php"); ?>

<div class="auth-container">

  <div class="auth-card">
    <h2>Login</h2>

    <form method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button name="login" class="btn">Login</button>
    </form>

    <p class="auth-switch">
      Don’t have an account? 
      <a href="register.php">Sign up</a>
    </p>
  </div>

</div>

<?php
if(isset($_POST['login'])){
  $email = $_POST['email'];
  $pass = $_POST['password'];

  $res = $conn->query("SELECT * FROM users WHERE email='$email'");
  $user = $res->fetch_assoc();

  if($user && password_verify($pass, $user['password'])){
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];

    header("Location: chat.php");
  } else {
    echo "<script>alert('Invalid email or password');</script>";
  }
}
?>