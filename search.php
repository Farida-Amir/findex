<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// ============================================
// ALLOW ALL LOGGED IN USERS TO SEARCH
// ============================================
requireLogin();

// Helper functions
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M d', $timestamp);
    }
}

if (!function_exists('truncateText')) {
    function truncateText($text, $length = 100) {
        if (strlen($text) <= $length) return $text;
        return substr($text, 0, $length) . '...';
    }
}

// Get filter parameters
$search_query = isset($_GET['q']) ? $_GET['q'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$location_category = isset($_GET['location_category']) ? $_GET['location_category'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$results = array();
$search_performed = !empty($search_query) || !empty($category) || !empty($location) || !empty($location_category);

// Egypt Governorates
$egypt_governorates = array(
    'Cairo' => array('Cairo', 'New Cairo', 'Nasr City', 'Maadi', 'Heliopolis', 'Zamalek', 'Downtown', 'Garden City', 'Mohandessin', 'Dokki'),
    'Alexandria' => array('Alexandria', 'Montazah', 'Sidi Gaber', 'Smouha', 'Stanley', 'Agami', 'San Stefano'),
    'Giza' => array('Giza', '6th October', 'Sheikh Zayed', 'Haram', 'Mohandessin', 'Dokki', 'Faisal'),
    'Red Sea' => array('Hurghada', 'Sharm El Sheikh', 'El Gouna', 'Marsa Alam', 'Dahab'),
    'North Coast' => array('North Coast', 'Sahel', 'Marassi', 'Al Alamein', 'Porto Marina'),
    'Upper Egypt' => array('Luxor', 'Aswan', 'Qena', 'Sohag', 'Minya', 'Fayoum'),
    'Delta' => array('Tanta', 'Mansoura', 'Zagazig', 'Damietta', 'Port Said', 'Ismailia', 'Suez'),
    'Other' => array('Other')
);

$gov_icons = array(
    'Cairo' => 'fa-landmark',
    'Alexandria' => 'fa-anchor', 
    'Giza' => 'fa-pyramid',
    'Red Sea' => 'fa-fish',
    'North Coast' => 'fa-umbrella-beach',
    'Upper Egypt' => 'fa-temple',
    'Delta' => 'fa-leaf',
    'Other' => 'fa-globe'
);

if ($search_performed) {
    $sql = "SELECT r.*, u.full_name as user_name FROM reports r JOIN users u ON r.user_id = u.id WHERE r.status = 'active'";
    $params = array();
    
    if (!empty($search_query)) {
        $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    if (!empty($category) && $category !== 'all') {
        $sql .= " AND r.report_type = ?";
        $params[] = $category;
    }
    if (!empty($location_category) && isset($egypt_governorates[$location_category])) {
        $sql .= " AND (";
        $first = true;
        foreach ($egypt_governorates[$location_category] as $city) {
            if (!$first) $sql .= " OR ";
            $sql .= "r.location LIKE ?";
            $params[] = "%" . $city . "%";
            $first = false;
        }
        $sql .= ")";
    }
    if (!empty($location)) {
        $sql .= " AND r.location LIKE ?";
        $params[] = "%$location%";
    }
    if (!empty($date_filter)) {
        $sql .= " AND r.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = (int)$date_filter;
    }
    if (!empty($status_filter) && $status_filter !== 'all') {
        $sql .= " AND r.status = ?";
        $params[] = $status_filter;
    }
    $sql .= " ORDER BY r.created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

// Get counts (only active reports)
$total = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'active'")->fetchColumn();
$lost = $pdo->query("SELECT COUNT(*) FROM reports WHERE report_type='lost' AND status = 'active'")->fetchColumn();
$stolen = $pdo->query("SELECT COUNT(*) FROM reports WHERE report_type='stolen' AND status = 'active'")->fetchColumn();
$found = $pdo->query("SELECT COUNT(*) FROM reports WHERE report_type='found' AND status = 'active'")->fetchColumn();

$gov_counts = array();
foreach ($egypt_governorates as $gov => $cities) {
    $count = 0;
    foreach ($cities as $city) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE location LIKE ? AND status = 'active'");
        $stmt->execute(array("%" . $city . "%"));
        $count += $stmt->fetchColumn();
    }
    $gov_counts[$gov] = $count;
}

$popular = $pdo->query("SELECT location, COUNT(*) as cnt FROM reports WHERE location IS NOT NULL AND location != '' AND status = 'active' GROUP BY location ORDER BY cnt DESC LIMIT 8")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Findex - Search Jewelry | Egypt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .artistic-font { font-family: 'Playfair Display', serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .gold-gradient { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
        
        .hero-pattern {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(249, 115, 22, 0.1);
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 30px -15px rgba(0, 0, 0, 0.1);
        }
        
        .gov-card {
            background: white;
            border-radius: 16px;
            padding: 16px 12px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #eef2ff;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        .gov-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -12px rgba(0,0,0,0.15);
            border-color: #f97316;
        }
        .gov-card.active {
            border: 2px solid #f97316;
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
        }
        
        .result-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
            border: 1px solid #eef2ff;
        }
        .result-card:hover {
            transform: translateX(5px);
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.12);
            border-color: #f97316;
        }
        
        .custom-select {
            border: 1px solid #eef2ff;
            border-radius: 12px;
            padding: 10px 14px;
            width: 100%;
            background: white;
            font-size: 13px;
        }
        .custom-select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        }
        
        .filter-chip {
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            background: white;
        }
        .filter-chip.active {
            background: #f97316;
            color: white;
            border-color: #f97316;
            box-shadow: 0 2px 8px rgba(249,115,22,0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249,115,22,0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #f97316;
            color: #f97316;
            padding: 8px 22px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-outline:hover {
            background: #f97316;
            color: white;
        }
        
        .badge-lost { background: #fef3c7; color: #d97706; padding: 4px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; }
        .badge-stolen { background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; }
        .badge-found { background: #d1fae5; color: #059669; padding: 4px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; }
        .badge-active { background: #dbeafe; color: #2563eb; padding: 4px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; }
        .badge-resolved { background: #e0e7ff; color: #4f46e5; padding: 4px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; }
        
        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #eef2ff;
            border-radius: 50px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .search-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        }
        
        .glass-card {
            background: white;
            border-radius: 20px;
            border: 1px solid rgba(249, 115, 22, 0.1);
        }
        
        /* Smooth scroll offset for fixed header */
        html {
            scroll-padding-top: 80px;
            scroll-behavior: smooth;
        }
        
        /* Results section anchor */
        #results-section {
            scroll-margin-top: 80px;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-pattern text-white py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full mb-6">
            <i class="fas fa-map-marked-alt text-orange-400"></i>
            <span class="text-xs tracking-wider">Find Your Precious Items</span>
        </div>
        <h1 class="artistic-font text-3xl md:text-5xl font-bold mb-4">Search Lost & Stolen</h1>
        <h2 class="text-2xl md:text-4xl font-bold gold-gradient bg-clip-text text-transparent mb-6">Jewelry in Egypt</h2>
        <p class="text-sm text-gray-300 max-w-md mx-auto">Recover what's yours with Egypt's most trusted platform</p>
    </div>
</section>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 py-12">

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
        <div class="stat-card text-center">
            <div class="text-2xl font-bold text-orange-600"><?php echo number_format($total); ?></div>
            <div class="text-xs text-gray-500 mt-1">Total Reports</div>
        </div>
        <div class="stat-card text-center">
            <div class="text-2xl font-bold text-amber-600"><?php echo number_format($lost); ?></div>
            <div class="text-xs text-gray-500 mt-1">Lost Items</div>
        </div>
        <div class="stat-card text-center">
            <div class="text-2xl font-bold text-red-600"><?php echo number_format($stolen); ?></div>
            <div class="text-xs text-gray-500 mt-1">Stolen Items</div>
        </div>
        <div class="stat-card text-center">
            <div class="text-2xl font-bold text-emerald-600"><?php echo number_format($found); ?></div>
            <div class="text-xs text-gray-500 mt-1">Found Items</div>
        </div>
    </div>

    <!-- Governorates Grid -->
    <div class="glass-card p-6 mb-8">
        <h2 class="text-base font-bold mb-4 flex items-center"><i class="fas fa-map-marked-alt text-orange-500 mr-2"></i> Browse by Governorate</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-3">
            <?php foreach ($egypt_governorates as $gov => $cities): ?>
                <a href="?location_category=<?php echo urlencode($gov); ?>" class="gov-card <?php echo $location_category === $gov ? 'active' : ''; ?>">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mx-auto mb-2 <?php echo $location_category === $gov ? 'orange-gradient' : 'bg-gray-100'; ?>">
                        <i class="fas <?php echo $gov_icons[$gov]; ?> text-base <?php echo $location_category === $gov ? 'text-white' : 'text-orange-500'; ?>"></i>
                    </div>
                    <div class="font-semibold text-xs text-gray-800"><?php echo $gov; ?></div>
                    <div class="text-xs text-gray-400 mt-1"><?php echo $gov_counts[$gov]; ?> reports</div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($location_category)): ?>
            <div class="text-center mt-4"><a href="search.php" class="text-xs text-orange-600">Clear region filter</a></div>
        <?php endif; ?>
    </div>

    <!-- Search Form -->
    <div class="glass-card p-6 mb-8" id="search-form">
        <form method="GET" action="search.php" id="searchForm">
            <div class="mb-4">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by title, description, or keywords..." class="search-input">
            </div>
            
            <div class="grid md:grid-cols-3 gap-4 mb-4">
                <div>
                    <select name="category" class="custom-select">
                        <option value="">All Categories</option>
                        <option value="lost" <?php echo $category === 'lost' ? 'selected' : ''; ?>>Lost Jewelry</option>
                        <option value="stolen" <?php echo $category === 'stolen' ? 'selected' : ''; ?>>Stolen Jewelry</option>
                        <option value="found" <?php echo $category === 'found' ? 'selected' : ''; ?>>Found Jewelry</option>
                    </select>
                </div>
                <div>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="Specific location (e.g., Maadi, Nasr City)" class="custom-select">
                </div>
                <div>
                    <select name="date" class="custom-select">
                        <option value="">Any time</option>
                        <option value="7" <?php echo $date_filter == '7' ? 'selected' : ''; ?>>Last 7 days</option>
                        <option value="30" <?php echo $date_filter == '30' ? 'selected' : ''; ?>>Last 30 days</option>
                        <option value="90" <?php echo $date_filter == '90' ? 'selected' : ''; ?>>Last 90 days</option>
                    </select>
                </div>
            </div>
            
            <div class="flex flex-wrap justify-between items-center gap-3">
                <div class="flex gap-2">
                    <button type="button" onclick="setStatusAndSubmit('')" class="filter-chip <?php echo empty($status_filter) ? 'active' : ''; ?>">All</button>
                    <button type="button" onclick="setStatusAndSubmit('active')" class="filter-chip <?php echo $status_filter === 'active' ? 'active' : ''; ?>">Active</button>
                    <button type="button" onclick="setStatusAndSubmit('resolved')" class="filter-chip <?php echo $status_filter === 'resolved' ? 'active' : ''; ?>">Resolved</button>
                </div>
                <input type="hidden" name="status" id="statusInput" value="<?php echo htmlspecialchars($status_filter); ?>">
                <input type="hidden" name="location_category" id="locationCategoryInput" value="<?php echo htmlspecialchars($location_category); ?>">
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary"><i class="fas fa-search mr-2"></i>Search</button>
                    <a href="search.php" class="btn-outline"><i class="fas fa-redo-alt mr-2"></i>Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Section with Anchor -->
    <?php if ($search_performed): ?>
        <div id="results-section">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-base font-bold">Found <?php echo count($results); ?> results</h2>
                <a href="search.php" class="text-xs text-orange-600">Clear all</a>
            </div>
            <?php if (empty($results)): ?>
                <div class="glass-card p-12 text-center">
                    <i class="fas fa-search text-4xl text-gray-300 mb-3"></i>
                    <p class="text-sm text-gray-500">No results found. Try different search criteria.</p>
                    <a href="search.php" class="inline-block mt-4 text-orange-600 text-sm">Browse all reports →</a>
                </div>
            <?php else: ?>
                <?php foreach ($results as $report): ?>
                    <div class="result-card">
                        <div class="flex flex-wrap justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <span class="<?php echo $report['report_type'] === 'stolen' ? 'badge-stolen' : ($report['report_type'] === 'lost' ? 'badge-lost' : 'badge-found'); ?>">
                                        <?php echo ucfirst($report['report_type']); ?>
                                    </span>
                                    <span class="<?php echo $report['status'] === 'active' ? 'badge-active' : 'badge-resolved'; ?>">
                                        <?php echo ucfirst($report['status']); ?>
                                    </span>
                                </div>
                                <a href="view_report.php?id=<?php echo $report['id']; ?>">
                                    <h3 class="text-base font-bold text-gray-800 hover:text-orange-600 mb-1"><?php echo htmlspecialchars($report['title']); ?></h3>
                                </a>
                                <p class="text-gray-500 text-sm mb-2"><?php echo truncateText($report['description'], 100); ?></p>
                                <div class="flex flex-wrap gap-3 text-xs text-gray-400">
                                    <span><i class="fas fa-map-marker-alt text-orange-500 mr-1"></i> <?php echo htmlspecialchars($report['location'] ?: 'Not specified'); ?></span>
                                    <span><i class="far fa-calendar-alt text-orange-500 mr-1"></i> <?php echo date('d M Y', strtotime($report['incident_date'])); ?></span>
                                    <span><i class="far fa-eye text-orange-500 mr-1"></i> <?php echo number_format($report['views_count']); ?> views</span>
                                </div>
                            </div>
                            <div>
                                <a href="view_report.php?id=<?php echo $report['id']; ?>" class="orange-gradient text-white px-4 py-2 rounded-full text-xs font-semibold">View →</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Popular Locations & Categories -->
        <div id="results-section">
            <div class="grid md:grid-cols-2 gap-6">
                <div class="glass-card p-6">
                    <h3 class="text-sm font-bold mb-3"><i class="fas fa-fire text-orange-500 mr-2"></i> Popular Locations</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($popular as $loc): ?>
                            <a href="#" onclick="setLocationAndSubmit('<?php echo htmlspecialchars($loc['location']); ?>'); return false;" class="px-3 py-1 bg-gray-100 rounded-full text-xs hover:bg-orange-100 hover:text-orange-600 transition">
                                📍 <?php echo htmlspecialchars($loc['location']); ?> (<?php echo $loc['cnt']; ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="glass-card p-6">
                    <h3 class="text-sm font-bold mb-3"><i class="fas fa-chart-pie text-orange-500 mr-2"></i> Browse by Category</h3>
                    <div class="space-y-2">
                        <a href="#" onclick="setCategoryAndSubmit('lost'); return false;" class="flex justify-between items-center p-3 bg-amber-50 rounded-xl hover:bg-amber-100 transition text-sm cursor-pointer">
                            <span><i class="fas fa-frown text-amber-600 mr-2"></i> Lost Jewelry</span>
                            <span class="text-amber-600 font-semibold text-xs"><?php echo number_format($lost); ?> reports →</span>
                        </a>
                        <a href="#" onclick="setCategoryAndSubmit('stolen'); return false;" class="flex justify-between items-center p-3 bg-red-50 rounded-xl hover:bg-red-100 transition text-sm cursor-pointer">
                            <span><i class="fas fa-skull text-red-600 mr-2"></i> Stolen Jewelry</span>
                            <span class="text-red-600 font-semibold text-xs"><?php echo number_format($stolen); ?> reports →</span>
                        </a>
                        <a href="#" onclick="setCategoryAndSubmit('found'); return false;" class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl hover:bg-emerald-100 transition text-sm cursor-pointer">
                            <span><i class="fas fa-smile text-emerald-600 mr-2"></i> Found Jewelry</span>
                            <span class="text-emerald-600 font-semibold text-xs"><?php echo number_format($found); ?> reports →</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Login Reminder Card -->
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 mt-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-lock text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-blue-800 text-sm">Secure Search Access</h4>
                        <p class="text-blue-700 text-xs mt-1">You are logged in and can search all reports. Your identity is protected when contacting report owners.</p>
                        <p class="text-blue-600 text-xs mt-2"><i class="fas fa-check-circle mr-1"></i> View detailed report information</p>
                        <p class="text-blue-600 text-xs"><i class="fas fa-check-circle mr-1"></i> Contact report owners securely</p>
                    </div>
                </div>
            </div>
            
            <!-- Tips Card -->
            <div class="gold-gradient rounded-2xl p-6 mt-6 text-white">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-4">
                        <i class="fas fa-lightbulb text-2xl"></i>
                        <div>
                            <h3 class="text-sm font-bold">Search Tips</h3>
                            <p class="text-white/80 text-xs">Use specific keywords like "gold ring" or "diamond necklace" for better results</p>
                        </div>
                    </div>
                    <div class="flex gap-4 text-xs">
                        <div class="text-center"><i class="fas fa-map-marker-alt text-lg mb-1 block"></i><span>Filter by region</span></div>
                        <div class="text-center"><i class="fas fa-calendar text-lg mb-1 block"></i><span>Date range</span></div>
                        <div class="text-center"><i class="fas fa-tag text-lg mb-1 block"></i><span>Item category</span></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Function to set status and submit the form
function setStatusAndSubmit(status) {
    document.getElementById('statusInput').value = status;
    document.getElementById('searchForm').submit();
}

// Function to set category and submit, then scroll to results
function setCategoryAndSubmit(category) {
    // Clear other params but keep category
    const form = document.getElementById('searchForm');
    const qInput = document.querySelector('input[name="q"]');
    const categorySelect = document.querySelector('select[name="category"]');
    const locationInput = document.querySelector('input[name="location"]');
    const dateSelect = document.querySelector('select[name="date"]');
    
    // Clear other fields
    if (qInput) qInput.value = '';
    if (locationInput) locationInput.value = '';
    if (dateSelect) dateSelect.value = '';
    
    // Set category
    if (categorySelect) categorySelect.value = category;
    
    // Clear status and location_category hidden fields
    document.getElementById('statusInput').value = '';
    document.getElementById('locationCategoryInput').value = '';
    
    // Submit the form
    form.submit();
}

// Function to set location and submit
function setLocationAndSubmit(location) {
    const form = document.getElementById('searchForm');
    const locationInput = document.querySelector('input[name="location"]');
    const categorySelect = document.querySelector('select[name="category"]');
    const qInput = document.querySelector('input[name="q"]');
    const dateSelect = document.querySelector('select[name="date"]');
    
    // Clear other fields
    if (qInput) qInput.value = '';
    if (categorySelect) categorySelect.value = '';
    if (dateSelect) dateSelect.value = '';
    
    // Set location
    if (locationInput) locationInput.value = location;
    
    // Clear status and location_category hidden fields
    document.getElementById('statusInput').value = '';
    document.getElementById('locationCategoryInput').value = '';
    
    // Submit the form
    form.submit();
}

// Scroll to results section after page loads if there are results
<?php if ($search_performed && !empty($results)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const resultsSection = document.getElementById('results-section');
    if (resultsSection) {
        setTimeout(function() {
            resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
});
<?php endif; ?>
</script>

<!-- AI Assistant Widget -->
<?php include 'includes/ai_assistant.php'; ?>
<?php include 'includes/footer.php'; ?>
</body>
</html>