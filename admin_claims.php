<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireUserType(['admin', 'moderator']);

$status_filter = $_GET['status'] ?? 'all';
$error = '';
$success = '';

// Handle claim escalation or override
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'])) {
    $claim_id = (int)$_POST['claim_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    
    if ($action === 'escalate') {
        $stmt = $pdo->prepare("UPDATE claims SET status = 'escalated', moderator_notes = CONCAT(IFNULL(moderator_notes, ''), '\n[ADMIN] ', ?) WHERE id = ?");
        $stmt->execute([$notes, $claim_id]);
        $success = 'Claim escalated for review.';
    } elseif ($action === 'override_approve') {
        $stmt = $pdo->prepare("UPDATE claims SET status = 'approved', resolved_by = ?, resolved_at = NOW(), moderator_notes = CONCAT(IFNULL(moderator_notes, ''), '\n[ADMIN OVERRIDE] ', ?) WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $notes, $claim_id]);
        
        // Also mark the report as resolved
        $stmt2 = $pdo->prepare("UPDATE reports r JOIN claims c ON c.report_id = r.id SET r.status = 'resolved' WHERE c.id = ?");
        $stmt2->execute([$claim_id]);
        $success = 'Claim approved by admin.';
    } elseif ($action === 'override_reject') {
        $stmt = $pdo->prepare("UPDATE claims SET status = 'rejected', resolved_by = ?, resolved_at = NOW(), moderator_notes = CONCAT(IFNULL(moderator_notes, ''), '\n[ADMIN REJECT] ', ?) WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $notes, $claim_id]);
        $success = 'Claim rejected by admin.';
    }
}

// Build query with filters
$sql = "
    SELECT c.*, 
           r.title as report_title, 
           r.report_type,
           u1.full_name as claimant_name,
           u1.email as claimant_email,
           u2.full_name as owner_name,
           u2.email as owner_email
    FROM claims c
    JOIN reports r ON c.report_id = r.id
    JOIN users u1 ON c.claimant_user_id = u1.id
    JOIN users u2 ON r.user_id = u2.id
    WHERE 1=1
";

$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND c.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$claims = $stmt->fetchAll();

// Get statistics
$stats = [];
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'escalated' THEN 1 ELSE 0 END) as escalated
    FROM claims
");
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Manage Claims - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: #f5f5f0; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .status-escalated { background: #fef3c7; color: #d97706; }
        .status-under_review { background: #dbeafe; color: #2563eb; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Manage Claims</h1>
        <p class="text-gray-500 text-sm">Review, moderate, and resolve disputes across all claims</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
            <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="text-xs text-gray-500">Total Claims</div>
        </div>
        <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
            <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending'] ?? 0; ?></div>
            <div class="text-xs text-gray-500">Pending</div>
        </div>
        <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
            <div class="text-2xl font-bold text-green-600"><?php echo $stats['approved'] ?? 0; ?></div>
            <div class="text-xs text-gray-500">Approved</div>
        </div>
        <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
            <div class="text-2xl font-bold text-red-600"><?php echo $stats['rejected'] ?? 0; ?></div>
            <div class="text-xs text-gray-500">Rejected</div>
        </div>
        <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
            <div class="text-2xl font-bold text-purple-600"><?php echo $stats['escalated'] ?? 0; ?></div>
            <div class="text-xs text-gray-500">Escalated</div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status Filter</label>
                <select name="status" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Claims</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="escalated" <?php echo $status_filter === 'escalated' ? 'selected' : ''; ?>>Escalated</option>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
            </div>
            <div>
                <a href="admin_claims.php" class="text-gray-500 hover:text-orange-600 text-sm">Clear Filters</a>
            </div>
        </form>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 text-sm">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 text-sm">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Claims Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Claimant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($claims)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2 block"></i>
                                <p>No claims found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($claims as $claim): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm">#<?php echo $claim['id']; ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($claim['report_title']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo ucfirst($claim['report_type']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium"><?php echo htmlspecialchars($claim['claimant_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($claim['claimant_email']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm"><?php echo htmlspecialchars($claim['owner_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($claim['owner_email']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="status-badge status-<?php echo $claim['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $claim['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M d, Y', strtotime($claim['created_at'])); ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="view_claim.php?id=<?php echo $claim['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                                        <?php if ($claim['status'] === 'pending' || $claim['status'] === 'escalated'): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Override and approve this claim?')">
                                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                                <input type="hidden" name="action" value="override_approve">
                                                <input type="hidden" name="notes" value="Admin override approval">
                                                <button type="submit" class="text-green-600 hover:text-green-800 text-sm">Approve</button>
                                            </form>
                                            <form method="POST" class="inline" onsubmit="return confirm('Override and reject this claim?')">
                                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                                <input type="hidden" name="action" value="override_reject">
                                                <input type="hidden" name="notes" value="Admin override rejection">
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($claim['status'] !== 'escalated'): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Escalate this claim for admin review?')">
                                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                                <input type="hidden" name="action" value="escalate">
                                                <div class="inline relative group">
                                                    <input type="text" name="notes" placeholder="Reason" class="hidden group-hover:inline w-32 text-xs border rounded px-1">
                                                    <button type="submit" class="text-purple-600 hover:text-purple-800 text-sm">Escalate</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>