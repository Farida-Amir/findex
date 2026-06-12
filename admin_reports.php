<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();
requireUserType(['admin', 'moderator']);

// Helper function to truncate text
function truncateText($text, $length = 50) {
    if (empty($text)) {
        return '';
    }
    if (strlen($text) <= $length) {
        return htmlspecialchars($text);
    }
    return htmlspecialchars(substr($text, 0, $length)) . '...';
}

// Helper function for status badges
function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-clock mr-1"></i> Pending</span>';
        case 'approved':
            return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i> Approved</span>';
        case 'rejected':
            return '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i> Rejected</span>';
        case 'investigating':
            return '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-search mr-1"></i> Investigating</span>';
        case 'resolved':
            return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i> Resolved</span>';
        case 'dismissed':
            return '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800"><i class="fas fa-ban mr-1"></i> Dismissed</span>';
        default:
            return '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">' . htmlspecialchars($status) . '</span>';
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !empty($_POST['action'])) {
    $report_ids = $_POST['report_ids'] ?? [];
    $action = $_POST['action'];
    
    if (!empty($report_ids)) {
        $placeholders = str_repeat('?,', count($report_ids) - 1) . '?';
        
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE reports SET moderation_status = 'approved', moderated_by = ?, moderated_at = NOW() WHERE id IN ($placeholders)");
            $params = array_merge([$_SESSION['user_id']], $report_ids);
            $stmt->execute($params);
            $_SESSION['success'] = count($report_ids) . ' report(s) approved.';
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE reports SET moderation_status = 'rejected', status = 'closed', moderated_by = ?, moderated_at = NOW() WHERE id IN ($placeholders)");
            $params = array_merge([$_SESSION['user_id']], $report_ids);
            $stmt->execute($params);
            $_SESSION['success'] = count($report_ids) . ' report(s) rejected.';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM reports WHERE id IN ($placeholders)");
            $stmt->execute($report_ids);
            $_SESSION['success'] = count($report_ids) . ' report(s) deleted.';
        }
        header('Location: admin_reports.php');
        exit();
    }
}

// Filters
$status_filter = $_GET['status'] ?? 'pending';
$type_filter = $_GET['type'] ?? '';

$sql = "SELECT r.*, u.full_name as user_name FROM reports r JOIN users u ON r.user_id = u.id WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND r.moderation_status = ?";
    $params[] = $status_filter;
} elseif ($status_filter === 'all') {
    // Show all reports, no status filter
}

if (!empty($type_filter)) {
    $sql .= " AND r.report_type = ?";
    $params[] = $type_filter;
}

$sql .= " ORDER BY r.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Get unique report types for filter dropdown
$typeStmt = $pdo->query("SELECT DISTINCT report_type FROM reports WHERE report_type IS NOT NULL AND report_type != ''");
$reportTypes = $typeStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-gem text-purple-600 text-2xl mr-2"></i>
                    <span class="font-bold text-xl">Reports Moderation</span>
                </div>
                <div class="flex space-x-4">
                    <a href="admin.php" class="text-gray-600 hover:text-purple-600">Dashboard</a>
                    <a href="moderator.php" class="text-gray-600 hover:text-purple-600">Moderator View</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="border rounded-lg px-3 py-2">
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="border rounded-lg px-3 py-2">
                        <option value="">All Types</option>
                        <?php foreach ($reportTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type['report_type']) ?>" <?= $type_filter === $type['report_type'] ? 'selected' : '' ?>>
                            <?= ucfirst(htmlspecialchars($type['report_type'])) ?>
                        </option>
                        <?php endforeach; ?>
                        <!-- Default options if no types in database -->
                        <option value="lost" <?= $type_filter === 'lost' ? 'selected' : '' ?>>Lost</option>
                        <option value="stolen" <?= $type_filter === 'stolen' ? 'selected' : '' ?>>Stolen</option>
                        <option value="found" <?= $type_filter === 'found' ? 'selected' : '' ?>>Found</option>
                        <option value="suspicious" <?= $type_filter === 'suspicious' ? 'selected' : '' ?>>Suspicious</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </div>
                <div>
                    <a href="admin_reports.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        <i class="fas fa-sync-alt mr-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <form method="POST" id="bulkForm">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <input type="checkbox" id="selectAll" class="rounded">
                        <span class="text-sm text-gray-600">Select All</span>
                        
                        <select name="action" class="border rounded-lg px-3 py-1 text-sm">
                            <option value="">Bulk Actions</option>
                            <option value="approve">Approve Selected</option>
                            <option value="reject">Reject Selected</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                        
                        <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded text-sm hover:bg-blue-700">
                            Apply
                        </button>
                    </div>
                    <div class="text-sm text-gray-500">
                        Total: <?= count($reports) ?> report(s)
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 w-10"></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($reports) > 0): ?>
                                <?php foreach ($reports as $report): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4">
                                        <input type="checkbox" name="report_ids[]" value="<?= $report['id'] ?>" class="report-checkbox rounded">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $report['id'] ?></td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($report['title']) ?></div>
                                        <?php if (!empty($report['description'])): ?>
                                        <div class="text-xs text-gray-500 mt-1"><?= truncateText($report['description'], 60) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($report['user_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            <?= $report['report_type'] === 'stolen' ? 'bg-red-100 text-red-800' : 
                                               ($report['report_type'] === 'lost' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($report['report_type'] === 'found' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) ?>">
                                            <?= ucfirst(htmlspecialchars($report['report_type'] ?? 'General')) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?= getStatusBadge($report['moderation_status'] ?? 'pending') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M d, Y', strtotime($report['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="view_report.php?id=<?= $report['id'] ?>&moderate=1" class="text-purple-600 hover:text-purple-900">
                                            <i class="fas fa-eye mr-1"></i> Review
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-2 block"></i>
                                        <p>No reports found.</p>
                                        <p class="text-sm mt-1">Try changing your filter criteria.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>

    <script>
    // Select all functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const reportCheckboxes = document.querySelectorAll('.report-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function(e) {
            reportCheckboxes.forEach(cb => {
                cb.checked = e.target.checked;
            });
        });
    }
    
    // Optional: Update select all checkbox when individual checkboxes change
    reportCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (selectAllCheckbox) {
                const allChecked = Array.from(reportCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(reportCheckboxes).some(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        });
    });
    
    // Validate bulk action form before submit
    document.getElementById('bulkForm')?.addEventListener('submit', function(e) {
        const actionSelect = document.querySelector('select[name="action"]');
        const selectedReports = document.querySelectorAll('.report-checkbox:checked');
        
        if (!actionSelect || !actionSelect.value) {
            e.preventDefault();
            alert('Please select a bulk action.');
            return false;
        }
        
        if (selectedReports.length === 0) {
            e.preventDefault();
            alert('Please select at least one report.');
            return false;
        }
        
        const action = actionSelect.value;
        let confirmMessage = '';
        
        if (action === 'approve') {
            confirmMessage = `Are you sure you want to approve ${selectedReports.length} report(s)?`;
        } else if (action === 'reject') {
            confirmMessage = `Are you sure you want to reject ${selectedReports.length} report(s)?`;
        } else if (action === 'delete') {
            confirmMessage = `Are you sure you want to delete ${selectedReports.length} report(s)? This action cannot be undone.`;
        }
        
        if (confirmMessage && !confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    });
    </script>
</body>
</html>