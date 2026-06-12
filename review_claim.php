<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$claim_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$stmt = $pdo->prepare("
    SELECT c.*, r.user_id as report_owner_id, r.title as report_title
    FROM claims c 
    JOIN reports r ON c.report_id = r.id 
    WHERE c.id = ?
");
$stmt->execute([$claim_id]);
$claim = $stmt->fetch();

if (!$claim) {
    header('Location: my_reports.php');
    exit();
}

$can_review = ($claim['report_owner_id'] == $_SESSION['user_id']) || (getUserType() === 'admin');

if (!$can_review) {
    header('Location: my_reports.php');
    exit();
}

if ($action === 'approve') {
    $stmt = $pdo->prepare("
        UPDATE claims 
        SET status = 'approved', resolved_by = ?, resolved_at = NOW(), moderator_notes = 'Claim approved by owner'
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $claim_id]);
    
    $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
    $stmt->execute([$claim['report_id']]);
    
    // NOTIFY THE CLAIMANT
    if (function_exists('notifyUser')) {
        notifyUser(
            $claim['claimant_user_id'],
            'claim_approved',
            'Claim Approved',
            "Great news! Your claim for '{$claim['report_title']}' has been approved. The owner will contact you soon.",
            "view_claim.php?id={$claim_id}"
        );
    }
    
    // NOTIFY THE SHOP OWNER (confirmation)
    if (function_exists('notifyUser')) {
        notifyUser(
            $_SESSION['user_id'],
            'claim_processed',
            'Claim Approved',
            "You have approved the claim for '{$claim['report_title']}'. Please contact the claimant to arrange the return.",
            "view_claim.php?id={$claim_id}"
        );
    }
    
    $_SESSION['success'] = 'Claim approved successfully. The item has been marked as resolved.';
    
} elseif ($action === 'reject') {
    $stmt = $pdo->prepare("
        UPDATE claims 
        SET status = 'rejected', resolved_by = ?, resolved_at = NOW(), moderator_notes = 'Claim rejected by owner'
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $claim_id]);
    
    // NOTIFY THE CLAIMANT
    if (function_exists('notifyUser')) {
        notifyUser(
            $claim['claimant_user_id'],
            'claim_rejected',
            'Claim Not Approved',
            "Your claim for '{$claim['report_title']}' was not approved. You can submit a new claim with more evidence.",
            "view_claim.php?id={$claim_id}"
        );
    }
    
    // NOTIFY THE SHOP OWNER (confirmation)
    if (function_exists('notifyUser')) {
        notifyUser(
            $_SESSION['user_id'],
            'claim_processed',
            'Claim Rejected',
            "You have rejected the claim for '{$claim['report_title']}'. The claimant has been notified.",
            "view_claim.php?id={$claim_id}"
        );
    }
    
    $_SESSION['success'] = 'Claim rejected.';
}

header('Location: view_claim.php?id=' . $claim_id);
exit();
?>