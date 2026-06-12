<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verify token
$stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = 'Invalid or expired reset link. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        
        if ($stmt->execute([$hash, $token])) {
            $success = 'Password reset successfully! You can now login with your new password.';
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="w-16 h-16 orange-gradient rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl">
                <i class="fas fa-lock text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Reset Password</h1>
            <p class="text-gray-500 text-sm mt-2">Create a new password for your account</p>
        </div>
        
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 text-sm"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                    <?php echo $success; ?>
                    <div class="mt-3">
                        <a href="login.php" class="text-green-700 font-semibold hover:underline">Login Now →</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!$error && !$success && $user): ?>
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-orange-500">
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-orange-500">
                    </div>
                    
                    <button type="submit" class="orange-gradient w-full text-white py-3 rounded-xl font-semibold hover:shadow-lg transition">
                        <i class="fas fa-save mr-2"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>