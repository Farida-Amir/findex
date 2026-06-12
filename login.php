<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Prevent browser caching 
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// If already logged in, redirect immediately
if (isLoggedIn()) {
    $user_type = getUserType();
    $redirect = match($user_type) {
        'admin' => 'dashboard_admin.php',
        'moderator' => 'moderator.php',
        'finance' => 'dashboard_finance.php',
        'shop' => 'dashboard_shop.php',
        default => 'dashboard_user.php'
    };
    header("Location: $redirect");
    exit();
}

$error = '';

// Handle login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        if (loginUser($email, $password)) {
            $user_type = getUserType();
            $redirect = match($user_type) {
                'admin' => 'dashboard_admin.php',
                'moderator' => 'moderator.php',
                'finance' => 'dashboard_finance.php',
                'shop' => 'dashboard_shop.php',
                default => 'dashboard_user.php'
            };
            header("Location: $redirect");
            exit();
        } else {
            $error = $_SESSION['error'] ?? 'Invalid email or password.';
            unset($_SESSION['error']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%);
            min-height: 100vh;
        }

        .login-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.08);
            padding: 40px;
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .logo-icon i {
            font-size: 28px;
            color: white;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .logo p {
            font-size: 13px;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.2s;
            background: #fafafa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #f97316;
            background: white;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.05);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
            cursor: pointer;
        }

        .remember input {
            width: 16px;
            height: 16px;
            margin: 0;
            cursor: pointer;
        }

        .forgot {
            font-size: 13px;
            color: #f97316;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 24px;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(249,115,22,0.25);
        }

        .signup {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .signup a {
            color: #f97316;
            font-weight: 600;
            text-decoration: none;
        }

        .signup a:hover {
            text-decoration: underline;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .success-msg {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .center {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
            padding: 20px;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="center">
    <div class="login-card">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-gem"></i>
            </div>
            <h1>Welcome Back</h1>
            <p>Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="Enter your email address">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <div class="form-options">
                <label class="remember">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="forgot_password.php" class="forgot">Forgot password?</a>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="signup">
            Don't have an account?
            <a href="register.php">Create Account</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>