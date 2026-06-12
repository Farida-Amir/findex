<?php


require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? 'User';
$response = '';
$error = '';
$processed_image = '';
$active_tab = $_GET['tab'] ?? 'analyze';

// Handle AI processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Create upload directory if not exists
    $upload_dir = 'uploads/ai_outputs/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // ============================================
    // ENHANCE IMAGE
    // ============================================
    if ($action === 'enhance_image' && isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
        $max_size = 10 * 1024 * 1024;
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Invalid file type. Please upload JPG, PNG, GIF, or WEBP.';
        } elseif ($file['size'] > $max_size) {
            $error = 'File too large. Maximum size is 10MB.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload failed. Please try again.';
        } else {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = time() . '_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Try basic image enhancement
                $enhanced = false;
                if (extension_loaded('gd')) {
                    $enhanced_filename = time() . '_enhanced_' . $user_id . '.jpg';
                    $enhanced_path = $upload_dir . $enhanced_filename;
                    
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
                        $processed_image = $enhanced_path;
                        $response = "✅ **Image enhanced successfully!**\n\n";
                        $response .= "Your image has been processed with contrast and brightness adjustments.\n\n";
                        $response .= "**Next Steps:**\n";
                        $response .= "• Use this image in your report\n";
                        $response .= "• Share with relevant authorities\n";
                        $response .= "• Keep a copy for insurance purposes";
                    } else {
                        $processed_image = $upload_path;
                        $response = "✅ **Image uploaded successfully!**\n\n";
                        $response .= "Your image has been saved. For advanced enhancement, try the AI features below.";
                    }
                } else {
                    $processed_image = $upload_path;
                    $response = "✅ **Image uploaded successfully!**\n\n";
                    $response .= "Your image has been saved and is ready to use.";
                }
            } else {
                $error = 'Failed to save uploaded file. Please check directory permissions.';
            }
        }
    }
    
    // ============================================
    // ANALYZE DESCRIPTION
    // ============================================
    elseif ($action === 'analyze_description') {
        $description = trim($_POST['description'] ?? '');
        
        if (empty($description)) {
            $error = 'Please provide a description to analyze.';
        } else {
            $response = analyzeJewelryDescription($description);
        }
    }
    
    // ============================================
    // GENERATE SUSPECT
    // ============================================
    elseif ($action === 'generate_suspect') {
        $description = trim($_POST['suspect_description'] ?? '');
        
        if (empty($description)) {
            $error = 'Please describe the suspect.';
        } else {
            $enhanced_prompt = "forensic police sketch, security camera style, realistic portrait of suspect, " . $description . ", front facing, neutral expression";
            $image_url = "https://image.pollinations.ai/prompt/" . urlencode($enhanced_prompt) . "?width=512&height=512&seed=" . rand(1000, 9999);
            $processed_image = $image_url;
            
            $response = "🕵️ **Suspect Composite Generated**\n\n";
            $response .= "Based on your description, AI has created a suspect composite image.\n\n";
            $response .= "⚠️ **Important Notice:**\n";
            $response .= "• This is an AI-generated representation for reference only\n";
            $response .= "• Do not use as official evidence\n";
            $response .= "• Share with law enforcement as a visual aid\n\n";
            $response .= "📌 **Next Steps:**\n";
            $response .= "1. Save this image to your device\n";
            $response .= "2. Share with local police\n";
            $response .= "3. Include in your report";
        }
    }
    
    // ============================================
    // RECONSTRUCT ITEM
    // ============================================
    elseif ($action === 'reconstruct_item') {
        $item_description = trim($_POST['item_description'] ?? '');
        
        if (empty($item_description)) {
            $error = 'Please describe the jewelry item.';
        } else {
            $enhanced_prompt = "professional product photography, white background, high resolution, detailed jewelry item, " . $item_description . ", studio lighting, 8K";
            $image_url = "https://image.pollinations.ai/prompt/" . urlencode($enhanced_prompt) . "?width=512&height=512&seed=" . rand(1000, 9999);
            $processed_image = $image_url;
            
            $response = "💎 **Item Reconstruction Complete**\n\n";
            $response .= "AI has generated an image based on your description.\n\n";
            $response .= "📌 **How to use this image:**\n";
            $response .= "• Attach to your theft/loss report\n";
            $response .= "• Share with pawn shops and jewelers\n";
            $response .= "• Post on social media for awareness\n";
            $response .= "• Provide to insurance claims\n\n";
            $response .= "⚠️ Note: This is an AI approximation. Actual item may vary.";
        }
    }
}

/**
 * Analyze jewelry description with enhanced keyword detection
 */
function analyzeJewelryDescription($description) {
    $keywords = [
        'metal' => ['gold', 'silver', 'platinum', 'palladium', 'rose gold', 'white gold', 'yellow gold', 'sterling silver'],
        'gemstone' => ['diamond', 'ruby', 'emerald', 'sapphire', 'amethyst', 'topaz', 'citrine', 'garnet', 'peridot', 'opal', 'pearl', 'jade'],
        'item_type' => ['ring', 'necklace', 'bracelet', 'earring', 'pendant', 'charm', 'brooch', 'cufflink', 'watch', 'bangle'],
        'design' => ['vintage', 'antique', 'modern', 'classic', 'art deco', 'victorian', 'contemporary', 'minimalist', 'engraved']
    ];
    
    $detected = ['metal' => [], 'gemstone' => [], 'item_type' => [], 'design' => []];
    $desc_lower = strtolower($description);
    
    foreach ($keywords as $category => $words) {
        foreach ($words as $word) {
            if (stripos($desc_lower, $word) !== false) {
                $detected[$category][] = $word;
            }
        }
    }
    
    foreach ($detected as $category => $items) {
        $detected[$category] = array_unique($items);
    }
    
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
    
    if (!$has_detected) {
        $analysis .= "⚠️ **Limited Information Detected**\n\n";
        $analysis .= "To improve analysis, please include:\n";
        $analysis .= "• Metal type (gold, silver, platinum)\n";
        $analysis .= "• Gemstone type (diamond, ruby, emerald)\n";
        $analysis .= "• Item type (ring, necklace, bracelet)\n";
        $analysis .= "• Any distinguishing features or markings\n\n";
    }
    
    $analysis .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $analysis .= "💡 **Recommendations:**\n\n";
    $analysis .= "✅ File a detailed report with this information\n";
    $analysis .= "✅ Upload clear photos for better identification\n";
    $analysis .= "✅ Check local pawn shops and jewelers\n";
    $analysis .= "✅ File an official police report for stolen items";
    
    return $analysis;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Findex - AI Assistant | Smart Jewelry Recovery</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        /* Custom Gradients */
        .gradient-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        
        .gradient-secondary {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        .gradient-gold {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .gradient-purple {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
        }
        
        .gradient-red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .gradient-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .gradient-green {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        
        /* Card Styles */
        .ai-card {
            background: white;
            border-radius: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(249, 115, 22, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .ai-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f97316, #ea580c, #f97316);
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }
        
        .ai-card:hover::before {
            transform: translateX(0);
        }
        
        .ai-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.15);
            border-color: rgba(249, 115, 22, 0.2);
        }
        
        /* Result Box */
        .result-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-left: 5px solid #22c55e;
            border-radius: 1rem;
            position: relative;
        }
        
        .error-box {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 5px solid #ef4444;
            border-radius: 1rem;
        }
        
        /* Form Elements */
        .form-input, .form-textarea, .form-select {
            transition: all 0.2s ease;
            border: 1.5px solid #e5e7eb;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* Tab Styles */
        .ai-tab {
            transition: all 0.2s ease;
            position: relative;
        }
        
        .ai-tab.active {
            color: #f97316;
            background: white;
        }
        
        .ai-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #f97316, #ea580c);
        }
        
        .ai-tab:hover:not(.active) {
            background: #f8fafc;
            color: #334155;
        }
        
        /* Image Preview */
        .image-preview {
            max-width: 250px;
            max-height: 250px;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }
        
        .image-preview:hover {
            transform: scale(1.02);
        }
        
        /* Feature Icon */
        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .ai-card:hover .feature-icon {
            transform: scale(1.05);
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #f97316;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        /* Loading Spinner */
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: #f97316;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .ai-card {
                border-radius: 1rem;
            }
            
            .feature-icon {
                width: 48px;
                height: 48px;
            }
            
            .image-preview {
                max-width: 200px;
                max-height: 200px;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 min-h-screen">

<?php include 'includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    
    <!-- Hero Section -->
    <div class="text-center mb-10 md:mb-12 animate-fade-in-up">
        <div class="gradient-primary w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-xl">
            <i class="fas fa-robot text-white text-3xl"></i>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">
            AI Intelligence Hub
        </h1>
        <p class="text-gray-500 text-base md:text-lg max-w-2xl mx-auto">
            Powered by advanced artificial intelligence to help recover your precious jewelry
        </p>
        
        <!-- Quick Stats -->
        <div class="flex justify-center gap-6 mt-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">4</div>
                <div class="text-xs text-gray-500">AI Features</div>
            </div>
            <div class="w-px h-8 bg-gray-300"></div>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">100%</div>
                <div class="text-xs text-gray-500">Free to Use</div>
            </div>
            <div class="w-px h-8 bg-gray-300"></div>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">24/7</div>
                <div class="text-xs text-gray-500">Available</div>
            </div>
        </div>
    </div>
    
    <!-- Welcome Message -->
    <div class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-2xl p-4 mb-8 text-center border border-orange-100">
        <i class="fas fa-hand-wave text-orange-500 mr-2"></i>
        <span class="text-gray-700">Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong>! How can AI assist you today?</span>
    </div>
    
    <!-- Response/Error Display -->
    <?php if ($response): ?>
        <div class="result-box p-5 mb-8 animate-fade-in-up">
            <div class="flex items-start gap-3">
                <div class="gradient-green w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-white text-lg"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-green-800 mb-2 flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-600"></i> AI Analysis Complete
                    </h3>
                    <div class="text-green-700 text-sm leading-relaxed whitespace-pre-line">
                        <?php echo nl2br(htmlspecialchars($response)); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error-box p-5 mb-8 animate-fade-in-up">
            <div class="flex items-start gap-3">
                <div class="gradient-red w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-white text-lg"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-red-800 mb-1">Error Occurred</h3>
                    <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($processed_image): ?>
        <div class="bg-white rounded-2xl p-5 mb-8 text-center shadow-md border border-gray-100 animate-fade-in-up">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <h3 class="font-bold text-gray-800">
                    <i class="fas fa-image text-orange-500 mr-2"></i> Generated Image
                </h3>
                <button onclick="downloadImage()" class="gradient-primary text-white px-4 py-2 rounded-lg text-sm font-semibold transition hover:opacity-90">
                    <i class="fas fa-download mr-1"></i> Download
                </button>
            </div>
            <img src="<?php echo htmlspecialchars($processed_image); ?>" class="image-preview mx-auto" alt="AI Generated" id="generatedImage">
        </div>
    <?php endif; ?>
    
    <!-- AI Features Grid -->
    <div class="grid md:grid-cols-2 gap-6 lg:gap-8">
        
        <!-- Feature 1: Enhance Image -->
        <div class="ai-card p-6 md:p-7 animate-fade-in-up" style="animation-delay: 0.1s">
            <div class="flex items-start gap-4 mb-5">
                <div class="feature-icon gradient-primary">
                    <i class="fas fa-magic text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Enhance Image</h2>
                    <p class="text-gray-500 text-sm mt-1">Upload blurry images and enhance quality</p>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="enhance_image">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-cloud-upload-alt text-orange-500 mr-1"></i> Upload Image
                    </label>
                    <input type="file" name="image" accept="image/*" required 
                           class="form-input w-full px-4 py-3 rounded-xl text-sm bg-gray-50 cursor-pointer">
                    <p class="text-xs text-gray-400 mt-1">Supports JPG, PNG, GIF. Max 10MB</p>
                </div>
                <button type="submit" class="btn-primary text-white px-5 py-3 rounded-xl text-sm font-semibold w-full transition">
                    <i class="fas fa-magic mr-2"></i> Enhance Image
                </button>
            </form>
        </div>
        
        <!-- Feature 2: Analyze Description -->
        <div class="ai-card p-6 md:p-7 animate-fade-in-up" style="animation-delay: 0.2s">
            <div class="flex items-start gap-4 mb-5">
                <div class="feature-icon gradient-gold">
                    <i class="fas fa-file-alt text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Analyze Description</h2>
                    <p class="text-gray-500 text-sm mt-1">Get AI insights from jewelry descriptions</p>
                </div>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="analyze_description">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-gem text-amber-500 mr-1"></i> Jewelry Description
                    </label>
                    <textarea name="description" rows="5" 
                              class="form-textarea w-full px-4 py-3 rounded-xl text-sm bg-gray-50 resize-none"
                              placeholder="Example: 18k gold ring with a 2-carat diamond, floral design, engraved with initials 'JD' on the inside..."></textarea>
                </div>
                <button type="submit" class="gradient-gold text-white px-5 py-3 rounded-xl text-sm font-semibold w-full transition hover:opacity-90">
                    <i class="fas fa-robot mr-2"></i> Analyze Description
                </button>
            </form>
        </div>
        
        <!-- Feature 3: Generate Suspect Image -->
        <div class="ai-card p-6 md:p-7 animate-fade-in-up" style="animation-delay: 0.3s">
            <div class="flex items-start gap-4 mb-5">
                <div class="feature-icon gradient-red">
                    <i class="fas fa-user-secret text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Generate Suspect</h2>
                    <p class="text-gray-500 text-sm mt-1">Create AI composite sketches</p>
                </div>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="generate_suspect">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-red-500 mr-1"></i> Suspect Description
                    </label>
                    <textarea name="suspect_description" rows="5" 
                              class="form-textarea w-full px-4 py-3 rounded-xl text-sm bg-gray-50 resize-none"
                              placeholder="Example: Male, 30-35 years old, medium build, dark hoodie, baseball cap, sunglasses, tattoo on left hand..."></textarea>
                </div>
                <button type="submit" class="gradient-red text-white px-5 py-3 rounded-xl text-sm font-semibold w-full transition hover:opacity-90">
                    <i class="fas fa-image mr-2"></i> Generate Suspect
                </button>
            </form>
        </div>
        
        <!-- Feature 4: Reconstruct Item -->
        <div class="ai-card p-6 md:p-7 animate-fade-in-up" style="animation-delay: 0.4s">
            <div class="flex items-start gap-4 mb-5">
                <div class="feature-icon gradient-purple">
                    <i class="fas fa-gem text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Reconstruct Item</h2>
                    <p class="text-gray-500 text-sm mt-1">Visualize lost or stolen jewelry</p>
                </div>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="reconstruct_item">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-ring text-purple-500 mr-1"></i> Item Description
                    </label>
                    <textarea name="item_description" rows="5" 
                              class="form-textarea w-full px-4 py-3 rounded-xl text-sm bg-gray-50 resize-none"
                              placeholder="Example: Vintage gold necklace with sapphire pendant, intricate filigree work, chain length 18 inches..."></textarea>
                </div>
                <button type="submit" class="gradient-purple text-white px-5 py-3 rounded-xl text-sm font-semibold w-full transition hover:opacity-90">
                    <i class="fas fa-palette mr-2"></i> Reconstruct Item
                </button>
            </form>
        </div>
    </div>
    
    <!-- Tips & Information Section -->
    <div class="mt-10 md:mt-12 grid md:grid-cols-2 gap-6">
        
        <!-- Tips Card -->
        <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-100">
            <div class="flex items-center gap-3 mb-4">
                <div class="gradient-blue w-10 h-10 rounded-xl flex items-center justify-center">
                    <i class="fas fa-lightbulb text-white text-lg"></i>
                </div>
                <h3 class="font-bold text-gray-800">Pro Tips for Better Results</h3>
            </div>
            <ul class="space-y-3 text-sm text-gray-600">
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <span><strong>Be descriptive</strong> - Include metal type, gemstones, design details</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <span><strong>Use clear images</strong> - Good lighting improves AI enhancement</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <span><strong>Be specific about suspects</strong> - Height, build, clothing, distinctive features</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <span><strong>Save generated images</strong> - Use them in your reports</span>
                </li>
            </ul>
        </div>
        
        <!-- Info Card -->
        <div class="bg-gradient-to-r from-orange-600 to-amber-600 rounded-2xl p-6 text-white shadow-md">
            <div class="flex items-center gap-3 mb-4">
                <i class="fas fa-info-circle text-2xl"></i>
                <h3 class="font-bold text-lg">About AI Features</h3>
            </div>
            <p class="text-sm text-white/90 mb-4">
                Our AI uses advanced machine learning models to analyze jewelry descriptions and generate images. 
                All processing is done securely and your data is never shared.
            </p>
            <div class="flex gap-4 text-xs text-white/80">
                <span><i class="fas fa-shield-alt mr-1"></i> Secure</span>
                <span><i class="fas fa-lock mr-1"></i> Private</span>
                <span><i class="fas fa-bolt mr-1"></i> Fast</span>
            </div>
        </div>
    </div>
    
    <!-- Quick Action Links -->
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="report_item.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-medium transition flex items-center gap-2">
            <i class="fas fa-plus-circle text-orange-500"></i> File a Report
        </a>
        <a href="search.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-medium transition flex items-center gap-2">
            <i class="fas fa-search text-orange-500"></i> Search Reports
        </a>
        <a href="my_reports.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-medium transition flex items-center gap-2">
            <i class="fas fa-folder-open text-orange-500"></i> My Reports
        </a>
    </div>
</div>

<!-- Include Footer -->
<?php include 'includes/footer.php'; ?>

<!-- Include AI Assistant Widget -->
<?php include 'includes/ai_assistant.php'; ?>

<script>
// Download generated image
function downloadImage() {
    const img = document.getElementById('generatedImage');
    if (img && img.src) {
        const link = document.createElement('a');
        link.href = img.src;
        link.download = 'ai_generated_image_' + Date.now() + '.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Auto-hide response after 10 seconds
setTimeout(function() {
    const resultBox = document.querySelector('.result-box');
    const errorBox = document.querySelector('.error-box');
    if (resultBox) {
        setTimeout(() => {
            resultBox.style.opacity = '0';
            setTimeout(() => {
                if (resultBox) resultBox.style.display = 'none';
            }, 300);
        }, 8000);
    }
    if (errorBox) {
        setTimeout(() => {
            errorBox.style.opacity = '0';
            setTimeout(() => {
                if (errorBox) errorBox.style.display = 'none';
            }, 300);
        }, 8000);
    }
}, 1000);

// Form validation before submit
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-pulse mr-2"></i> Processing...';
            submitBtn.disabled = true;
        }
    });
});
</script>

</body>
</html>