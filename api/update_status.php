<?php
// API endpoint to update report status
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login to update status']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

if (!$report_id || empty($status)) {
    echo json_encode(['success' => false, 'error' => 'Report ID and status are required']);
    exit();
}

// Check if user has permission
$checkStmt = $conn->prepare("SELECT user_id FROM reports WHERE id = ?");
$checkStmt->bind_param("i", $report_id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    echo json_encode(['success' => false, 'error' => 'Report not found']);
    exit();
}

$allowed_statuses = ['pending', 'approved', 'rejected', 'resolved', 'closed'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit();
}

// Check permissions
$can_update = false;
if ($user_role === 'admin' || $user_role === 'moderator') {
    $can_update = true;
} elseif ($report['user_id'] == $user_id && in_array($status, ['closed'])) {
    $can_update = true;
}

if (!$can_update) {
    echo json_encode(['success' => false, 'error' => 'You don\'t have permission to update this report']);
    exit();
}

// Update status
$updateStmt = $conn->prepare("UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?");
$updateStmt->bind_param("si", $status, $report_id);

if ($updateStmt->execute()) {
    // Log status change
    $logStmt = $conn->prepare("INSERT INTO report_status_logs (report_id, user_id, old_status, new_status, notes) 
                               SELECT ?, ?, status, ?, ? FROM reports WHERE id = ?");
    $logStmt->bind_param("iissi", $report_id, $user_id, $status, $notes, $report_id);
    $logStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Report status updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update status']);
}
?>