<?php
// API endpoint for file uploads
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login to upload files']);
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_type = isset($_POST['upload_type']) ? $_POST['upload_type'] : 'report';

// Define upload directories
$upload_dirs = [
    'report' => '../uploads/reports/',
    'proof' => '../uploads/proofs/',
    'payment' => '../uploads/payments/',
    'receipt' => '../uploads/receipts/',
    'verification' => '../uploads/verifications/',
    'ai' => '../uploads/ai_outputs/'
];

$target_dir = isset($upload_dirs[$upload_type]) ? $upload_dirs[$upload_type] : $upload_dirs['report'];

// Create directory if not exists
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit();
}

$file = $_FILES['file'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
$max_size = 10 * 1024 * 1024; // 10MB

// Validate file
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP, PDF']);
    exit();
}

if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'File too large. Max 10MB']);
    exit();
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = time() . '_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
$target_file = $target_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target_file)) {
    // Log upload
    $relative_path = str_replace('../', '', $target_file);
    
    $stmt = $conn->prepare("INSERT INTO uploads (user_id, filename, original_name, file_path, file_type, file_size, upload_type) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $original_name = $file['name'];
    $file_type = $file['type'];
    $file_size = $file['size'];
    $stmt->bind_param("issssis", $user_id, $filename, $original_name, $relative_path, $file_type, $file_size, $upload_type);
    $stmt->execute();
    
    $upload_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'data' => [
            'id' => $upload_id,
            'filename' => $filename,
            'original_name' => $original_name,
            'path' => $relative_path,
            'url' => '/' . $relative_path
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
}
?>