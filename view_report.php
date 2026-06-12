<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as user_name, u.user_type as reporter_type, u.phone as user_phone, u.email as user_email
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.id = ?
");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    header('Location: my_reports.php');
    exit();
}

// Increment view count
$stmt = $pdo->prepare("UPDATE reports SET views_count = views_count + 1 WHERE id = ?");
$stmt->execute([$report_id]);

// Get report images
$stmt = $pdo->prepare("SELECT * FROM report_media WHERE report_id = ? ORDER BY sort_order ASC");
$stmt->execute([$report_id]);
$report_images = $stmt->fetchAll();

// Check if user already submitted a claim
$has_claimed = false;
$claim_status = '';
$claim_id = 0;
$stmt = $pdo->prepare("SELECT id, status FROM claims WHERE report_id = ? AND claimant_user_id = ?");
$stmt->execute([$report_id, $_SESSION['user_id']]);
$existing_claim = $stmt->fetch();
if ($existing_claim) {
    $has_claimed = true;
    $claim_status = $existing_claim['status'];
    $claim_id = $existing_claim['id'];
}

$is_owner = ($report['user_id'] == $_SESSION['user_id']);
$user_type = $_SESSION['user_type'] ?? '';
$is_shop = ($user_type === 'shop' || $user_type === 'admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Findex - View Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .gold-gradient { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
        .report-card { background: white; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; }
        .status-badge { padding: 4px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; }
        .status-active { background: #dbeafe; color: #2563eb; }
        .status-resolved { background: #d1fae5; color: #059669; }
        .status-closed { background: #fee2e2; color: #dc2626; }
        .type-lost { background: #fef3c7; color: #d97706; }
        .type-stolen { background: #fee2e2; color: #dc2626; }
        .type-found { background: #d1fae5; color: #059669; }
        
        .report-images {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .report-image {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .report-image:hover { transform: scale(1.05); }
        .report-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            cursor: pointer;
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .close-modal {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 py-8">
    
    <div class="report-card">
        <!-- Header -->
        <div class="orange-gradient px-6 py-5 text-white">
            <div class="flex flex-wrap justify-between items-start gap-3">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold"><?php echo htmlspecialchars($report['title']); ?></h1>
                    <div class="flex flex-wrap items-center gap-3 mt-2">
                        <span class="status-badge type-<?php echo $report['report_type']; ?>">
                            <?php echo ucfirst($report['report_type']); ?>
                        </span>
                        <span class="status-badge status-<?php echo $report['status']; ?>">
                            <?php echo ucfirst($report['status']); ?>
                        </span>
                        <span class="text-orange-100 text-sm">
                            <i class="far fa-user mr-1"></i> Posted by <?php echo htmlspecialchars($report['user_name']); ?>
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-orange-100">Report #<?php echo $report['id']; ?></div>
                    <div class="text-xs text-orange-200 mt-1">
                        <?php 
                        if (function_exists('timeAgo')) {
                            echo timeAgo($report['created_at']); 
                        } else {
                            echo date('M d, Y', strtotime($report['created_at']));
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <!-- Images Section -->
            <?php if (!empty($report_images)): ?>
            <div class="mb-6">
                <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-images text-orange-500 mr-2"></i> Images
                </h3>
                <div class="report-images">
                    <?php foreach ($report_images as $img): ?>
                        <div class="report-image" onclick="openImageModal('<?php echo htmlspecialchars($img['file_path']); ?>')">
                            <img src="<?php echo htmlspecialchars($img['file_path']); ?>" alt="Report image">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Description -->
            <div class="mb-6">
                <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-file-alt text-orange-500 mr-2"></i> Description
                </h3>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-gray-700 leading-relaxed whitespace-pre-line"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                </div>
            </div>
            
            <!-- Details Grid -->
            <div class="grid md:grid-cols-2 gap-5 mb-6">
                <div class="bg-gray-50 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">Location</h3>
                    <p class="text-gray-800"><?php echo htmlspecialchars($report['location']); ?></p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">Incident Date</h3>
                    <p class="text-gray-800"><?php echo date('F d, Y', strtotime($report['incident_date'])); ?></p>
                </div>
                <?php if ($report['estimated_value'] && $report['estimated_value'] > 0): ?>
                <div class="bg-gray-50 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">Estimated Value</h3>
                    <p class="text-gray-800">$<?php echo number_format($report['estimated_value'], 2); ?></p>
                </div>
                <?php endif; ?>
                <div class="bg-gray-50 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">Views</h3>
                    <p class="text-gray-800"><?php echo number_format($report['views_count']); ?> people have viewed this report</p>
                </div>
                <?php if ($report['police_report_number']): ?>
                <div class="bg-gray-50 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">Police Report</h3>
                    <p class="text-gray-800"><?php echo htmlspecialchars($report['police_report_number']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Location Map -->
            <?php if ($report['latitude'] && $report['longitude']): ?>
            <div class="mb-6">
                <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-map-marker-alt text-orange-500 mr-2"></i> Location Map
                </h3>
                <div id="reportMap" style="height: 250px; border-radius: 16px;"></div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 pt-4 border-t">
                <!-- Back Button -->
                <a href="javascript:history.back()" class="bg-gray-500 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-gray-600 transition shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
                
                <!-- Edit Button (only for report owner) -->
                <?php if ($is_owner && $report['status'] === 'active'): ?>
                    <a href="edit_report.php?id=<?php echo $report['id']; ?>" class="bg-blue-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                        <i class="fas fa-edit mr-2"></i> Edit Report
                    </a>
                <?php endif; ?>
                
                <!-- Delete Button (only for report owner) -->
                <?php if ($is_owner && $report['status'] === 'active'): ?>
                    <a href="delete_report.php?id=<?php echo $report['id']; ?>" class="bg-red-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-red-700 transition shadow-sm" 
                       onclick="return confirm('Are you sure you want to delete this report? This cannot be undone.')">
                        <i class="fas fa-trash-alt mr-2"></i> Delete
                    </a>
                <?php endif; ?>
                
                <!-- CLAIM BUTTON - ONLY FOR SHOP USERS -->
                <?php if (!$is_owner && $report['status'] === 'active'): ?>
                    <?php if ($is_shop): ?>
                        <?php if ($has_claimed): ?>
                            <a href="view_claim.php?id=<?php echo $claim_id; ?>" class="bg-yellow-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-yellow-700 transition shadow-sm">
                                <i class="fas fa-clock mr-2"></i> View Your Claim (<?php echo ucfirst($claim_status); ?>)
                            </a>
                        <?php else: ?>
                            <a href="submit_claim.php?report_id=<?php echo $report['id']; ?>" class="bg-green-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-green-700 transition shadow-sm">
                                <i class="fas fa-store mr-2"></i> Claim This Item (Shop Only)
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Regular users see this message - removed the non-functional link -->
                        <div class="bg-gray-100 rounded-xl p-4 text-center w-full">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-store text-gray-400 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Shop Verification Required</p>
                                    <p class="text-xs text-gray-500 mt-1">Only verified jewelry shops can claim found or stolen items.</p>
                                </div>
                                <div class="mt-2 pt-2 border-t border-gray-200 w-full">
                                    <p class="text-xs text-gray-400">
                                        <i class="fas fa-info-circle mr-1"></i> 
                                        You are currently logged in as a regular user. To claim items, you need a shop account.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Share Button -->
                <button onclick="copyReportLink()" class="bg-gray-200 text-gray-700 px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-gray-300 transition shadow-sm">
                    <i class="fas fa-share-alt mr-2"></i> Share
                </button>
            </div>
        </div>
    </div>
    
    <!-- Related Information -->
    <div class="mt-6 bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-orange-500 text-xl mt-0.5"></i>
            <div class="text-sm text-gray-600">
                <p class="font-semibold text-gray-800">About Claims</p>
                <p class="mt-1">If you recognize this item and believe it belongs to you or your customer, click "Claim This Item" to submit a claim. You will need to provide proof of ownership.</p>
                <p class="mt-2 text-xs text-gray-500">The report owner will review your claim and contact you if approved.</p>
            </div>
        </div>
    </div>
    
    <!-- AI Analysis -->
    <div class="mt-6 gold-gradient rounded-xl p-5 text-white">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div class="flex items-center gap-3">
                <i class="fas fa-robot text-2xl"></i>
                <div>
                    <h3 class="font-bold">AI Analysis Available</h3>
                    <p class="text-white/80 text-sm">Get AI-powered insights about this item</p>
                </div>
            </div>
            <a href="ai_analyze.php?report_id=<?php echo $report_id; ?>" 
               class="bg-white text-orange-600 px-5 py-2 rounded-full text-sm font-semibold hover:shadow-lg transition inline-block">
                <i class="fas fa-magic mr-1"></i> Analyze Now
            </a>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="modal" onclick="closeImageModal()">
    <span class="close-modal">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
function copyReportLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        alert('Report link copied to clipboard!');
    }).catch(() => {
        alert('Press Ctrl+C to copy the link');
    });
}

function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modal.style.display = "block";
    modalImg.src = src;
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = "none";
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>

<?php if ($report['latitude'] && $report['longitude']): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var map = L.map('reportMap').setView([<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>], 14);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors',
        subdomains: 'abcd'
    }).addTo(map);
    
    var iconColor = '<?php echo $report['report_type'] === 'stolen' ? '#dc2626' : ($report['report_type'] === 'lost' ? '#d97706' : '#059669'); ?>';
    var marker = L.marker([<?php echo $report['latitude']; ?>, <?php echo $report['longitude']; ?>], {
        icon: L.divIcon({
            className: 'custom-marker',
            html: `<div style="background-color: ${iconColor}; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                      <i class="fas fa-map-marker-alt" style="color: white; font-size: 14px;"></i>
                  </div>`,
            iconSize: [30, 30]
        })
    }).addTo(map);
    
    var title = <?php echo json_encode(htmlspecialchars($report['title'])); ?>;
    var location = <?php echo json_encode(htmlspecialchars($report['location'])); ?>;
    marker.bindPopup(`<strong>${title}</strong><br>${location}`).openPopup();
</script>
<?php endif; ?>
</body>
</html>