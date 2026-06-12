<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get report
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    $_SESSION['error'] = 'Report not found.';
    header('Location: my_reports.php');
    exit();
}

// Check ownership
if ($report['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = 'You can only edit your own reports.';
    header('Location: my_reports.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $incident_date = $_POST['incident_date'] ?? '';
    
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if (empty($location)) $errors[] = 'Location is required.';
    if (empty($incident_date)) $errors[] = 'Incident date is required.';
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE reports 
            SET title = ?, description = ?, location = ?, incident_date = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        if ($stmt->execute([$title, $description, $location, $incident_date, $report_id, $_SESSION['user_id']])) {
            $_SESSION['success'] = 'Report updated successfully!';
            header('Location: view_report.php?id=' . $report_id);
            exit();
        } else {
            $error = 'Failed to update report.';
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
    <title>Edit Report - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="orange-gradient px-6 py-4 text-white">
            <h1 class="text-2xl font-bold">Edit Report</h1>
            <p class="text-orange-100 text-sm">Update your report information</p>
        </div>
        
        <form method="POST" class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" required value="<?php echo htmlspecialchars($report['title']); ?>" 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description *</label>
                <textarea name="description" rows="5" required 
                          class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"><?php echo htmlspecialchars($report['description']); ?></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Location *</label>
                <input type="text" name="location" required value="<?php echo htmlspecialchars($report['location']); ?>" 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Incident Date *</label>
                <input type="date" name="incident_date" required value="<?php echo $report['incident_date']; ?>" 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500">
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="orange-gradient text-white px-6 py-2 rounded-full font-semibold">
                    <i class="fas fa-save mr-2"></i> Save Changes
                </button>
                <a href="view_report.php?id=<?php echo $report_id; ?>" class="bg-gray-500 text-white px-6 py-2 rounded-full font-semibold hover:bg-gray-600">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>