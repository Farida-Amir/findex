<?php

function uploadFile($file, $destination, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 5242880) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $error_codes = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
        ];
        $error_code = $file['error'] ?? 'unknown';
        $error_msg = $error_codes[$error_code] ?? 'Unknown upload error';
        return ['success' => false, 'error' => $error_msg];
    }
    
    // Get file info
    $file_name = basename($file['name']);
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', $allowed_types)];
    }
    
    // Validate file size
    if ($file_size > $max_size) {
        $max_mb = $max_size / 1024 / 1024;
        return ['success' => false, 'error' => "File too large. Max size: {$max_mb}MB"];
    }
    
    // Create unique filename
    $new_filename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
    
    // Ensure destination directory exists
    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }
    
    // Full path
    $upload_path = rtrim($destination, '/') . '/' . $new_filename;
    
    // Move file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return ['success' => true, 'filename' => $new_filename, 'path' => $upload_path];
    }
    
    return ['success' => false, 'error' => 'Failed to save file. Check directory permissions.'];
}

/**
 * Validate National ID format
 */
function validateNationalId($id, $country = 'EG') {
    $id = preg_replace('/[^A-Z0-9]/i', '', $id);
    
    switch ($country) {
        case 'EG':
            // Egyptian National ID (14 digits)
            return preg_match('/^[0-9]{14}$/', $id);
        case 'US':
            return strlen($id) >= 6 && strlen($id) <= 20;
        case 'UK':
            return preg_match('/^[A-CEGHJ-PR-TW-Z]{1}[A-CEGHJ-NPR-TW-Z]{1}[0-9]{6}[A-D]{1}$/i', $id);
        default:
            return strlen($id) >= 5;
    }
}
?>