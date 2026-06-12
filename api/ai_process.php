<?php
/**
 * AI Process API - Complete Advanced Version
 * Handles: Text Analysis, Image Enhancement, Suspect Generation, Item Reconstruction
 * Includes: Error handling, logging, rate limiting, and response optimization
 */

// Error handling - log errors but don't display to client
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/ai_errors.log');

// Create logs directory if not exists
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// CORS headers for API requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database and auth includes
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Rate limiting - prevent abuse
session_start();
$rate_limit_key = 'ai_rate_limit_' . ($_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR']);
if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'reset' => time() + 60];
}

if ($_SESSION[$rate_limit_key]['count'] >= 10 && time() < $_SESSION[$rate_limit_key]['reset']) {
    echo json_encode([
        'success' => false, 
        'error' => 'Rate limit exceeded. Please wait before making more requests.',
        'retry_after' => $_SESSION[$rate_limit_key]['reset'] - time()
    ]);
    exit();
}

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false, 
        'error' => 'Please login to use AI features',
        'redirect' => 'login.php'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit();
}

// Increment rate limit counter
$_SESSION[$rate_limit_key]['count']++;

// Initialize response
$response = ['success' => false, 'message' => '', 'image' => ''];

// Create upload directories if not exists
$upload_dirs = [
    '../uploads/ai_outputs/',
    '../uploads/temp/'
];
foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// ============================================
// ACTION: ANALYZE DESCRIPTION
// ============================================
if ($action === 'analyze_description') {
    $description = trim($_POST['description'] ?? '');
    
    if (empty($description)) {
        echo json_encode(['success' => false, 'error' => 'Please provide a description to analyze']);
        exit();
    }
    
    // Enhanced keyword detection
    $keywords = [
        'metal' => ['gold', 'silver', 'platinum', 'palladium', 'rose gold', 'white gold', 'yellow gold', 'sterling silver'],
        'gemstone' => ['diamond', 'ruby', 'emerald', 'sapphire', 'amethyst', 'topaz', 'citrine', 'garnet', 'peridot', 'tourmaline', 'aquamarine', 'opal', 'pearl', 'jade', 'onyx'],
        'item_type' => ['ring', 'necklace', 'bracelet', 'earring', 'pendant', 'charm', 'brooch', 'cufflink', 'watch', 'bangle', 'anklet', 'tiara', 'crown'],
        'design' => ['vintage', 'antique', 'modern', 'classic', 'art deco', 'victorian', 'edwardian', 'contemporary', 'minimalist', 'ornate', 'engraved', 'etched'],
        'condition' => ['new', 'like new', 'excellent', 'good', 'fair', 'worn', 'damaged', 'scratched', 'missing stone']
    ];
    
    $detected = [
        'metal' => [],
        'gemstone' => [],
        'item_type' => [],
        'design' => [],
        'condition' => []
    ];
    
    $description_lower = strtolower($description);
    
    foreach ($keywords as $category => $words) {
        foreach ($words as $word) {
            if (stripos($description_lower, $word) !== false) {
                $detected[$category][] = $word;
            }
        }
    }
    
    // Remove duplicates
    foreach ($detected as $category => $items) {
        $detected[$category] = array_unique($items);
    }
    
    // Generate analysis
    $analysis = "🔍 **Jewelry Analysis Report**\n\n";
    $analysis .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $has_detected = false;
    
    if (!empty($detected['item_type'])) {
        $has_detected = true;
        $analysis .= "📿 **Item Type:** " . implode(", ", $detected['item_type']) . "\n\n";
    }
    
    if (!empty($detected['metal'])) {
        $has_detected = true;
        $analysis .= "⚜️ **Metal/Material:** " . implode(", ", $detected['metal']) . "\n\n";
    }
    
    if (!empty($detected['gemstone'])) {
        $has_detected = true;
        $analysis .= "💎 **Gemstones:** " . implode(", ", $detected['gemstone']) . "\n\n";
    }
    
    if (!empty($detected['design'])) {
        $has_detected = true;
        $analysis .= "🎨 **Design Style:** " . implode(", ", $detected['design']) . "\n\n";
    }
    
    if (!empty($detected['condition'])) {
        $has_detected = true;
        $analysis .= "📊 **Condition:** " . implode(", ", $detected['condition']) . "\n\n";
    }
    
    if (!$has_detected) {
        $analysis .= "⚠️ **Limited Information Detected**\n\n";
        $analysis .= "To improve analysis, please include:\n";
        $analysis .= "• Metal type (gold, silver, platinum)\n";
        $analysis .= "• Gemstone type (diamond, ruby, emerald)\n";
        $analysis .= "• Item type (ring, necklace, bracelet)\n";
        $analysis .= "• Any distinguishing features or markings\n\n";
    }
    
    // Add recommendations
    $analysis .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $analysis .= "💡 **Recommendations:**\n\n";
    
    if (!empty($detected['item_type']) || !empty($detected['gemstone'])) {
        $analysis .= "✅ **File a Report** - Create a detailed report with this information\n";
        $analysis .= "✅ **Upload Photos** - Clear images help with identification\n";
        $analysis .= "✅ **Check Local Shops** - Notify pawn shops and jewelers in your area\n";
        $analysis .= "✅ **File Police Report** - For stolen items, file an official police report\n";
    } else {
        $analysis .= "📝 **Provide More Details** - Add specific characteristics to help identify your item\n";
        $analysis .= "📸 **Upload Images** - Visual evidence greatly improves recovery chances\n";
        $analysis .= "🏪 **Contact Local Jewelers** - They may have seen your item\n";
    }
    
    $response = ['success' => true, 'message' => $analysis];
    
    // Log the activity
    logAIActivity($user_id, 'analyze_description', $description, $analysis);
}

// ============================================
// ACTION: GENERATE SUSPECT
// ============================================
elseif ($action === 'generate_suspect') {
    $description = trim($_POST['suspect_description'] ?? '');
    
    if (empty($description)) {
        echo json_encode(['success' => false, 'error' => 'Please describe the suspect']);
        exit();
    }
    
    // Enhance the prompt for better image generation
    $enhanced_prompt = "forensic police sketch, security camera style, realistic portrait of suspect, " . $description . ", front facing, neutral expression, detailed face, law enforcement composite sketch";
    
    // Use Pollinations.ai for image generation (free, no API key needed)
    $image_url = "https://image.pollinations.ai/prompt/" . urlencode($enhanced_prompt) . "?width=512&height=512&seed=" . rand(1000, 9999) . "&nologo=true";
    
    $response = [
        'success' => true, 
        'message' => "🕵️ **Suspect Composite Generated**\n\n" .
                     "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                     "Based on your description:\n" .
                     "> \"" . htmlspecialchars(substr($description, 0, 100)) . (strlen($description) > 100 ? "...\"" : "\"") . "\n\n" .
                     "⚠️ **Important Notice:**\n" .
                     "• This is an AI-generated representation for reference only\n" .
                     "• Do not use as official evidence\n" .
                     "• Share with law enforcement as a visual aid\n" .
                     "• Combine with actual witness descriptions\n\n" .
                     "📌 **Next Steps:**\n" .
                     "1. Save this image to your device\n" .
                     "2. Share with local police\n" .
                     "3. Post in your report for awareness",
        'image' => $image_url
    ];
    
    logAIActivity($user_id, 'generate_suspect', $description, $image_url);
}

// ============================================
// ACTION: RECONSTRUCT ITEM
// ============================================
elseif ($action === 'reconstruct_item') {
    $description = trim($_POST['item_description'] ?? '');
    
    if (empty($description)) {
        echo json_encode(['success' => false, 'error' => 'Please describe the jewelry item']);
        exit();
    }
    
    // Enhance prompt for better jewelry visualization
    $enhanced_prompt = "professional product photography, white background, high resolution, detailed jewelry item, " . $description . ", studio lighting, 8K, sharp focus, isolated on white";
    
    $image_url = "https://image.pollinations.ai/prompt/" . urlencode($enhanced_prompt) . "?width=512&height=512&seed=" . rand(1000, 9999) . "&nologo=true";
    
    $response = [
        'success' => true,
        'message' => "💎 **Item Reconstruction Complete**\n\n" .
                     "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                     "AI has generated an image based on:\n" .
                     "> \"" . htmlspecialchars(substr($description, 0, 100)) . (strlen($description) > 100 ? "...\"" : "\"") . "\n\n" .
                     "📌 **How to use this image:**\n" .
                     "• Attach to your theft/loss report\n" .
                     "• Share with pawn shops and jewelers\n" .
                     "• Post on social media for awareness\n" .
                     "• Provide to insurance claims\n\n" .
                     "⚠️ Note: This is an AI approximation. Actual item may vary.",
        'image' => $image_url
    ];
    
    logAIActivity($user_id, 'reconstruct_item', $description, $image_url);
}

// ============================================
// ACTION: ENHANCE IMAGE
// ============================================
elseif ($action === 'enhance_image' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Please upload JPG, PNG, GIF, or WEBP.']);
        exit();
    }
    
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 10MB.']);
        exit();
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload failed. Please try again.']);
        exit();
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = time() . '_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $upload_path = '../uploads/ai_outputs/' . $filename;
    $relative_path = 'uploads/ai_outputs/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Try to enhance image quality (basic enhancement)
        $enhanced = false;
        $enhanced_path = $upload_path;
        
        // If GD library is available, try basic enhancement
        if (extension_loaded('gd')) {
            $enhanced_filename = time() . '_enhanced_' . $user_id . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $enhanced_path = '../uploads/ai_outputs/' . $enhanced_filename;
            
            // Simple image enhancement based on type
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $src = imagecreatefromjpeg($upload_path);
                    if ($src) {
                        imagefilter($src, IMG_FILTER_CONTRAST, 10);
                        imagefilter($src, IMG_FILTER_BRIGHTNESS, 5);
                        imagejpeg($src, $enhanced_path, 90);
                        imagedestroy($src);
                        $enhanced = true;
                    }
                    break;
                case 'png':
                    $src = imagecreatefrompng($upload_path);
                    if ($src) {
                        imagefilter($src, IMG_FILTER_CONTRAST, 10);
                        imagefilter($src, IMG_FILTER_BRIGHTNESS, 5);
                        imagepng($src, $enhanced_path, 8);
                        imagedestroy($src);
                        $enhanced = true;
                    }
                    break;
            }
            
            if ($enhanced) {
                $relative_path = 'uploads/ai_outputs/' . $enhanced_filename;
                // Optionally delete original
                // unlink($upload_path);
            }
        }
        
        $response = [
            'success' => true,
            'message' => "🖼️ **Image Processed Successfully!**\n\n" .
                         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                         "✅ Original filename: " . htmlspecialchars($file['name']) . "\n" .
                         "✅ File size: " . round($file['size'] / 1024, 2) . " KB\n" .
                         ($enhanced ? "✅ Enhanced: Yes (contrast & brightness adjusted)\n\n" : "\n") .
                         "📌 **Next Steps:**\n" .
                         "• Use this image in your report\n" .
                         "• Share with relevant authorities\n" .
                         "• Keep a copy for insurance purposes",
            'image' => '/' . $relative_path
        ];
        
        logAIActivity($user_id, 'enhance_image', $file['name'], $relative_path);
    } else {
        $response = ['success' => false, 'error' => 'Failed to save uploaded file. Please check directory permissions.'];
    }
}

// ============================================
// ACTION: SMART SEARCH (Enhanced)
// ============================================
elseif ($action === 'smart_search') {
    $query = trim($_POST['query'] ?? '');
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Please enter search criteria']);
        exit();
    }
    
    // Search in reports database
    $searchResults = [];
    $searchTerm = "%$query%";
    
    $searchStmt = $conn->prepare("
        SELECT id, title, description, item_type, status, created_at, location 
        FROM reports 
        WHERE (title LIKE ? OR description LIKE ? OR item_type LIKE ?) 
        AND status = 'approved'
        LIMIT 10
    ");
    $searchStmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $searchStmt->execute();
    $result = $searchStmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
    
    if (count($searchResults) > 0) {
        $message = "🔍 **Search Results for: \"" . htmlspecialchars($query) . "\"**\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "Found " . count($searchResults) . " matching report(s):\n\n";
        
        foreach ($searchResults as $i => $item) {
            $message .= ($i + 1) . ". **" . htmlspecialchars($item['title']) . "**\n";
            $message .= "   📍 Location: " . htmlspecialchars($item['location'] ?? 'Not specified') . "\n";
            $message .= "   📅 Date: " . date('M d, Y', strtotime($item['created_at'])) . "\n";
            $message .= "   📝 " . htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : '') . "\n\n";
        }
        
        $message .= "💡 **Tip:** Click on 'Search' in the navigation to view full details and filter results.";
        
        $response = ['success' => true, 'message' => $message];
    } else {
        $response = [
            'success' => true, 
            'message' => "🔍 **No results found for: \"" . htmlspecialchars($query) . "\"**\n\n" .
                         "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                         "💡 **Suggestions:**\n" .
                         "• Try different keywords\n" .
                         "• Check spelling\n" .
                         "• Browse all reports using the Search page\n" .
                         "• Consider filing a new report if your item isn't listed"
        ];
    }
    
    logAIActivity($user_id, 'smart_search', $query, json_encode($searchResults));
}

// ============================================
// ACTION: GET HELP (Contextual Help)
// ============================================
elseif ($action === 'get_help') {
    $topic = $_POST['topic'] ?? $_GET['topic'] ?? 'general';
    
    $helpContent = [
        'general' => [
            'title' => '🤖 AI Assistant Help',
            'content' => "**How to use the AI Assistant:**\n\n" .
                        "📝 **Analyze Text** - Describe your jewelry for detailed analysis\n" .
                        "🖼️ **Enhance Image** - Upload photos for processing\n" .
                        "🕵️ **Generate Suspect** - Create suspect composite images\n" .
                        "💎 **Reconstruct Item** - Generate jewelry images from descriptions\n" .
                        "🎤 **Voice Commands** - Click the microphone and speak naturally\n\n" .
                        "Need more help? Contact our support team."
        ],
        'report' => [
            'title' => '📋 Filing a Report',
            'content' => "**How to file a report:**\n\n" .
                        "1. Click the 'Report' button in the navigation\n" .
                        "2. Provide detailed item description\n" .
                        "3. Upload clear photos of the item\n" .
                        "4. Specify incident date and location\n" .
                        "5. Submit for review by moderators\n\n" .
                        "⚠️ Provide as many details as possible for better recovery chances."
        ],
        'claim' => [
            'title' => '📄 Submitting a Claim',
            'content' => "**How to submit a claim:**\n\n" .
                        "1. Find the report you're interested in\n" .
                        "2. Click 'File Claim'\n" .
                        "3. Provide proof of ownership or relationship\n" .
                        "4. Describe how you can identify the item\n" .
                        "5. Wait for admin verification\n\n" .
                        "The report owner will be notified of your claim."
        ],
        'verification' => [
            'title' => '✅ Account Verification',
            'content' => "**Verification benefits:**\n\n" .
                        "• Increased trust from other users\n" .
                        "• Priority support\n" .
                        "• Access to advanced features\n" .
                        "• Higher visibility for your reports\n\n" .
                        "Visit your profile settings to start verification."
        ]
    ];
    
    $help = $helpContent[$topic] ?? $helpContent['general'];
    
    $response = [
        'success' => true,
        'message' => "**" . $help['title'] . "**\n\n" . $help['content']
    ];
    
    logAIActivity($user_id, 'get_help', $topic, $help['title']);
}

// ============================================
// DEFAULT: Invalid action
// ============================================
else {
    $response = [
        'success' => false, 
        'error' => 'Invalid action. Available actions: analyze_description, generate_suspect, reconstruct_item, enhance_image, smart_search, get_help'
    ];
}

// ============================================
// FUNCTIONS
// ============================================

/**
 * Log AI activity to database
 */
function logAIActivity($user_id, $action, $input, $output) {
    global $conn;
    
    try {
        // Create table if not exists
        $createTable = "CREATE TABLE IF NOT EXISTS ai_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100),
            input_data TEXT,
            output_data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        )";
        $conn->query($createTable);
        
        $stmt = $conn->prepare("INSERT INTO ai_activity_logs (user_id, action, input_data, output_data) VALUES (?, ?, ?, ?)");
        $input_json = is_array($input) ? json_encode($input) : (string)$input;
        $output_json = is_array($output) ? json_encode($output) : (string)$output;
        
        // Truncate if too long
        if (strlen($input_json) > 5000) $input_json = substr($input_json, 0, 5000);
        if (strlen($output_json) > 5000) $output_json = substr($output_json, 0, 5000);
        
        $stmt->bind_param("isss", $user_id, $action, $input_json, $output_json);
        $stmt->execute();
    } catch (Exception $e) {
        // Log error but don't break the main response
        error_log("AI Activity Log Error: " . $e->getMessage());
    }
}

// Send final response
echo json_encode($response);
?>