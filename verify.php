<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$token = $_GET['token'] ?? '';
$message = '';
$error = '';

if (empty($token)) {
    $error = 'Invalid verification link.';
} else {
    if (verifyUser($token)) {
        $message = 'Email verified successfully! You can now login.';
    } else {
        $error = 'Invalid or expired verification link.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Findex Trial</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <?php if ($message): ?>
            <div class="mb-4">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-check text-green-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Email Verified!</h2>
                <p class="text-gray-600 mb-6"><?= htmlspecialchars($message) ?></p>
                <a href="login.php" class="inline-block bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                    Proceed to Login
                </a>
            </div>
            <?php else: ?>
            <div class="mb-4">
                <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Verification Failed</h2>
                <p class="text-gray-600"><?= htmlspecialchars($error) ?></p>
                <a href="register.php" class="inline-block mt-6 text-purple-600 hover:text-purple-700">
                    Register a new account →
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>