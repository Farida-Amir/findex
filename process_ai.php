<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit();
}

$response = ['success' => false, 'message' => '', 'image' => ''];

// Analyze description (AI text analysis)
if ($action === 'analyze_description') {
    $description = $_POST['description'] ?? '';
    
    if (empty($description)) {
        echo json_encode(['success' => false, 'error' => 'Please provide a description']);
        exit();
    }
    
    // Keywords for jewelry analysis
    $keywords = [
        'gold' => 'precious metal',
        'silver' => 'precious metal',
        'platinum' => 'precious metal',
        'diamond' => 'precious stone',
        'ruby' => 'precious stone',
        'emerald' => 'precious stone',
        'sapphire' => 'precious stone',
        'ring' => 'jewelry type',
        'necklace' => 'jewelry type',
        'bracelet' => 'jewelry type',
        'earring' => 'jewelry type',
        'pendant' => 'jewelry type',
        'antique' => 'style',
        'vintage' => 'style',
        'modern' => 'style',
        'engraved' => 'feature',
    ];
    
    $found = [];
    foreach ($keywords as $keyword => $type) {
        if (stripos($description, $keyword) !== false) {
            $found[] = $keyword;
        }
    }
    
    $analysis = "📋 **Jewelry Analysis**\n\n";
    if (!empty($found)) {
        $analysis .= "✅ **Detected characteristics:** " . implode(", ", $found) . "\n\n";
        $analysis .= "💎 **Estimated Value:** This appears to be a valuable piece\n\n";
        $analysis .= "🔍 **Recommendation:** File a detailed report with local authorities\n\n";
        $analysis .= "📸 **Tip:** Upload clear photos for better AI matching\n\n";
        $analysis .= "🆘 Need immediate assistance? Contact our support team.";
    } else {
        $analysis = "⚠️ **Unable to identify specific characteristics.**\n\n";
        $analysis .= "Please provide more details about:\n";
        $analysis .= "• Material (gold, silver, platinum)\n";
        $analysis .= "• Gemstones (diamond, ruby, emerald)\n";
        $analysis .= "• Item type (ring, necklace, bracelet)\n";
        $analysis .= "• Any unique markings or engravings";
    }
    
    $response = ['success' => true, 'message' => $analysis];
}

// Generate suspect image
elseif ($action === 'generate_suspect') {
    $description = $_POST['suspect_description'] ?? '';
    
    if (empty($description)) {
        echo json_encode(['success' => false, 'error' => 'Please describe the suspect']);
        exit();
    }
    
    // Using Pollinations.ai - free image generation
    $prompt = urlencode("portrait of suspect, security camera style, " . $description);
    $image_url = "https://image.pollinations.ai/prompt/{$prompt}?width=512&height=512&seed=" . rand(1, 9999);
    
    // Save to database
    $stmt = $pdo->prepare("
        INSERT INTO ai_requests (user_id, request_type, input_data, output_data, status, created_at) 
        VALUES (?, 'generate_suspect', ?, ?, 'completed', NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $description, $image_url]);
    
    $response = [
        'success' => true,
        'message' => "🕵️ **Suspect image generated!**\n\nBased on your description, AI has created a suspect image.\n\n⚠️ This is an AI-generated representation for reference only.",
        'image' => $image_url
    ];
}

// Reconstruct item image
elseif ($action === 'reconstruct_item') {
    $description = $_POST['item_description'] ?? '';
    
    if (empty($description)) {
        echo json_encode(['success' => false, 'error' => 'Please describe the jewelry item']);
        exit();
    }
    
    $prompt = urlencode("detailed jewelry item, professional product photo, white background, high quality, " . $description);
    $image_url = "https://image.pollinations.ai/prompt/{$prompt}?width=512&height=512&seed=" . rand(1, 9999);
    
    $stmt = $pdo->prepare("
        INSERT INTO ai_requests (user_id, request_type, input_data, output_data, status, created_at) 
        VALUES (?, 'reconstruct_item', ?, ?, 'completed', NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $description, $image_url]);
    
    $response = [
        'success' => true,
        'message' => "💎 **Item reconstruction complete!**\n\nAI has generated an image based on your description.\n\n📌 Use this image in your report to help identify your item.",
        'image' => $image_url
    ];
}

// Enhance image
elseif ($action === 'enhance_image' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $upload_dir = 'uploads/ai_outputs/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '_' . basename($file['name']);
    $upload_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $stmt = $pdo->prepare("
            INSERT INTO ai_requests (user_id, request_type, input_data, output_data, status, created_at) 
            VALUES (?, 'enhance_image', ?, ?, 'completed', NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $upload_path, $upload_path]);
        
        $response = [
            'success' => true,
            'message' => "🖼️ **Image uploaded successfully!**\n\nYour image has been saved.\n\n📌 You can use this image in your report.",
            'image' => $upload_path
        ];
    } else {
        $response = ['success' => false, 'error' => 'Failed to upload image'];
    }
}

else {
    $response = ['success' => false, 'error' => 'Invalid action'];
}

echo json_encode($response);
?>