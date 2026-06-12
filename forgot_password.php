<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$demo_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            // For demo, show the reset link (in production, send email)
            $reset_link = SITE_URL . "reset_password.php?token=" . $token;
            $demo_link = $reset_link;
            $success = "demo_mode"; // Flag for demo mode
        } else {
            // Don't reveal if email exists for security
            $success = 'If an account exists with that email, you will receive a password reset link.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
        
        /* Additional styles for better button display */
        .btn-orange {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            transition: all 0.3s ease;
        }
        .btn-orange:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2);
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="min-h-screen flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="w-16 h-16 orange-gradient rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xl">
                <i class="fas fa-key text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Forgot Password?</h1>
            <p class="text-gray-500 text-sm mt-2">Enter your email to reset your password</p>
        </div>
        
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success === "demo_mode"): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm text-blue-800 mb-3">
                                <strong>Demo Mode Activated</strong><br>
                                Your password reset link has been generated. Click the button below to reset your password:
                            </p>
                            <a href="<?php echo htmlspecialchars($demo_link); ?>" 
                               class="btn-orange inline-flex items-center justify-center w-full text-white px-4 py-3 rounded-lg text-sm font-semibold text-center">
                                <i class="fas fa-key mr-2"></i> 
                                Reset Your Password Now
                            </a>
                            <p class="text-xs text-gray-500 mt-3 text-center">
                                <i class="fas fa-envelope mr-1"></i> 
                                In production, this link would be sent to your email address.
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif ($success && $success !== "demo_mode"): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition">
                </div>
                
                <button type="submit" class="orange-gradient w-full text-white py-3 rounded-xl font-semibold hover:shadow-lg transition transform hover:-translate-y-0.5">
                    <i class="fas fa-paper-plane mr-2"></i> Send Reset Link
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="login.php" class="text-orange-600 text-sm hover:underline inline-flex items-center">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>