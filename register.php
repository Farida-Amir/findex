<?php
include("includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== HANDLE REGISTER ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordRaw = $_POST['password'] ?? '';

    if (!$name || !$email || !$passwordRaw) {
        echo "Please fill all fields";
        exit;
    }

    if (strlen($passwordRaw) < 6) {
        echo "Password must be at least 6 characters";
        exit;
    }

    $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

    // check if email exists
    $check = $conn->query("SELECT * FROM users WHERE email='$email'");

    if ($check && $check->num_rows > 0) {
        echo "Email already exists";
    } else {

        $conn->query("INSERT INTO users(name,email,password)
                      VALUES('$name','$email','$password')");

        echo "success"; // ✅ REQUIRED for JS
    }

    exit;
}

/* ===== BLOCK DIRECT ACCESS ===== */
header("Location: auth.php");
exit;
?>