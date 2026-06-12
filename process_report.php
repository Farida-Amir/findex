<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: report_item.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$report_type = $_POST['report_type'] ?? '';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');
$incident_date = $_POST['incident_date'] ?? '';
$estimated_value = $_POST['estimated_value'] ?? null;
$police_report_number = $_POST['police_report_number'] ?? null;

$errors = [];

if (empty($report_type)) $errors[] = 'Report type is required.';
if (empty($title)) $errors[] = 'Title is required.';
if (empty($description)) $errors[] = 'Description is required.';
if (empty($location)) $errors[] = 'Location is required.';
if (empty($incident_date)) $errors[] = 'Incident date is required.';

if (empty($errors)) {
    try {
        $pdo->beginTransaction();
        
        // Insert report
        $stmt = $pdo->prepare("
            INSERT INTO reports (
                user_id, report_type, title, description, location, incident_date, 
                estimated_value, police_report_number, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            $user_id, $report_type, $title, $description, $location, $incident_date,
            $estimated_value, $police_report_number
        ]);
        
        $report_id = $pdo->lastInsertId();
        
        // Handle image uploads
        $uploaded_images = [];
        if (isset($_FILES['report_images']) && !empty($_FILES['report_images']['name'][0])) {
            $upload_dir = 'uploads/reports/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5242880; // 5MB
            
            foreach ($_FILES['report_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['report_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['report_images']['type'][$key];
                    $file_size = $_FILES['report_images']['size'][$key];
                    
                    if (!in_array($file_type, $allowed_types)) continue;
                    if ($file_size > $max_size) continue;
                    
                    $ext = pathinfo($_FILES['report_images']['name'][$key], PATHINFO_EXTENSION);
                    $new_filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $img_stmt = $pdo->prepare("
                            INSERT INTO report_media (report_id, media_type, file_path, sort_order, uploaded_at) 
                            VALUES (?, 'photo', ?, ?, NOW())
                        ");
                        $img_stmt->execute([$report_id, $upload_path, $key]);
                        $uploaded_images[] = $upload_path;
                    }
                }
            }
        }
        
        $pdo->commit();
        
        // Send notifications to shops
        if (function_exists('notifyShopsAboutNewReport')) {
            notifyShopsAboutNewReport($report_id, $title, $report_type, $location);
        }
        
        // Notify user
        if (function_exists('notifyUser')) {
            notifyUser(
                $user_id,
                'report_created',
                'Report Submitted',
                "Your report '{$title}' has been submitted with " . count($uploaded_images) . " image(s).",
                "view_report.php?id={$report_id}"
            );
        }
        
        $_SESSION['success'] = 'Report submitted successfully!';
        $redirect = 'dashboard_' . getUserType() . '.php';
        header("Location: $redirect");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Failed to submit report: ' . $e->getMessage();
        $_SESSION['error'] = $error;
        header('Location: report_item.php');
        exit();
    }
} else {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: report_item.php');
    exit();
}
?>