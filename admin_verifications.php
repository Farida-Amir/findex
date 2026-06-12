<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();
requireUserType(['admin']);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle verification approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['request_id']) || !isset($_POST['action'])) {
        $_SESSION['error'] = 'Missing required fields';
        header('Location: admin_verifications.php');
        exit();
    }
    
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate action
    if (!in_array($action, ['approve', 'reject'])) {
        $_SESSION['error'] = 'Invalid action';
        header('Location: admin_verifications.php');
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get the request details first
        $stmt = $pdo->prepare("SELECT * FROM verification_requests WHERE id = ? AND status = 'pending'");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            throw new Exception('Verification request not found or already processed');
        }
        
        if ($action === 'approve') {
            // Update verification request
            $stmt = $pdo->prepare("
                UPDATE verification_requests 
                SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), admin_notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $request_id]);
            
            // Update user based on verification type
            if ($request['verification_type'] === 'national_id') {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET national_id_verified = 1, 
                        national_id_verified_at = NOW(), 
                        national_id_verified_by = ?,
                        is_verified = 1,
                        status = 'active'
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $request['user_id']]);
                
                createNotification($request['user_id'], 'verification', 'National ID Verified', 
                    'Your National ID has been verified. You can now fully use the platform.');
                    
            } elseif ($request['verification_type'] === 'business_license') {
                // Check if shop exists
                $stmt = $pdo->prepare("SELECT id FROM shops WHERE user_id = ?");
                $stmt->execute([$request['user_id']]);
                $shop = $stmt->fetch();
                
                if ($shop) {
                    $stmt = $pdo->prepare("
                        UPDATE shops 
                        SET is_approved = 1, 
                            verified_badge = 1, 
                            verification_level = 'verified',
                            approved_by = ?, 
                            approved_at = NOW(), 
                            approval_notes = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $notes, $request['user_id']]);
                }
                
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, status = 'active' WHERE id = ?");
                $stmt->execute([$request['user_id']]);
                
                createNotification($request['user_id'], 'verification', 'Business Verified', 
                    'Your business has been verified. You now have access to premium shop features.');
            }
            
            $_SESSION['success'] = 'Verification request approved.';
            
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("
                UPDATE verification_requests 
                SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), admin_notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $request_id]);
            
            createNotification($request['user_id'], 'verification', 'Verification Failed', 
                'Your verification request was rejected. Reason: ' . $notes);
            
            $_SESSION['success'] = 'Verification request rejected.';
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error processing request: ' . $e->getMessage();
    }
    
    header('Location: admin_verifications.php');
    exit();
}

// Get statistics with error checking
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM verification_requests
    ");
    $stats = $stmt->fetch();
    if (!$stats) {
        $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    }
} catch (PDOException $e) {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    $_SESSION['error'] = 'Error fetching statistics: ' . $e->getMessage();
}

// Get pending verification requests
try {
    $stmt = $pdo->prepare("
        SELECT v.*, u.full_name, u.email, u.user_type 
        FROM verification_requests v
        JOIN users u ON v.user_id = u.id
        WHERE v.status = 'pending'
        ORDER BY v.created_at ASC
    ");
    $stmt->execute();
    $pending_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $pending_requests = [];
    $_SESSION['error'] = 'Error fetching requests: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Management - Findex Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-orange { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .btn-primary { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .badge-pending { background: #fed7aa; color: #9a3412; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg border-b-4 border-orange-500">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-orange-600 text-2xl mr-2"></i>
                    <span class="font-bold text-xl">Verification Management</span>
                </div>
                <a href="admin.php" class="text-gray-600 hover:text-orange-600">← Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Display messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Total Requests</p>
                <p class="text-3xl font-bold text-gray-800"><?= $stats['total'] ?? 0 ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Pending</p>
                <p class="text-3xl font-bold text-yellow-600"><?= $stats['pending'] ?? 0 ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Approved</p>
                <p class="text-3xl font-bold text-green-600"><?= $stats['approved'] ?? 0 ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Rejected</p>
                <p class="text-3xl font-bold text-red-600"><?= $stats['rejected'] ?? 0 ?></p>
            </div>
        </div>

        <!-- Debug info (remove in production) -->
        <?php if (isset($_GET['debug'])): ?>
        <div class="bg-gray-100 p-4 rounded mb-4">
            <h3 class="font-bold">Debug Info:</h3>
            <pre><?php 
                echo "Pending Requests Count: " . count($pending_requests) . "\n";
                echo "Stats: " . print_r($stats, true);
            ?></pre>
        </div>
        <?php endif; ?>

        <!-- Pending Requests -->
        <div class="bg-white rounded-xl shadow-lg">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Pending Verification Requests</h2>
                <p class="text-gray-500 text-sm">Review and verify user identities and business licenses</p>
            </div>
            <div class="divide-y">
                <?php if (count($pending_requests) > 0): ?>
                    <?php foreach ($pending_requests as $request): ?>
                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <span class="badge-pending px-3 py-1 rounded-full text-sm">
                                        <i class="fas fa-clock mr-1"></i> Pending Review
                                    </span>
                                    <span class="text-sm text-gray-500">Request #<?= htmlspecialchars($request['id']) ?></span>
                                </div>
                                
                                <div class="grid md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p><strong>User:</strong> <?= htmlspecialchars($request['full_name']) ?></p>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($request['email']) ?></p>
                                        <p><strong>Type:</strong> <?= ucfirst(htmlspecialchars($request['user_type'])) ?></p>
                                    </div>
                                    <div>
                                        <p><strong>Verification Type:</strong> 
                                            <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded text-sm">
                                                <?= str_replace('_', ' ', ucfirst(htmlspecialchars($request['verification_type']))) ?>
                                            </span>
                                        </p>
                                        <p><strong>Document Number:</strong> <?= htmlspecialchars($request['document_number'] ?? 'N/A') ?></p>
                                        <p><strong>Submitted:</strong> <?= date('M d, Y H:i', strtotime($request['created_at'])) ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <?php if (!empty($request['document_path']) && file_exists($request['document_path'])): ?>
                                        <a href="<?= htmlspecialchars($request['document_path']) ?>" target="_blank" class="text-orange-600 hover:text-orange-700">
                                            <i class="fas fa-file-pdf mr-1"></i> View Document
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">
                                            <i class="fas fa-file-missing mr-1"></i> Document not found
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="ml-6">
                                <form method="POST" class="space-y-3" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['id']) ?>">
                                    <textarea name="notes" rows="2" class="w-64 border rounded-lg p-2 text-sm" placeholder="Verification notes..."></textarea>
                                    <div class="flex space-x-2">
                                        <button type="submit" name="action" value="approve" 
                                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" 
                                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                                            <i class="fas fa-times mr-1"></i> Reject
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-12 text-center text-gray-500">
                        <i class="fas fa-check-circle text-5xl text-green-500 mb-3"></i>
                        <p class="text-lg">No pending verification requests</p>
                        <p class="text-sm mt-1">All caught up!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add client-side form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('button[type="submit"]:focus')?.value;
                if (action === 'reject') {
                    const notes = this.querySelector('textarea[name="notes"]').value;
                    if (!notes.trim()) {
                        e.preventDefault();
                        alert('Please provide rejection notes before rejecting.');
                    }
                }
            });
        });
    </script>
</body>
</html>