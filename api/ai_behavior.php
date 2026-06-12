<?php
// AI Behavior Analysis API - Movement pattern tracking and behavior analysis
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login to use AI features']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit();
}

$response = ['success' => false, 'message' => '', 'data' => []];

// Store behavior data for analysis
if ($action === 'track_behavior') {
    $user_id = $_SESSION['user_id'];
    $behavior_data = $_POST['behavior_data'] ?? '';
    $session_id = session_id();
    
    if (empty($behavior_data)) {
        echo json_encode(['success' => false, 'error' => 'No behavior data provided']);
        exit();
    }
    
    // Decode the behavior data
    $data = json_decode($behavior_data, true);
    
    if ($data) {
        // Create behavior_logs table if not exists
        $createTable = "CREATE TABLE IF NOT EXISTS behavior_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            session_id VARCHAR(255),
            movement_patterns TEXT,
            click_patterns TEXT,
            scroll_patterns TEXT,
            typing_patterns TEXT,
            behavior_score DECIMAL(5,2),
            risk_level VARCHAR(50),
            analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id)
        )";
        $conn->query($createTable);
        
        // Calculate behavior score and risk level
        $movement_score = analyzeMovementPatterns($data['movements'] ?? []);
        $click_score = analyzeClickPatterns($data['clicks'] ?? []);
        $typing_score = analyzeTypingPatterns($data['typing'] ?? []);
        $behavior_score = round(($movement_score + $click_score + $typing_score) / 3, 2);
        
        $risk_level = 'low';
        if ($behavior_score > 70) $risk_level = 'high';
        elseif ($behavior_score > 40) $risk_level = 'medium';
        
        // Store behavior data
        $stmt = $conn->prepare("INSERT INTO behavior_logs (user_id, session_id, movement_patterns, click_patterns, typing_patterns, behavior_score, risk_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $movements_json = json_encode($data['movements'] ?? []);
        $clicks_json = json_encode($data['clicks'] ?? []);
        $typing_json = json_encode($data['typing'] ?? []);
        $stmt->bind_param("issssss", $user_id, $session_id, $movements_json, $clicks_json, $typing_json, $behavior_score, $risk_level);
        $stmt->execute();
        
        $response = [
            'success' => true,
            'message' => "Behavior analyzed successfully",
            'data' => [
                'behavior_score' => $behavior_score,
                'risk_level' => $risk_level,
                'recommendation' => getRiskRecommendation($risk_level)
            ]
        ];
    } else {
        $response = ['success' => false, 'error' => 'Invalid behavior data format'];
    }
    
} elseif ($action === 'get_behavior_insights') {
    $user_id = $_SESSION['user_id'];
    
    // Get user's behavior history
    $stmt = $conn->prepare("SELECT behavior_score, risk_level, analyzed_at FROM behavior_logs WHERE user_id = ? ORDER BY analyzed_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    $avg_score = 0;
    $total = 0;
    $risk_counts = ['low' => 0, 'medium' => 0, 'high' => 0];
    
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
        $avg_score += $row['behavior_score'];
        $total++;
        $risk_counts[$row['risk_level']]++;
    }
    
    $avg_score = $total > 0 ? round($avg_score / $total, 2) : 0;
    
    // Get insights about suspicious patterns
    $insights = generateBehaviorInsights($history);
    
    $response = [
        'success' => true,
        'data' => [
            'average_score' => $avg_score,
            'total_sessions' => $total,
            'risk_distribution' => $risk_counts,
            'history' => $history,
            'insights' => $insights
        ]
    ];
    
} elseif ($action === 'detect_suspicious') {
    $user_id = $_SESSION['user_id'];
    
    // Check for suspicious patterns
    $stmt = $conn->prepare("SELECT COUNT(*) as suspicious_count FROM behavior_logs WHERE user_id = ? AND risk_level = 'high' AND analyzed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $is_suspicious = $row['suspicious_count'] > 3;
    
    $response = [
        'success' => true,
        'data' => [
            'is_suspicious' => $is_suspicious,
            'recent_alerts' => $row['suspicious_count'],
            'action_needed' => $is_suspicious ? 'Verify your identity to continue' : null
        ]
    ];
    
} elseif ($action === 'get_movement_heatmap') {
    $user_id = $_SESSION['user_id'];
    $period = $_GET['period'] ?? 'day';
    
    $interval = 'HOUR';
    if ($period === 'week') $interval = 'DAY';
    if ($period === 'month') $interval = 'DAY';
    
    $stmt = $conn->prepare("SELECT 
        DATE_FORMAT(analyzed_at, '%Y-%m-%d %H:00:00') as time_slot,
        AVG(behavior_score) as avg_score,
        COUNT(*) as count,
        risk_level
        FROM behavior_logs 
        WHERE user_id = ? AND analyzed_at > DATE_SUB(NOW(), INTERVAL 1 $interval)
        GROUP BY time_slot, risk_level
        ORDER BY time_slot DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $response = ['success' => true, 'data' => $data];
    
} else {
    $response = ['success' => false, 'error' => 'Invalid action'];
}

echo json_encode($response);

function analyzeMovementPatterns($movements) {
    if (empty($movements)) return 50;
    
    $total_distance = 0;
    $prev_x = null;
    $prev_y = null;
    $total_time = 0;
    $unusual_patterns = 0;
    
    foreach ($movements as $movement) {
        if ($prev_x !== null && $prev_y !== null) {
            $distance = sqrt(pow($movement['x'] - $prev_x, 2) + pow($movement['y'] - $prev_y, 2));
            $total_distance += $distance;
            
            // Detect erratic movements (very fast changes)
            if ($distance > 200 && isset($movement['timestamp'])) {
                $unusual_patterns++;
            }
        }
        $prev_x = $movement['x'];
        $prev_y = $movement['y'];
    }
    
    // Score based on movement patterns (lower is more normal)
    $score = min(100, ($total_distance / 1000) * 10 + ($unusual_patterns * 5));
    return $score;
}

function analyzeClickPatterns($clicks) {
    if (empty($clicks)) return 50;
    
    $rapid_clicks = 0;
    $prev_time = null;
    
    foreach ($clicks as $click) {
        if ($prev_time !== null && isset($click['timestamp'])) {
            $time_diff = $click['timestamp'] - $prev_time;
            if ($time_diff < 100) { // Less than 100ms between clicks
                $rapid_clicks++;
            }
        }
        $prev_time = $click['timestamp'] ?? null;
    }
    
    $score = min(100, $rapid_clicks * 15);
    return $score;
}

function analyzeTypingPatterns($typing) {
    if (empty($typing)) return 50;
    
    $irregular_patterns = 0;
    $prev_time = null;
    $typing_speeds = [];
    
    foreach ($typing as $keypress) {
        if ($prev_time !== null && isset($keypress['timestamp'])) {
            $time_diff = $keypress['timestamp'] - $prev_time;
            $typing_speeds[] = $time_diff;
            
            // Detect very fast typing (>200 chars per minute)
            if ($time_diff < 50) {
                $irregular_patterns++;
            }
        }
        $prev_time = $keypress['timestamp'] ?? null;
    }
    
    // Calculate average typing speed variance
    $variance = 0;
    if (count($typing_speeds) > 0) {
        $avg = array_sum($typing_speeds) / count($typing_speeds);
        foreach ($typing_speeds as $speed) {
            $variance += pow($speed - $avg, 2);
        }
        $variance = $variance / count($typing_speeds);
        
        // High variance indicates irregular patterns
        if ($variance > 1000) $irregular_patterns += 5;
    }
    
    $score = min(100, $irregular_patterns * 10);
    return $score;
}

function getRiskRecommendation($risk_level) {
    $recommendations = [
        'low' => "Your behavior patterns are normal. Continue using the system as usual.",
        'medium' => "Some unusual patterns detected. Please be mindful of your interactions.",
        'high' => "Suspicious activity detected. Please verify your identity or contact support."
    ];
    return $recommendations[$risk_level] ?? $recommendations['low'];
}

function generateBehaviorInsights($history) {
    $insights = [];
    
    if (count($history) == 0) {
        $insights[] = "No behavior data available yet. Continue using the system to get insights.";
        return $insights;
    }
    
    $recent_score = $history[0]['behavior_score'] ?? 0;
    $trend = "stable";
    
    if (count($history) > 1) {
        $prev_score = $history[1]['behavior_score'] ?? $recent_score;
        if ($recent_score > $prev_score + 10) $trend = "increasing";
        elseif ($recent_score < $prev_score - 10) $trend = "decreasing";
    }
    
    $insights[] = "Your behavior score is currently " . $recent_score . " and is " . $trend . ".";
    
    if ($trend === "increasing") {
        $insights[] = "⚠️ Alert: Your behavior patterns are becoming more erratic. Please be aware of your mouse movements and typing patterns.";
    }
    
    // Check for unusual patterns in recent sessions
    $high_risk_count = 0;
    foreach ($history as $h) {
        if ($h['risk_level'] === 'high') $high_risk_count++;
    }
    
    if ($high_risk_count > 3) {
        $insights[] = "🚨 Multiple high-risk behavior patterns detected in recent sessions. Please contact support if this is an error.";
    }
    
    return $insights;
}
?>