<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $report_type = $_POST['report_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $incident_date = $_POST['incident_date'] ?? '';
    
    $errors = [];
    if (empty($report_type)) $errors[] = 'Report type is required.';
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if (empty($location)) $errors[] = 'Location is required.';
    if (empty($incident_date)) $errors[] = 'Incident date is required.';
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO reports (user_id, report_type, title, description, location, incident_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$user_id, $report_type, $title, $description, $location, $incident_date])) {
            
            $report_id = $pdo->lastInsertId();
            
            // IMAGE UPLOAD HANDLING
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
                        $file_name = $_FILES['report_images']['name'][$key];
                        $file_type = $_FILES['report_images']['type'][$key];
                        $file_size = $_FILES['report_images']['size'][$key];
                        
                        if (!in_array($file_type, $allowed_types)) continue;
                        if ($file_size > $max_size) continue;
                        
                        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
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
            
            // NOTIFICATIONS - Notify ALL shop users
            if (function_exists('notifyShopsAboutNewReport')) {
                $notified_count = notifyShopsAboutNewReport($report_id, $title, $report_type, $location);
                // This will appear in your PHP error log
                error_log("New report #{$report_id} - Notified {$notified_count} shops");
            }
            
            // Notify the user who posted
            if (function_exists('notifyUser')) {
                notifyUser(
                    $user_id,
                    'report_created',
                    'Report Submitted Successfully',
                    "Your report '{$title}' has been submitted with " . count($uploaded_images) . " image(s). You will be notified of any matches.",
                    "view_report.php?id={$report_id}"
                );
            }
            
            $_SESSION['success'] = 'Report submitted successfully!';
            header('Location: dashboard_' . getUserType() . '.php');
            exit();
        } else {
            $error = 'Failed to submit report. Please try again.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Findex - Submit Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .gradient-orange { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .card { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 12px; border: 1.5px solid #e5e7eb; border-radius: 10px; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #f97316; }
        .btn-primary { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; padding: 12px 24px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; width: 100%; }
        
        .upload-area {
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #fafafa;
        }
        .upload-area:hover { border-color: #f97316; background: #fff7ed; }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .image-preview {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .remove-image {
            position: absolute;
            top: 2px;
            right: 2px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="bg-white shadow-lg border-b-4 border-orange-500">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-gem text-orange-600 text-2xl mr-2"></i>
                    <span class="font-bold text-xl">Findex</span>
                </div>
                <a href="dashboard_<?= getUserType() ?>.php" class="text-gray-600 hover:text-orange-600">← Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="card p-8">
            <h1 class="text-2xl font-bold mb-6">Submit New Report</h1>
            
            <?php if (isset($error) && !empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Report Type *</label>
                    <select name="report_type" required class="form-select">
                        <option value="">Select type</option>
                        <option value="lost">Lost Jewelry</option>
                        <option value="stolen">Stolen Jewelry</option>
                        <option value="found">Found Jewelry</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Title *</label>
                    <input type="text" name="title" required class="form-input" placeholder="e.g., Gold Wedding Ring with Diamond">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description *</label>
                    <textarea name="description" rows="5" required class="form-textarea" placeholder="Describe the item in detail..."></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Location *</label>
                    <input type="text" name="location" required class="form-input" placeholder="City, country or specific location">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Incident Date *</label>
                    <input type="date" name="incident_date" required class="form-input">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Images (Optional)</label>
                    <div class="upload-area" onclick="document.getElementById('report_images').click()">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600">Click or drag to upload images</p>
                        <p class="text-xs text-gray-400 mt-1">Supported: JPG, PNG, GIF (Max 5MB per image)</p>
                        <input type="file" id="report_images" name="report_images[]" accept="image/*" multiple class="hidden">
                    </div>
                    <div id="imagePreviewContainer" class="image-preview-container"></div>
                </div>
                
                <button type="submit" class="btn-primary">Submit Report</button>
            </form>
        </div>
    </div>

    <script>
    const fileInput = document.getElementById('report_images');
    const previewContainer = document.getElementById('imagePreviewContainer');
    let selectedFiles = [];

    fileInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        selectedFiles = files;
        previewContainer.innerHTML = '';
        
        files.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                const previewDiv = document.createElement('div');
                previewDiv.className = 'image-preview';
                previewDiv.setAttribute('data-index', index);
                
                reader.onload = function(e) {
                    previewDiv.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <div class="remove-image" onclick="removeImage(${index})">×</div>
                    `;
                };
                reader.readAsDataURL(file);
                previewContainer.appendChild(previewDiv);
            }
        });
    });

    function removeImage(index) {
        const newFiles = selectedFiles.filter((_, i) => i !== index);
        selectedFiles = newFiles;
        
        const dataTransfer = new DataTransfer();
        newFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        
        previewContainer.innerHTML = '';
        newFiles.forEach((file, newIndex) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                const previewDiv = document.createElement('div');
                previewDiv.className = 'image-preview';
                previewDiv.setAttribute('data-index', newIndex);
                
                reader.onload = function(e) {
                    previewDiv.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <div class="remove-image" onclick="removeImage(${newIndex})">×</div>
                    `;
                };
                reader.readAsDataURL(file);
                previewContainer.appendChild(previewDiv);
            }
        });
    }
    </script>
</body>
</html>