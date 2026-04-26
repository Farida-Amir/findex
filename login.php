<?php
include("includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== HANDLE LOGIN (FROM AUTH PAGE) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        echo "Please fill all fields";
        exit;
    }

    $res = $conn->query("SELECT * FROM users WHERE email='$email'");

    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];

            echo "success"; // ✅ REQUIRED for JS
        } else {
            echo "Wrong password";
        }

    } else {
        echo "User not found";
    }

    exit;
}

/* ===== BLOCK DIRECT ACCESS ===== */
header("Location: auth.php");
exit;
?>