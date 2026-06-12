<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get report to check ownership
$stmt = $pdo->prepare("SELECT user_id FROM reports WHERE id = ?");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    $_SESSION['error'] = 'Report not found.';
    header('Location: my_reports.php');
    exit();
}

// Check if user owns this report
if ($report['user_id'] != $_SESSION['user_id'] && getUserType() !== 'admin') {
    $_SESSION['error'] = 'You do not have permission to delete this report.';
    header('Location: my_reports.php');
    exit();
}

// Delete the report
$stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
if ($stmt->execute([$report_id])) {
    $_SESSION['success'] = 'Report deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete report.';
}

header('Location: my_reports.php');
exit();
?>