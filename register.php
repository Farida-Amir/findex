<?php include("includes/header.php"); ?>
<?php include("includes/db.php"); ?>

<div class="auth-container">

  <div class="auth-card">
    <h2>Create Account</h2>

    <form method="POST">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button name="register" class="btn">Sign Up</button>
    </form>

    <p class="auth-switch">
      Already have an account? 
      <a href="login.php">Login</a>
    </p>
  </div>

</div>

<?php
if(isset($_POST['register'])){
  $name = $_POST['name'];
  $email = $_POST['email'];
  $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

  /* check if email exists */
  $check = $conn->query("SELECT * FROM users WHERE email='$email'");

  if($check->num_rows > 0){
    echo "<script>alert('Email already exists');</script>";
  } else {
    $conn->query("INSERT INTO users(name,email,password)
                  VALUES('$name','$email','$pass')");
    header("Location: login.php");
  }
}
?>