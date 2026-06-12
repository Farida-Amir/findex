<?php
// AI Voice Assistant API - Speech recognition and voice interaction
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login to use AI voice features']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit();
}

$response = ['success' => false, 'message' => '', 'data' => []];

if ($action === 'process_voice') {
    $voice_text = $_POST['voice_text'] ?? '';
    $command_type = $_POST['command_type'] ?? 'general';
    
    if (empty($voice_text)) {
        echo json_encode(['success' => false, 'error' => 'No voice input received']);
        exit();
    }
    
    // Process the voice command
    $result = processVoiceCommand($voice_text, $command_type);
    
    // Log voice interaction
    logVoiceInteraction($_SESSION['user_id'], $voice_text, $result['intent'], $result['confidence']);
    
    $response = [
        'success' => true,
        'data' => $result
    ];
    
} elseif ($action === 'get_voice_response') {
    $query = $_GET['query'] ?? '';
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'No query provided']);
        exit();
    }
    
    $ai_response = generateAIResponse($query);
    
    $response = [
        'success' => true,
        'data' => [
            'response_text' => $ai_response['text'],
            'response_action' => $ai_response['action'],
            'suggested_reply' => $ai_response['suggestions']
        ]
    ];
    
} elseif ($action === 'voice_help') {
    $help_categories = [
        'reporting' => "You can say things like 'File a new report', 'Report a lost item', or 'Submit a claim'",
        'searching' => "Try saying 'Search for jewelry', 'Find reports near me', or 'Look up item'",
        'account' => "Say 'My profile', 'Edit settings', or 'View my reports'",
        'navigation' => "Say 'Go to dashboard', 'Show reports', or 'Open AI assistant'",
        'assistance' => "Say 'Help me with reports', 'How do I file a claim?', or 'What can you do?'"
    ];
    
    $response = [
        'success' => true,
        'data' => [
            'categories' => $help_categories,
            'examples' => [
                "File a new report for a gold ring",
                "Search for diamond earrings reported last week",
                "Show my submitted reports",
                "Help me understand the verification process",
                "Generate a suspect image based on description"
            ]
        ]
    ];
    
} elseif ($action === 'voice_settings') {
    $user_id = $_SESSION['user_id'];
    $settings = $_POST['settings'] ?? '';
    
    if ($settings) {
        $settings_data = json_decode($settings, true);
        
        // Store voice settings
        $stmt = $conn->prepare("UPDATE users SET voice_settings = ? WHERE id = ?");
        $settings_json = json_encode($settings_data);
        $stmt->bind_param("si", $settings_json, $user_id);
        $stmt->execute();
        
        $response = ['success' => true, 'message' => 'Voice settings updated'];
    } else {
        // Get current settings
        $stmt = $conn->prepare("SELECT voice_settings FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $settings = $user['voice_settings'] ? json_decode($user['voice_settings'], true) : [
            'language' => 'en-US',
            'voice_speed' => 1.0,
            'voice_pitch' => 1.0,
            'auto_listen' => false,
            'voice_feedback' => true
        ];
        
        $response = ['success' => true, 'data' => $settings];
    }
    
} else {
    $response = ['success' => false, 'error' => 'Invalid action'];
}

echo json_encode($response);

function processVoiceCommand($text, $command_type) {
    $text = strtolower(trim($text));
    $intent = 'unknown';
    $confidence = 0.0;
    $action_taken = null;
    $response_text = "I'm not sure how to help with that. Try saying 'help' for available commands.";
    
    // Define command patterns
    $commands = [
        'file_report' => [
            'patterns' => ['file report', 'new report', 'create report', 'report item', 'report lost'],
            'response' => "I'll help you file a new report. Please describe the item you want to report."
        ],
        'search' => [
            'patterns' => ['search for', 'find', 'look for', 'search reports', 'find item'],
            'response' => "Let me search for that. What specific item are you looking for?"
        ],
        'my_reports' => [
            'patterns' => ['my reports', 'my submissions', 'my reports list', 'show my reports'],
            'response' => "Opening your reports dashboard."
        ],
        'generate_suspect' => [
            'patterns' => ['generate suspect', 'create suspect', 'suspect image', 'make suspect'],
            'response' => "I'll help you generate a suspect image. Please describe the suspect's appearance."
        ],
        'analyze_jewelry' => [
            'patterns' => ['analyze jewelry', 'analyze item', 'jewelry analysis', 'analyze this'],
            'response' => "Please describe the jewelry item or upload an image for analysis."
        ],
        'help' => [
            'patterns' => ['help', 'what can you do', 'commands', 'how to', 'assist me'],
            'response' => "I can help you file reports, search for items, generate suspect images, analyze jewelry, and navigate the system. What would you like to do?"
        ],
        'dashboard' => [
            'patterns' => ['dashboard', 'home', 'main page', 'go to dashboard'],
            'response' => "Taking you to your dashboard."
        ],
        'profile' => [
            'patterns' => ['my profile', 'profile settings', 'edit profile', 'view profile'],
            'response' => "Opening your profile settings."
        ]
    ];
    
    // Find matching intent
    foreach ($commands as $cmd_intent => $cmd_data) {
        foreach ($cmd_data['patterns'] as $pattern) {
            if (strpos($text, $pattern) !== false) {
                $intent = $cmd_intent;
                $confidence = 0.9;
                $response_text = $cmd_data['response'];
                $action_taken = $intent;
                break 2;
            }
        }
    }
    
    // Extract entities from text
    $entities = extractEntities($text);
    
    return [
        'intent' => $intent,
        'confidence' => $confidence,
        'response_text' => $response_text,
        'action_taken' => $action_taken,
        'entities' => $entities,
        'original_text' => $text
    ];
}

function extractEntities($text) {
    $entities = [];
    
    // Extract item types
    $items = ['ring', 'necklace', 'bracelet', 'earring', 'watch', 'gold', 'silver', 'diamond'];
    foreach ($items as $item) {
        if (strpos($text, $item) !== false) {
            $entities['item_type'][] = $item;
        }
    }
    
    // Extract colors
    $colors = ['gold', 'silver', 'white', 'yellow', 'rose', 'platinum'];
    foreach ($colors as $color) {
        if (strpos($text, $color) !== false) {
            $entities['color'][] = $color;
        }
    }
    
    // Extract time references
    if (preg_match('/(last|past|previous)\s+(day|week|month)/i', $text, $matches)) {
        $entities['timeframe'] = $matches[2];
    }
    
    return $entities;
}

function generateAIResponse($query) {
    $query = strtolower(trim($query));
    $response_text = "";
    $action = null;
    $suggestions = [];
    
    // Check for report-related queries
    if (strpos($query, 'report') !== false || strpos($query, 'stolen') !== false || strpos($query, 'lost') !== false) {
        $response_text = "To file a report, click the 'New Report' button on your dashboard. You'll need to provide details about the item, upload photos, and describe the incident. Would you like me to guide you through the process?";
        $suggestions = ["Guide me through filing a report", "Show me existing reports", "What information do I need?"];
        $action = "guide_report";
    }
    // Claim-related queries
    elseif (strpos($query, 'claim') !== false) {
        $response_text = "To submit a claim, go to the report you're interested in and click 'File Claim'. You'll need to provide proof of ownership or relationship to the item. I can help you prepare the necessary documents.";
        $suggestions = ["How to prove ownership", "What documents are accepted?", "Check my claim status"];
        $action = "claim_help";
    }
    // Verification queries
    elseif (strpos($query, 'verify') !== false || strpos($query, 'verification') !== false) {
        $response_text = "Account verification helps build trust in the community. You can verify your identity by uploading a government ID and business license if applicable. This usually takes 24-48 hours to process.";
        $suggestions = ["Start verification process", "Verification requirements", "Check verification status"];
        $action = "verification_info";
    }
    // Search queries
    elseif (strpos($query, 'search') !== false || strpos($query, 'find') !== false) {
        $response_text = "Use the search bar at the top of any page to find reports, items, or shops. You can filter by date, location, item type, and status. What would you like to search for?";
        $suggestions = ["Search for gold rings", "Recent reports in my area", "Verified jewelry shops"];
        $action = "search_help";
    }
    // General assistance
    else {
        $response_text = "I'm your AI assistant for the jewelry recovery system. I can help with filing reports, submitting claims, searching for items, and account management. What would you like assistance with today?";
        $suggestions = ["File a report", "Submit a claim", "Search for items", "Verify my account"];
        $action = "general_help";
    }
    
    return [
        'text' => $response_text,
        'action' => $action,
        'suggestions' => $suggestions
    ];
}

function logVoiceInteraction($user_id, $voice_text, $intent, $confidence) {
    global $conn;
    
    $createTable = "CREATE TABLE IF NOT EXISTS voice_interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        voice_text TEXT,
        intent VARCHAR(100),
        confidence DECIMAL(5,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    )";
    $conn->query($createTable);
    
    $stmt = $conn->prepare("INSERT INTO voice_interactions (user_id, voice_text, intent, confidence) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issd", $user_id, $voice_text, $intent, $confidence);
    $stmt->execute();
}
?>